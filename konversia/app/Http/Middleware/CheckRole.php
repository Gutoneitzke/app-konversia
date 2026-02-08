<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super admin tem acesso a tudo
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Verificar role específica
        if ($user->role !== $role) {
            abort(403, 'Acesso não autorizado');
        }

        // Para company_owner e employee, verificar se tem empresa
        if (in_array($role, ['company_owner', 'employee']) && !$user->company_id) {
            abort(403, 'Usuário não associado a uma empresa');
        }

        // Para company_owner, verificar se é realmente dono
        if ($role === 'company_owner' && !$user->is_owner) {
            abort(403, 'Acesso restrito a donos de empresa');
        }

        return $next($request);
    }
}
