<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginViewResponse;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;
use Laravel\Fortify\TwoFactorAuthenticatable;

class AuthenticateUser
{
    public function __construct(
        protected LoginRateLimiter $limiter
    ) {}

    public function authenticate(Request $request)
    {
        $request->validate([
            Fortify::username() => 'required|string',
            'password' => 'required|string',
        ]);

        // Verificar rate limiting
        if ($this->limiter->tooManyAttempts($request)) {
            $this->limiter->availableIn($request);
            throw ValidationException::withMessages([
                Fortify::username() => __('auth.throttle', [
                    'seconds' => $this->limiter->availableIn($request),
                ]),
            ]);
        }

        // Buscar usuário
        $user = \App\Models\User::where(Fortify::username(), $request->input(Fortify::username()))->first();

        // Verificar se usuário existe e senha está correta
        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->limiter->increment($request);
            throw ValidationException::withMessages([
                Fortify::username() => __('auth.failed'),
            ]);
        }

        // Verificar se usuário está ativo
        if (!$user->active) {
            throw ValidationException::withMessages([
                Fortify::username() => 'Sua conta está desativada. Entre em contato com o administrador.',
            ]);
        }

        // Limpar rate limiting em caso de sucesso
        $this->limiter->clear($request);

        return $user;
    }
}
