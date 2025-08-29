<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\ArquivoClassificacao;
use App\Models\Estrutura;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ArquivoClassificacaoController extends Controller
{
    // Caminho base para os arquivos de classificação
    private string $caminhoBase = 'arquivos_classificacao';

    // Listar arquivos de uma estrutura
    public function index($estruturaId)
    {
        $arquivos = ArquivoClassificacao::where('estrutura_id', $estruturaId)->get();
        return response()->json($arquivos);
    }

    // Upload de arquivos
    public function store(Request $request, $estruturaId)
    {
        try {
            $user = Auth::user();
            $estrutura = \App\Models\Estrutura::findOrFail($estruturaId);

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
                $arquivo = ArquivoClassificacao::create([
                    'estrutura_id' => $estruturaId,
                    'nome_arquivo' => $file->getClientOriginalName(),
                    'tipo' => $file->getClientMimeType(),
                    'tamanho' => $file->getSize(),
                ]);

                $path = "{$this->caminhoBase}/{$estruturaId}/{$arquivo->id}";
                $disk = config('filesystems.default', 'public');
                $filePath = $file->storeAs("{$this->caminhoBase}/{$estruturaId}/{$arquivo->id}", $arquivo->nome_arquivo, $disk);
                Log::info($filePath);
                $arquivos[] = $arquivo;

                // Audit - upload de arquivos de classificação
                $estrutura = $arquivo->estrutura;
                if ($estrutura && method_exists($estrutura, 'audits')) {
                    $estrutura->isCustomEvent = true;
                    $estrutura->auditEvent = 'file_upload';
                    $estrutura->auditCustomOld = [];
                    $estrutura->auditCustomNew = ['file_upload' => $arquivo->nome_arquivo];
                    $estrutura->setAttribute('justificativa', 'Carregamento de arquivo de classificação.');
                    \OwenIt\Auditing\Facades\Auditor::execute($estrutura);
                    $estrutura->isCustomEvent = false;
                        // Geração do HTML após upload
                        $alteracoes = [
                            [
                                'campo' => 'file_upload',
                                'valor_anterior' => '',
                                'novo_valor' => $arquivo->nome_arquivo,
                            ]
                        ];
                        $dadosHtml = [
                            'nome_estrutura' => $estrutura->finalidade ?? $estrutura->id,
                            'usuario' => $user->name ?? 'Desconhecido',
                            'data_hora' => now('America/Sao_Paulo')->format('d/m/Y') . ' às ' . now('America/Sao_Paulo')->format('H:i'),
                            'alteracoes' => $alteracoes,
                            'justificativa' => 'Carregamento de arquivo de classificação.',
                        ];
                        $html = \App\Services\EstruturaHtmlService::gerarHtmlAlteracaoEstrutura($dadosHtml);
                        $caminho = 'C:/OneDrive - BRIDGE/Área de Trabalho/alteracao_estrutura_' . $estrutura->id . '_' . time() . '.html';
                        file_put_contents($caminho, $html);
                    \App\Jobs\SendEstruturaMailJob::dispatch($estrutura, $html, 'Upload de Arquivo na Estrutura: ' . ($estrutura->finalidade ?? $estrutura->id));
                }
            }

            return response()->json($arquivos, 201);
        } catch (\Exception $e) {
            Log::error('Erro ao enviar arquivo de classificação: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao enviar arquivo.'], 500);
        }
    }

    // Download de arquivo
    public function download($estruturaId, $arquivoId)
    {
        $arquivo = ArquivoClassificacao::where('estrutura_id', $estruturaId)->findOrFail($arquivoId);
        $disk = config('filesystems.default', 'public');
        $path = "{$this->caminhoBase}/{$estruturaId}/{$arquivo->id}/{$arquivo->nome_arquivo}";

        if (Storage::disk($disk)->exists($path)) {
            if ($disk === 's3') {
                $stream = Storage::disk('s3')->readStream($path);
                if ($stream === false) {
                    return response()->json(['error' => 'Arquivo não encontrado no S3'], 404);
                }
                return response()->streamDownload(function () use ($stream) {
                    fpassthru($stream);
                }, $arquivo->nome_arquivo, [
                    'Content-Type' => $arquivo->tipo,
                    'Content-Length' => $arquivo->tamanho,
                    'Content-Disposition' => 'attachment; filename="' . $arquivo->nome_arquivo . '"',
                ]);
            } else {
                // Para disco local
                $absolutePath = Storage::disk($disk)->path($path);
                if (!file_exists($absolutePath)) {
                    return response()->json(['error' => 'Arquivo não encontrado no disco'], 404);
                }
                return response()->download($absolutePath, $arquivo->nome_arquivo);
            }
        }

        return response()->json(['error' => 'Arquivo não encontrado'], 404);
    }

    // Excluir arquivo
    public function destroy($estruturaId, $arquivoId){
    try {
        $arquivo = ArquivoEstrutura::where('estrutura_id', $estruturaId)->findOrFail($arquivoId);
        $path = "{$this->caminhoBase}/{$estruturaId}/{$arquivo->id}_{$arquivo->nome_arquivo}";

        if (\Storage::exists($path)) {
            \Storage::delete($path);
        }

        $arquivo->delete();

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        \Log::error('Erro ao deletar arquivo: ' . $e->getMessage(), ['estrutura_id'=>$estruturaId,'arquivo_id'=>$arquivoId]);
        return response()->json(['success' => false, 'message' => 'Erro ao deletar arquivo'], 500);
    }
    }


}