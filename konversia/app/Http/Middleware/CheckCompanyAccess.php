<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super admin tem acesso a tudo
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Usuários devem ter empresa associada
        if (!$user->company_id) {
            abort(403, 'Usuário não associado a uma empresa');
        }

        // Verificar se há parâmetro de company_id na rota
        $routeCompanyId = $request->route('company_id') ??
                         $request->route('company')?->id ??
                         $request->input('company_id');

        if ($routeCompanyId && $routeCompanyId != $user->company_id) {
            abort(403, 'Acesso negado: empresa não autorizada');
        }

        // Para requests sem company_id explícita, assumir a empresa do usuário
        if (!$request->has('company_id') && !$request->route('company_id')) {
            $request->merge(['company_id' => $user->company_id]);
        }

        return $next($request);
    }
}
