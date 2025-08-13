<?php

namespace App\Http\Controllers;

use App\Models\Estrutura;
use App\Models\GrupoEstruturaAcesso;
use Illuminate\Http\Request;
use App\Http\Requests\EstruturaRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EstruturaController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role == 'superadmin') {
            $estruturas = Estrutura::all();
        } else {
            // Busca os IDs dos grupos do usuário
            $gruposIds = $user->grupos()->pluck('grupos_acesso.id');
            // Busca os IDs das estruturas acessíveis por esses grupos, exceto 'sem_nivel'
            $estruturasIds = GrupoEstruturaAcesso::whereIn('grupo_id', $gruposIds)
                ->where('nivel_acesso', '!=', 'sem_nivel')
                ->pluck('estrutura_id');
            // Busca as estruturas
            $estruturas = Estrutura::whereIn('id', $estruturasIds)->get();
        }

        return response()->json([
            'message' => 'Estruturas listadas com sucesso!',
            'data' => $estruturas,
        ], 200);
    }

    public function store(EstruturaRequest $request)
    {
        try {
            $user = Auth::user();

            if (!in_array($user->role, ['admin', 'superadmin'])) {
                return response()->json(['message' => 'Você não tem permissão para criar estruturas.'], 403);
            }

            if (!$user->company_id) {
                return response()->json(['error' => 'Usuário não associado a uma empresa.'], 200);
            }

            $estrutura = Estrutura::create(array_merge(
                $request->validated(),
                [
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'company_id' => ($user->role === 'superadmin' && $request->input('company_id'))
                        ? $request->input('company_id')
                        : $user->company_id,
                    'altura_maxima_federal' => $request->input('altura_maxima_federal'),
                    'altura_maxima_estadual' => $request->input('altura_maxima_estadual'),
                ]
            ));
            return response()->json(['message' => 'Estrutura criada com sucesso!', 'data' => $estrutura], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao criar estrutura.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Estrutura $estrutura)
    {
        try {
            $user = Auth::user();

            if ($user->role != 'superadmin' && $user->company_id !== $estrutura->company_id) {
                return response()->json(['message' => 'Você não tem permissão para visualizar esta estrutura.'], 403);
            }

            if (!in_array($user->role, ['admin', 'superadmin'])) {
                $gruposIds = $user->grupos()->pluck('grupos_acesso.id');
                $acessos = GrupoEstruturaAcesso::where('estrutura_id', $estrutura->id)
                    ->whereIn('grupo_id', $gruposIds)
                    ->get();

                $temAcesso = $acessos->contains(function ($acesso) {
                    return $acesso->nivel_acesso !== 'sem_nivel';
                });

                $podeEditar = $acessos->contains(function ($acesso) {
                    return $acesso->nivel_acesso === 'leitura_escrita';
                });

                if (!$temAcesso) {
                    return response()->json(['message' => 'Você não tem permissão para visualizar esta estrutura.'], 403);
                }

                $estrutura->pode_editar = $podeEditar;
            }

            return response()->json($estrutura, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao buscar estrutura.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(EstruturaRequest $request, Estrutura $estrutura)
    {
        try {
            $user = Auth::user();
            if ($user->role != 'superadmin' && $user->company_id !== $estrutura->company_id) {
                return response()->json(['message' => 'Você não tem permissão para editar esta estrutura.'], 403);
            }

            if (!in_array($user->role, ['admin', 'superadmin'])) {
                $gruposIds = $user->grupos()->pluck('grupos_acesso.id');
                $acesso = GrupoEstruturaAcesso::where('estrutura_id', $estrutura->id)
                    ->where('nivel_acesso', 'leitura_escrita')
                    ->whereIn('grupo_id', $gruposIds)
                    ->exists();

                if (!$acesso) {
                    return response()->json(['message' => 'Você não tem permissão para atualizar esta estrutura.'], 403);
                }
            }

            $estrutura->update(array_merge(
                $request->validated(),
                [
                    'updated_by' => Auth::id(),
                    'altura_maxima_federal' => $request->input('altura_maxima_federal'),
                    'altura_maxima_estadual' => $request->input('altura_maxima_estadual'),
                ]
            ));
            return response()->json(['message' => 'Estrutura atualizada com sucesso!', 'data' => $estrutura], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao atualizar estrutura.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Estrutura $estrutura)
    {
        try {
            $user = Auth::user();
            if ($user->role != 'superadmin' && $user->company_id !== $estrutura->company_id) {
                return response()->json(['message' => 'Você não tem permissão para excluir esta estrutura.'], 403);
            }

            if (!in_array($user->role, ['admin', 'superadmin'])) {
                /* $gruposIds = $user->grupos()->pluck('grupos_acesso.id');
                $acesso = GrupoEstruturaAcesso::where('estrutura_id', $estrutura->id)
                    ->where('nivel_acesso', 'leitura_escrita')
                    ->whereIn('grupo_id', $gruposIds)
                    ->exists();

                if (!$acesso) { */
                    return response()->json(['message' => 'Você não tem permissão para excluir esta estrutura.'], 403);
                //}
            }

            $estrutura->delete();
            return response()->json(['message' => 'Estrutura excluída com sucesso!'], 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao excluir estrutura.', 'error' => $e->getMessage()], 500);
        }
    }

    public function auditoria($id)
    {
        $estrutura = Estrutura::findOrFail($id);

        $audits = $estrutura->audits()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($audit) {
                
                $fields = array_unique(array_merge(
                    array_keys((array)$audit->old_values),
                    array_keys((array)$audit->new_values)
                ));
                // Remove o campo updated_by
                $fields = array_filter($fields, fn($f) => $f !== 'updated_by');
                $changes = collect($fields)
                    ->mapWithKeys(function ($field) use ($audit) {
                        return [
                            $field => [
                                'old' => $audit->old_values[$field] ?? null,
                                'new' => $audit->new_values[$field] ?? null,
                            ]
                        ];
                    });
                return [
                    'id' => $audit->id,
                    'user' => $audit->user ? ['name' => $audit->user->name] : null,
                    'created_at' => $audit->created_at,
                    'justificativa' => $audit->justificativa,
                    'changes' => $changes,
                ];
            });

        return response()->json($audits);
    }
}
