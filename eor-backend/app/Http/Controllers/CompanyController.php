<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    // Listar todas as empresas
    public function index()
    {
        $user = Auth::user();
        if ($user && $user->role === 'superadmin') {
            return response()->json(Company::all());
            
        }
        $company = Company::where('id', $user->company_id)->get();
        return response()->json($company);
    }

    // Criar nova empresa
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
        ]);

        $company = Company::create($validated);

        return response()->json($company, 201);
    }

    // Mostrar uma empresa específica
    public function show($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['error' => 'Empresa não encontrada'], 404);
        }

        return response()->json($company);
    }

    // Atualizar empresa
    public function update(Request $request, $id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['error' => 'Empresa não encontrada'], 404);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
        ]);

        $company->update($validated);

        return response()->json($company);
    }

    // Excluir empresa
    public function destroy($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['error' => 'Empresa não encontrada'], 404);
        }

        $company->delete();

        return response()->json(['message' => 'Empresa excluída com sucesso']);
    }

    // Listar usuários de uma empresa
    public function getUsers($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['error' => 'Empresa não encontrada'], 404);
        }

        return response()->json($company->users);
    }

    // Adicionar usuário a uma empresa
    public function addUser(Request $request, $id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['error' => 'Empresa não encontrada'], 404);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = $company->users()->find($validated['user_id']);

        if ($user) {
            return response()->json(['error' => 'Usuário já está associado a esta empresa'], 400);
        }

        $user = User::find($validated['user_id']);
        $user->company_id = $company->id;
        $user->save();

        return response()->json(['message' => 'Usuário adicionado com sucesso']);
    }

    // Remover usuário de uma empresa
    public function removeUser($id, $userId)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['error' => 'Empresa não encontrada'], 404);
        }

        $user = $company->users()->find($userId);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado na empresa'], 404);
        }

        $user = User::find($userId);
        $user->company_id = null;
        $user->save();

        return response()->json(['message' => 'Usuário removido com sucesso']);
    }
}