<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    /**
     * Lista os usuários locais e sincroniza com os usuários externos.
     */
    public function index()
    {
        try {
            // Sincroniza os usuários externos com o banco local

            // Adicionar uma validação para verificar se o usuário logado é externo ou interno.
            // $this->sincronizarUsuariosExternos();

            // Busca os campos id, name, email e role da tabela local
            $loggedUser = Auth::user();

            $query = User::select('id', 'name', 'email', 'role', 'company_id')
                ->with(['grupos', 'company']);

            if ($loggedUser->role === 'admin') {
                $query->where('company_id', $loggedUser->company_id)
                       ->where('role', '!=', 'superadmin');
            }

            $usuarios = $query->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'company_id' => $user->company_id,
                    'company_name' => optional($user->company)->nome,
                    'grupos' => $user->grupos,
                ];
            });

            return response()->json([
                'message' => 'Usuários listados com sucesso!',
                'data' => $usuarios,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar usuários.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sincroniza os usuários externos com o banco de dados local.
     */
    private function sincronizarUsuariosExternos()
    {
        $apiUrl = env('EXTERNAL_API_URL') . '/users';
        // Faz uma requisição ao sistema externo para buscar os usuários
        $response = Http::get('https://sistema-externo.com/api/usuarios');

        if ($response->failed()) {
            throw new \Exception('Erro ao sincronizar usuários externos.');
        }

        $usuariosExternos = $response->json();

        // Itera sobre os usuários externos e sincroniza com o banco local
        foreach ($usuariosExternos as $usuarioExterno) {
            User::updateOrCreate(
                ['id' => $usuarioExterno['id']], // ID do sistema externo
                [
                    'name' => $usuarioExterno['name'],
                    'email' => $usuarioExterno['email'],
                    'role' => $usuarioExterno['role'] ?? false,
                ]
            );
        }
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        return response()->json($user);
    }

    /**
     * Cria um novo usuário.
     */
    public function store(Request $request)
    {
        $loggedUser = Auth::user();

        if ($loggedUser->role === 'admin' && $request->input('company_id') != $loggedUser->company_id) {
            return response()->json(['error' => 'Você não tem permissão para criar usuários para outra empresa.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:normal,admin,superadmin',
            'company_id' => 'nullable|exists:companies,id',
            'grupos' => 'array'
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = bcrypt($validated['password']);
        $user->role = $validated['role'] ?? false;
        $user->company_id = $validated['company_id'] ?? null;
        $user->save();

        // Relaciona grupos de acesso, se enviados
        if (!empty($validated['grupos'])) {
            $user->grupos()->sync($validated['grupos']);
        }

        return response()->json($user, 201);
    }

    /**
     * Edita um usuário existente.
     */
    public function update(Request $request, $id)
    {
        $loggedUser = Auth::user();
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        if ($loggedUser->role === 'admin' && $user->company_id != $loggedUser->company_id) {
            return response()->json(['error' => 'Você não tem permissão para editar usuários de outra empresa.'], 403);
        }

        if ($loggedUser->role === 'admin' && $request->input('company_id') != $loggedUser->company_id) {
            return response()->json(['error' => 'Você não pode alterar o usuário para outra empresa.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$id}",
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:normal,admin,superadmin',
            'company_id' => 'nullable|exists:companies,id',
            'grupos' => 'array'
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->role = $validated['role'] ?? false;
        $user->company_id = $validated['company_id'] ?? null;
        $user->save();

        // Atualiza grupos de acesso, se enviados
        if (isset($validated['grupos'])) {
            $user->grupos()->sync($validated['grupos']);
        }

        return response()->json($user);
    }

    /**
     * Exclui um usuário.
     */
    public function destroy($id)
    {
        $loggedUser = Auth::user();
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        if ($loggedUser->role === 'admin' && $user->company_id != $loggedUser->company_id) {
            return response()->json(['error' => 'Você não tem permissão para excluir usuários de outra empresa.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Usuário excluído com sucesso']);
    }
}
