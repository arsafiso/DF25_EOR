<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\ArquivoEstrutura;
use App\Models\Estrutura;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ArquivoEstruturaController extends Controller
{
    private string $caminhoBase = 'arquivos_estrutura';

    // Listar arquivos de uma estrutura por categoria
    public function index(Request $request, $estruturaId)
    {
        $categoria = $request->query('categoria');
        $query = ArquivoEstrutura::where('estrutura_id', $estruturaId);
        if ($categoria) {
            $query->where('categoria', $categoria);
        }
        $arquivos = $query->orderByDesc('uploaded_at')->get();
        return response()->json($arquivos);
    }

    // Upload de arquivos
    public function store(Request $request, $estruturaId)
    {
        try {
            $user = Auth::user();
            $estrutura = Estrutura::findOrFail($estruturaId);

            $categoria = $request->input('categoria', 'classificacao');
            $manterHistorico = $request->input('manter_historico', false);

            // Validação de permissão
            $temPermissao = false;
            if ($user) {
                if ($user->role === 'superadmin' || ($user->role === 'admin' && $user->company_id === $estrutura->company_id)) {
                    $temPermissao = true;
                } else {
                    $gruposIds = $user->grupos ? $user->grupos->pluck('id') : [];
                    $acesso = \App\Models\GrupoEstruturaAcesso::where('estrutura_id', $estruturaId)
                        ->where('nivel_acesso', 'leitura_escrita')
                        ->whereIn('grupo_id', $gruposIds)
                        ->exists();
                    if ($acesso) {
                        $temPermissao = true;
                    }
                }
            }

            if (!$temPermissao) {
                return response()->json(['error' => 'Sem permissão para enviar arquivos.'], 403);
            }

            $arquivos = [];
            foreach ($request->file('files') as $file) {
                // Cria registro no banco primeiro
                $arquivo = ArquivoEstrutura::create([
                    'estrutura_id' => $estruturaId,
                    'nome_arquivo' => $file->getClientOriginalName(),
                    'tipo' => $file->getClientMimeType(),
                    'categoria' => $categoria,
                    'tamanho' => $file->getSize(),
                    'manter_historico' => $manterHistorico,
                    'uploaded_by' => $user->id,
                    'uploaded_at' => now(),
                ]);

                // Salva arquivo no disco
                $path = "{$this->caminhoBase}/{$estruturaId}/{$arquivo->id}";
                $disk = config('filesystems.default', 'public');
                $file->storeAs($path, $arquivo->nome_arquivo, $disk);

                $arquivos[] = $arquivo;

                Log::info("Arquivo {$arquivo->nome_arquivo} enviado para {$path}");
            }

            return response()->json($arquivos, 201);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar arquivo: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao enviar arquivo.'], 500);
        }
    }

    // Download de arquivo
    public function download($estruturaId, $arquivoId)
    {
        $arquivo = ArquivoEstrutura::where('estrutura_id', $estruturaId)->findOrFail($arquivoId);
        $disk = config('filesystems.default', 'public');
        $path = "{$this->caminhoBase}/{$estruturaId}/{$arquivo->id}/{$arquivo->nome_arquivo}";

        if (!Storage::disk($disk)->exists($path)) {
            return response()->json(['error' => 'Arquivo não encontrado'], 404);
        }

        if ($disk === 's3') {
            $stream = Storage::disk('s3')->readStream($path);
            return response()->streamDownload(function () use ($stream) {
                fpassthru($stream);
            }, $arquivo->nome_arquivo, [
                'Content-Type' => $arquivo->tipo,
                'Content-Length' => $arquivo->tamanho,
                'Content-Disposition' => 'attachment; filename="' . $arquivo->nome_arquivo . '"',
            ]);
        }

        return response()->download(Storage::disk($disk)->path($path), $arquivo->nome_arquivo);
    }

    // Excluir arquivo
    public function destroy($estruturaId, $arquivoId)
    {
        $arquivo = ArquivoEstrutura::where('estrutura_id', $estruturaId)->findOrFail($arquivoId);
        $disk = config('filesystems.default', 'public');
        $path = "{$this->caminhoBase}/{$estruturaId}/{$arquivo->id}/{$arquivo->nome_arquivo}";

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }

        $arquivo->delete();

        return response()->json(['success' => true]);
    }

    // Listar histórico de arquivos
    public function historico(Request $request, $estruturaId)
    {
        $categoria = $request->query('categoria');
        $query = ArquivoEstrutura::where('estrutura_id', $estruturaId)
            ->where('manter_historico', true);

        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        $arquivos = $query->orderByDesc('uploaded_at')->get();
        return response()->json($arquivos);
    }

    // Marcar arquivo como histórico
    public function marcarHistorico($estruturaId, $arquivoId)
    {
        $arquivo = ArquivoEstrutura::where('estrutura_id', $estruturaId)->findOrFail($arquivoId);
        $arquivo->manter_historico = true;
        $arquivo->save();

        return response()->json($arquivo);
    }
}
