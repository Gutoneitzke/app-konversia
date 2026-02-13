<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'super_admin') {
                abort(403, 'Acesso negado. Apenas administradores podem acessar esta área.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $companies = Company::with('owner')->latest()->get();

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'nullable|string|max:20',
        ]);

        $company = Company::create($validated);

        return redirect()->back()->with('success', 'Empresa criada com sucesso!');
    }

    public function show(Company $company)
    {
        $company->load(['owner', 'users', 'whatsappNumbers']);

        return Inertia::render('Companies/Show', [
            'company' => $company,
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,email,' . $company->id,
            'phone' => 'nullable|string|max:20',
            'active' => 'boolean',
        ]);

        $company->update($validated);

        return redirect()->back()->with('success', 'Empresa atualizada com sucesso!');
    }

    public function destroy(Company $company)
    {
        // Verificar se há usuários associados
        if ($company->users()->count() > 0) {
            return redirect()->back()->with('error', 'Não é possível excluir uma empresa com usuários associados.');
        }

        $company->delete();

        return redirect()->route('admin.companies.index')->with('success', 'Empresa excluída com sucesso!');
    }
}
