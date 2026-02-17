<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Department;
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
}
