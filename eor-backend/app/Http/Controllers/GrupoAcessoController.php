<?php

namespace App\Http\Controllers;

use App\Models\GrupoAcesso;
use App\Models\GrupoEstruturaAcesso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
//use Illuminate\Support\Facades\Log;

class GrupoAcessoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = Auth::user();
            if ($user->role == 'superadmin') {
                $grupos = GrupoAcesso::with('estruturas')->get();
            } else {
                // Busca os grupos de acesso do mesmo company_id do usuário autenticado
                $grupos = GrupoAcesso::where('company_id', $user->company_id)
                    ->with('estruturas')
                    ->get();
            }

            return response()->json([
                'message' => 'Grupos de acesso listados com sucesso!',
                'data' => $grupos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar grupos de acesso.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:grupos_acesso,nome',
            'descricao' => 'nullable|string',
            'estruturas' => 'nullable|array', // Valida que estruturas é um array
            'estruturas.*.estrutura_id' => 'required_with:estruturas|exists:estruturas,id', // Valida cada estrutura
            'estruturas.*.nivel_acesso' => 'required_with:estruturas|in:sem_nivel,leitura,leitura_escrita', // Valida o nível de acesso
        ]);

        try {
            $user = Auth::user();
            // Permite superadmin criar grupo para qualquer empresa
            //Log::info('Payload recebido para grupo de acesso:', $request->all());
            $companyId = ($user->role === 'superadmin' && $request->input('company_id'))
                ? $request->input('company_id')
                : $user->company_id;
            //Log::info('Company ID atribuído ao grupo:', ['company_id' => $companyId, 'user_id' => $user->id, 'role' => $user->role]);
            if (!$companyId) {
                return response()->json(['error' => 'Usuário não associado a uma empresa.'], 200);
            }

            // Cria o grupo de acesso
            $grupo = GrupoAcesso::create([
                'nome' => $validated['nome'],
                'descricao' => $validated['descricao'] ?? null,
                'company_id' => $companyId,
            ]);

            // Verifica se há estruturas para associar
            if (!empty($validated['estruturas'])) {
                foreach ($validated['estruturas'] as $estrutura) {
                    GrupoEstruturaAcesso::create([
                        'grupo_id' => $grupo->id,
                        'estrutura_id' => $estrutura['estrutura_id'],
                        'nivel_acesso' => $estrutura['nivel_acesso'],
                    ]);
                }
            }

            return response()->json([
                'message' => 'Grupo de acesso criado com sucesso!',
                'data' => $grupo->load('estruturas'), // Retorna o grupo com as estruturas associadas
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar grupo de acesso.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            // Busca o grupo com as estruturas relacionadas
            $grupo = GrupoAcesso::with(['estruturas' => function ($query) {
                $query->select('id', 'grupo_id', 'estrutura_id', 'nivel_acesso', 'created_at', 'updated_at');
            }])->findOrFail($id);

            $user = Auth::user();
            if ($user->role != 'superadmin' && $user->company_id !== $grupo->company_id) {
                return response()->json(['message' => 'Você não tem permissão para visualizar este grupo.'], 403);
            }

            // Retorna o grupo no formato solicitado
            return response()->json([
                'id' => $grupo->id,
                'nome' => $grupo->nome,
                'descricao' => $grupo->descricao,
                'created_at' => $grupo->created_at,
                'updated_at' => $grupo->updated_at,
                'estruturas' => $grupo->estruturas->map(function ($estrutura) {
                    return [
                        'id' => $estrutura->id,
                        'finalidade' => $estrutura->estrutura->finalidade,
                        'grupo_id' => $estrutura->grupo_id,
                        'estrutura_id' => $estrutura->estrutura_id,
                        'nivel_acesso' => $estrutura->nivel_acesso,
                        'created_at' => $estrutura->created_at,
                        'updated_at' => $estrutura->updated_at,
                    ];
                }),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar grupo de acesso.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'nome' => 'sometimes|string|max:255|unique:grupos_acesso,nome,' . $id,
            'descricao' => 'nullable|string',
            'estruturas' => 'nullable|array', // Valida que estruturas é um array
            'estruturas.*.estrutura_id' => 'required_with:estruturas|exists:estruturas,id', // Valida cada estrutura
            'estruturas.*.nivel_acesso' => 'required_with:estruturas|in:sem_nivel,leitura,leitura_escrita', // Valida o nível de acesso
        ]);

        try {
            // Busca o grupo de acesso
            $grupo = GrupoAcesso::findOrFail($id);
            $user = Auth::user();
            if ($user->role != 'superadmin' && $user->company_id !== $grupo->company_id) {
                return response()->json(['message' => 'Você não tem permissão para editar este grupo.'], 403);
            }

            // Atualiza os dados do grupo
            $grupo->update([
                'nome' => $validated['nome'] ?? $grupo->nome,
                'descricao' => $validated['descricao'] ?? $grupo->descricao,
            ]);

            // Atualiza as associações de estruturas, se fornecidas
            if (!empty($validated['estruturas'])) {
                // Remove todas as associações existentes para recriar
                GrupoEstruturaAcesso::where('grupo_id', $grupo->id)->delete();

                // Cria as novas associações
                foreach ($validated['estruturas'] as $estrutura) {
                    GrupoEstruturaAcesso::create([
                        'grupo_id' => $grupo->id,
                        'estrutura_id' => $estrutura['estrutura_id'],
                        'nivel_acesso' => $estrutura['nivel_acesso'],
                    ]);
                }
            }

            return response()->json([
                'message' => 'Grupo de acesso atualizado com sucesso!',
                'data' => $grupo->load('estruturas'), // Retorna o grupo com as estruturas associadas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar grupo de acesso.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Busca o grupo de acesso
            $grupo = GrupoAcesso::findOrFail($id);

            $user = Auth::user();
            if ($user->role != 'superadmin' && $user->company_id !== $grupo->company_id) {
                return response()->json(['message' => 'Você não tem permissão para excluir este grupo.'], 403);
            }

            // Remove todas as associações de estruturas relacionadas ao grupo
            GrupoEstruturaAcesso::where('grupo_id', $grupo->id)->delete();

            // Exclui o grupo
            $grupo->delete();

            return response()->json([
                'message' => 'Grupo de acesso excluído com sucesso!',
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao excluir grupo de acesso.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Adicionar um usuário ao grupo.
     */
    public function addUsersToGroup(Request $request, string $grupoId)
    {
        $validated = $request->validate([
            'usuario_id' => 'required|exists:users,id', // Verifica se o usuário existe
        ]);

        try {
            // Busca o grupo de acesso
            $grupo = GrupoAcesso::findOrFail($grupoId);

            // Adiciona o usuário ao grupo
            $grupo->usuarios()->attach($validated['usuario_id']);

            return response()->json([
                'message' => 'Usuário adicionado ao grupo com sucesso!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao adicionar usuário ao grupo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUsuariosGroup(Request $request, string $grupoId)
    {
        try {
            $grupo = GrupoAcesso::with('usuarios')->findOrFail($grupoId);

            return response()->json([
                'message' => 'Usuários do grupo listados com sucesso!',
                'data' => $grupo->usuarios,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar usuários do grupo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remover um usuário do grupo.
     */
    public function removeUsersFromGroup(Request $request, string $grupoId)
    {
        $validated = $request->validate([
            'usuario_id' => 'required|exists:users,id', // Verifica se o usuário existe
        ]);

        try {
            // Busca o grupo de acesso
            $grupo = GrupoAcesso::findOrFail($grupoId);

            // Remove o usuário do grupo
            $grupo->usuarios()->detach($validated['usuario_id']);

            return response()->json([
                'message' => 'Usuário removido do grupo com sucesso!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao remover usuário do grupo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
