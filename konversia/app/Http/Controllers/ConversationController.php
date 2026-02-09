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
            ->with(['contact', 'department', 'assignedUser', 'messages' => function ($q) {
                $q->latest('sent_at')->limit(1);
            }]);

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
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('contact', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('jid', 'like', "%{$search}%");
            })->orWhere('contact_name', 'like', "%{$search}%");
        }

        // Ordenação
        $query->latest('last_message_at');

        $conversations = $query->paginate(20);

        // Contar não lidas e adicionar ao array
        $conversations->getCollection()->transform(function ($conversation) {
            $conversation->unread_count = $conversation->messages()
                ->where('direction', 'inbound')
                ->whereNull('read_at')
                ->count();
            return $conversation;
        });

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

        return Inertia::render('Conversations/Index', [
            'conversations' => $conversations,
            'departments' => $departments,
            'filters' => [
                'status' => $request->status ?? 'all',
                'department_id' => $request->department_id ?? null,
                'search' => $request->search ?? '',
            ],
            'stats' => $stats,
        ]);
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
