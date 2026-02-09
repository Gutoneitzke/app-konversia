<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsAppNumber;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Dashboard por role
        if ($user->isSuperAdmin()) {
            return $this->superAdminDashboard();
        } elseif ($user->isCompanyOwner()) {
            return $this->companyOwnerDashboard($user);
        } else {
            return $this->employeeDashboard($user);
        }
    }

    /**
     * Dashboard Super Admin - Visão geral de todas as empresas
     */
    private function superAdminDashboard()
    {
        $stats = [
            'total_companies' => Company::count(),
            'active_companies' => Company::where('active', true)->count(),
            'total_users' => User::where('role', '!=', 'super_admin')->count(),
            'total_conversations' => Conversation::count(),
            'pending_conversations' => Conversation::where('status', 'pending')->count(),
            'total_messages' => Message::count(),
            'connected_numbers' => WhatsAppNumber::where('status', 'connected')->count(),
        ];

        $recent_companies = Company::with('whatsappNumbers')
            ->latest()
            ->limit(10)
            ->get();

        $companies_stats = Company::withCount([
            'users',
            'conversations',
            'whatsappNumbers'
        ])
        ->with(['whatsappNumbers' => function ($query) {
            $query->where('status', 'connected');
        }])
        ->latest()
        ->get();

        return Inertia::render('Dashboard/SuperAdmin', [
            'stats' => $stats,
            'recentCompanies' => $recent_companies,
            'companiesStats' => $companies_stats,
        ]);
    }

    /**
     * Dashboard Company Owner - Estatísticas da empresa
     */
    private function companyOwnerDashboard(User $user)
    {
        $company = $user->company;
        
        if (!$company) {
            abort(403, 'Usuário não possui empresa associada');
        }

        $stats = [
            'total_users' => $company->users()->count(),
            'total_departments' => $company->departments()->count(),
            'total_conversations' => $company->conversations()->count(),
            'pending_conversations' => $company->conversations()->where('status', 'pending')->count(),
            'in_progress_conversations' => $company->conversations()->where('status', 'in_progress')->count(),
            'resolved_conversations' => $company->conversations()->where('status', 'resolved')->count(),
            'total_messages' => $company->messages()->count(),
            'whatsapp_numbers' => $company->whatsappNumbers()->count(),
            'connected_numbers' => $company->whatsappNumbers()->where('status', 'connected')->count(),
        ];

        // Conversas por departamento
        $conversations_by_department = $company->departments()
            ->withCount(['conversations as pending_count' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->withCount(['conversations as in_progress_count' => function ($query) {
                $query->where('status', 'in_progress');
            }])
            ->get();

        // Conversas recentes
        $recent_conversations = $company->conversations()
            ->with(['contact', 'department', 'assignedUser'])
            ->latest('last_message_at')
            ->limit(10)
            ->get();

        // Mensagens hoje
        $messages_today = $company->messages()
            ->whereDate('messages.created_at', today())
            ->count();

        return Inertia::render('Dashboard/CompanyOwner', [
            'company' => $company,
            'stats' => $stats,
            'conversationsByDepartment' => $conversations_by_department,
            'recentConversations' => $recent_conversations,
            'messagesToday' => $messages_today,
        ]);
    }

    /**
     * Dashboard Employee - Conversas pendentes
     */
    private function employeeDashboard(User $user)
    {
        $company = $user->company;
        
        if (!$company) {
            abort(403, 'Usuário não possui empresa associada');
        }
        $departments = $user->getActiveDepartments()->get()->pluck('id');

        // Conversas pendentes dos departamentos do usuário
        $pending_conversations = Conversation::where('company_id', $company->id)
            ->whereIn('department_id', $departments)
            ->where('status', 'pending')
            ->with(['contact', 'department', 'messages' => function ($query) {
                $query->latest('sent_at')->limit(1);
            }])
            ->latest('last_message_at')
            ->get();

        // Conversas atribuídas ao usuário
        $my_conversations = Conversation::where('company_id', $company->id)
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->with(['contact', 'department'])
            ->latest('last_message_at')
            ->get();

        // Estatísticas pessoais
        $stats = [
            'pending_count' => $pending_conversations->count(),
            'my_conversations_count' => $my_conversations->count(),
            'messages_today' => Message::whereHas('conversation', function ($query) use ($company, $departments) {
                $query->where('company_id', $company->id)
                      ->whereIn('department_id', $departments);
            })
            ->whereDate('messages.created_at', today())
            ->where('direction', 'inbound')
            ->count(),
        ];

        return Inertia::render('Dashboard/Employee', [
            'pendingConversations' => $pending_conversations,
            'myConversations' => $my_conversations,
            'stats' => $stats,
        ]);
    }
}
