<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;
        // Listar apenas funcionários (não incluir o dono da empresa)
        $users = $company->users()
            ->where('is_owner', false)
            ->with('department')
            ->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'company' => $company->load('departments'),
        ]);
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'department_id' => 'required|exists:departments,id',
        ]);

        // Verificar se o departamento pertence à empresa
        $department = Department::where('id', $validated['department_id'])
            ->where('company_id', $company->id)
            ->first();

        if (!$department) {
            return back()->withErrors(['department_id' => 'Departamento inválido.']);
        }

        // Gerar senha temporária
        $temporaryPassword = 'temp' . rand(1000, 9999);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'company_id' => $company->id,
            'department_id' => $validated['department_id'],
            'role' => 'user',
            'active' => true,
        ]);

        // TODO: Enviar email com senha temporária

        return redirect()->back()->with('success', 'Usuário criado com sucesso! Uma senha temporária foi gerada.');
    }

    public function show(User $user)
    {
        // Verificar se o usuário pertence à mesma empresa
        if ($user->company_id !== auth()->user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        $user->load(['department', 'company']);

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        // Verificar se o usuário pertence à mesma empresa
        if ($user->company_id !== auth()->user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'department_id' => 'required|exists:departments,id',
            'active' => 'boolean',
        ]);

        // Verificar se o departamento pertence à empresa
        $department = Department::where('id', $validated['department_id'])
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (!$department) {
            return back()->withErrors(['department_id' => 'Departamento inválido.']);
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Usuário atualizado com sucesso!');
    }

    public function updateStatus(Request $request, User $user)
    {
        // Verificar se o usuário pertence à mesma empresa
        if ($user->company_id !== auth()->user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        $validated = $request->validate([
            'active' => 'required|boolean',
        ]);

        $user->update(['active' => $validated['active']]);

        return redirect()->back()->with('success', 'Status do usuário atualizado com sucesso!');
    }

    public function destroy(User $user)
    {
        // Verificar se o usuário pertence à mesma empresa
        if ($user->company_id !== auth()->user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        // Não permitir excluir o próprio usuário
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Não é possível excluir sua própria conta.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuário excluído com sucesso!');
    }
}
