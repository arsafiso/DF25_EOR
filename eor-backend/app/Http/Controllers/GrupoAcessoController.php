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

            if ($user->role === 'superadmin') {
                $grupos = GrupoAcesso::all();
            } else {
                $grupos = GrupoAcesso::where('company_id', $user->company_id)->get();
            }

            $gruposArr = $grupos->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'nome' => $grupo->nome,
                    'descricao' => $grupo->descricao,
                    'company_id' => $grupo->company_id,
                    'tipo' => $grupo->tipo,
                    'created_at' => $grupo->created_at,
                    'updated_at' => $grupo->updated_at,
                ];
            });
            return response()->json([
                'message' => 'Grupos de acesso listados com sucesso!',
                'data' => $gruposArr,
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
            'estruturas' => 'nullable|array',
            'estruturas.*.estrutura_id' => 'required_with:estruturas|exists:estruturas,id',
            'estruturas.*.nivel_acesso' => 'required_with:estruturas|in:sem_nivel,leitura,leitura_escrita',
            'special' => 'nullable|boolean',
        ]);

        try {
            $user = Auth::user();
            // Permite superadmin criar grupo para qualquer empresa
            //Log::info('Payload recebido para grupo de acesso:', $request->all());
            $isSpecial = $request->input('special') ? true : false;
            $companyId = ($user->role === 'superadmin' && $request->input('company_id'))
                ? $request->input('company_id')
                : $user->company_id;
            if (!$companyId && !$isSpecial) {
                return response()->json(['error' => 'Usuário não associado a uma empresa.'], 200);
            }

            // Cria o grupo de acesso
            $grupo = GrupoAcesso::create([
                'nome' => $validated['nome'],
                'descricao' => $validated['descricao'] ?? null,
                'company_id' => $isSpecial ? null : $companyId,
                'tipo' => $isSpecial ? 'especial' : 'regular',
            ]);

            // Verifica se há estruturas para associar
            if (!empty($validated['estruturas'])) {
                foreach ($validated['estruturas'] as $estrutura) {
                    $estruturaModel = \App\Models\Estrutura::find($estrutura['estrutura_id']);
                    if (!$estruturaModel) {
                        return response()->json(['error' => 'Estrutura não encontrada.'], 422);
                    }
                    if (!$isSpecial && $estrutura['nivel_acesso'] !== 'sem_nivel' && $estruturaModel->company_id != $grupo->company_id) {
                        return response()->json([
                            'error' => 'Estrutura (' . $estruturaModel->id . ') não pertence à mesma empresa do grupo de acesso.'
                        ], 422);
                    }
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
                'company_id' => $grupo->company_id,
                'tipo' => $grupo->tipo,
                'estruturas' => $grupo->estruturas->map(function ($estrutura) {
                    return [
                        'id' => $estrutura->id,
                        'nome' => $estrutura->estrutura->nome,
                        'grupo_id' => $estrutura->grupo_id,
                        'estrutura_id' => $estrutura->estrutura_id,
                        'company_id' => $estrutura->estrutura->company_id,
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
                    // Busca estrutura no banco para validação do company_id
                    $estruturaModel = \App\Models\Estrutura::find($estrutura['estrutura_id']);
                    if (!$estruturaModel) {
                        return response()->json(['error' => 'Estrutura não encontrada.'], 422);
                    }
                    if ($grupo->tipo !== 'especial' && $estrutura['nivel_acesso'] !== 'sem_nivel' && $estruturaModel->company_id != $grupo->company_id) {
                        return response()->json([
                            'error' => 'Estrutura (' . $estruturaModel->id . ') não pertence à mesma empresa do grupo de acesso.'
                        ], 422);
                    }
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
            'usuario_id' => 'required|exists:users,id',
        ]);

        try {
            $grupo = GrupoAcesso::findOrFail($grupoId);
            $usuario = \App\Models\User::find($validated['usuario_id']);
            $user = Auth::user();

            if ($grupo->tipo === 'especial') {
                if ($user->role !== 'superadmin') {
                    return response()->json([
                        'error' => 'Apenas superadmin pode adicionar usuários a grupos especiais.'
                    ], 403);
                }
            } else {
                if (!$usuario || $usuario->company_id != $grupo->company_id) {
                    return response()->json([
                        'error' => 'Usuário não pertence à empresa do grupo.'
                    ], 422);
                }
            }
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
            $usuario = \App\Models\User::find($validated['usuario_id']);
            if ($grupo->tipo === 'especial') {

                if (!$usuario) {
                    return response()->json(['error' => 'Usuário não encontrado.'], 422);
                }
            } else {
                if (!$usuario || $usuario->company_id != $grupo->company_id) {
                    return response()->json(['error' => 'Usuário não pertence à empresa do grupo.'], 422);
                }
            }
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
