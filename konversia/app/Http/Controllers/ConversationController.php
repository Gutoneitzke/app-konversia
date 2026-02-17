<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationTransfer;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    /**
     * Lista de conversas
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'Usuário não possui empresa associada');
        }

        // Query base
        $query = Conversation::where('company_id', $company->id)
            ->with(['contact', 'department', 'assignedUser', 'lastMessage'])
            ->withCount(['unreadMessages']);

        // Filtros por role
        if ($user->isEmployee()) {
            // Employee só vê conversas dos seus departamentos
            $departments = $user->getActiveDepartments()->get()->pluck('id');
            $query->whereIn('department_id', $departments);
        }

        // Filtro por status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtro por departamento
        if ($request->has('department_id') && $request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        // Busca por contato
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->whereHas('contact', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('jid', 'like', "%{$search}%");
                })
                ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        // Ordenação
        $query->latest('last_message_at');

        $conversations = $query->paginate(50); // Aumentado para melhor UX no chat

        // Departamentos para filtro
        $departments = Department::where('company_id', $company->id)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        // Usuários da empresa para transferência
        $users = User::where('company_id', $company->id)
            ->where('users.active', true) // Especificar tabela para evitar ambiguidade
            ->with(['departments' => function ($query) {
                $query->where('departments.active', true)
                      ->where('user_departments.active', true); // Especificar tabela pivot
            }])
            ->orderBy('name')
            ->get();

        // Estatísticas
        $stats = [
            'total' => Conversation::where('company_id', $company->id)->count(),
            'pending' => Conversation::where('company_id', $company->id)
                ->where('status', 'pending')
                ->count(),
            'in_progress' => Conversation::where('company_id', $company->id)
                ->where('status', 'in_progress')
                ->count(),
            'resolved' => Conversation::where('company_id', $company->id)
                ->where('status', 'resolved')
                ->count(),
        ];

        // Conversa selecionada (se informada) - SEM FILTROS para garantir que seja sempre carregada
        $selectedConversation = null;
        if ($request->has('selected') && $request->selected) {
            $selectedConversation = Conversation::where('company_id', $company->id)
                ->where('id', $request->selected)
                ->with([
                    'contact',
                    'department',
                    'assignedUser',
                    'messages' => function ($query) {
                        $query->orderBy('sent_at');
                    }
                ])
                ->first();

            // Verificar permissão para employee
            if ($selectedConversation && $user->isEmployee() && !$user->belongsToDepartment($selectedConversation->department_id)) {
                $selectedConversation = null;
            }

        }
        return Inertia::render('Conversations/Index', [
            'conversations' => $conversations,
            'departments' => $departments,
            'users' => $users,
            'filters' => [
                'status' => $request->status ?? 'all',
                'department_id' => $request->department_id ?? null,
                'search' => $request->search ?? '',
            ],
            'stats' => $stats,
            'selectedConversation' => $selectedConversation,
        ]);
    }

    /**
     * Buscar apenas mensagens de uma conversa (para polling)
     */
    public function getMessages(Conversation $conversation, Request $request)
    {
        $user = $request->user();

        // Verificar permissão
        if ($conversation->company_id !== $user->company_id) {
            abort(403);
        }

        // Employee só pode ver conversas dos seus departamentos
        if ($user->isEmployee() && !$user->belongsToDepartment($conversation->department_id)) {
            abort(403);
        }

        $messages = $conversation->messages()
            ->orderBy('sent_at')
            ->get();

        return response()->json([
            'messages' => $messages,
            'conversation_id' => $conversation->id
        ]);
    }

    /**
     * Marcar mensagens de uma conversa como lidas
     */
    public function markAsRead(Conversation $conversation, Request $request)
    {
        $user = $request->user();

        // Verificar permissão
        if ($conversation->company_id !== $user->company_id) {
            abort(403);
        }

        // Employee só pode marcar como lidas conversas dos seus departamentos
        if ($user->isEmployee() && !$user->belongsToDepartment($conversation->department_id)) {
            abort(403);
        }

        // Marcar mensagens inbound não lidas como lidas
        $conversation->messages()
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Redirecionar de volta para a página de conversas com a conversa selecionada
        return redirect()->route('conversations.index', ['selected' => $conversation->id]);
    }

    /**
     * Exibir conversa específica
     */
    public function show(Conversation $conversation, Request $request)
    {
        $user = $request->user();

        // Verificar permissão
        if ($conversation->company_id !== $user->company_id) {
            abort(403);
        }

        // Employee só pode ver conversas dos seus departamentos
        if ($user->isEmployee() && !$user->belongsToDepartment($conversation->department_id)) {
            abort(403);
        }

        $conversation->load([
            'contact',
            'department',
            'assignedUser',
            'messages' => function ($query) {
                $query->orderBy('sent_at');
            }
        ]);

        // Marcar mensagens como lidas
        $conversation->messages()
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Inertia::render('Conversations/Show', [
            'conversation' => $conversation,
        ]);
    }

    /**
     * Transferir conversa para outro departamento
     */
    public function transfer(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $validated = $request->validate([
            'to_department_id' => 'required|exists:departments,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Verificar se a conversa pertence à empresa do usuário
        if ($conversation->company_id !== $user->company_id) {
            abort(403, 'Acesso negado');
        }

        $toDepartment = Department::findOrFail($validated['to_department_id']);

        // Verificar se o departamento de destino pertence à mesma empresa
        if ($toDepartment->company_id !== $user->company_id) {
            abort(403, 'Departamento de destino inválido');
        }

        // Verificar se o departamento de destino é diferente do atual
        if ($toDepartment->id === $conversation->department_id) {
            return redirect()->back()->withErrors(['to_department_id' => 'A conversa já pertence a este departamento']);
        }

        // Verificar se o usuário atribuído (se informado) pertence ao departamento de destino
        if ($validated['assigned_to_user_id']) {
            $assignedUser = \App\Models\User::findOrFail($validated['assigned_to_user_id']);
            if (!$assignedUser->belongsToDepartment($toDepartment->id)) {
                return redirect()->back()->withErrors(['assigned_to_user_id' => 'O usuário atribuído não pertence ao departamento de destino']);
            }
        }

        // Criar registro de transferência
        $transfer = ConversationTransfer::create([
            'conversation_id' => $conversation->id,
            'from_department_id' => $conversation->department_id,
            'to_department_id' => $toDepartment->id,
            'from_user_id' => $user->id,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'transferred_at' => now(),
        ]);

        // Atualizar conversa
        $conversation->update([
            'department_id' => $toDepartment->id,
            'assigned_to' => $validated['assigned_to_user_id'] ?? null,
            'transferred_from_department_id' => $conversation->department_id,
            'transferred_at' => now(),
            'transfer_notes' => $validated['notes'] ?? null,
            'status' => 'pending', // Resetar status para pending quando transferida
        ]);

        // Log da transferência
        \Illuminate\Support\Facades\Log::info('Conversa transferida', [
            'conversation_id' => $conversation->id,
            'from_department' => $conversation->department->name,
            'to_department' => $toDepartment->name,
            'transferred_by' => $user->name,
            'assigned_to' => $validated['assigned_to_user_id'] ? $assignedUser->name : null,
        ]);

        // Redirecionar de volta para a página de conversas sem filtros ou conversa selecionada
        return redirect()->to('/conversations')->with('success', 'Conversa transferida com sucesso');
    }
}
