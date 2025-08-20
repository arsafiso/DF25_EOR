<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comentario;
use Illuminate\Support\Facades\Auth;

class ComentarioController extends Controller
{
    public function store(Request $request, $estruturaId)
    {
        $request->validate([
            'texto' => 'required|string',
        ]);
        $comentario = \App\Models\Comentario::create([
            'estrutura_id' => $estruturaId,
            'user_id' => Auth::Id(),
            'data' => now(),
            'texto' => $request->texto,
        ]);

        $comentario->user_nome = $comentario->user ? $comentario->user->name : null;
        
        $envio_email = true;// true ativa envio, false desativa envio de e-mail

        if ($envio_email) {
                // Geração do HTML de comentário
                $estrutura = $comentario->estrutura;
                $dadosHtml = [
                    'nome_estrutura' => $estrutura ? ($estrutura->finalidade ?? $estrutura->id) : $estruturaId,
                    'usuario' => $comentario->user_nome ?? 'Desconhecido',
                    'data_hora' => now('America/Sao_Paulo')->format('d/m/Y') . ' às ' . now('America/Sao_Paulo')->format('H:i'),
                    'comentario' => $comentario->texto,
                ];
                $html = \App\Services\EstruturaComentarioHtmlService::gerarHtmlComentarioEstrutura($dadosHtml);
                //para teste local
                //$caminho = 'C:/OneDrive - BRIDGE/Área de Trabalho/comentario_estrutura_' . $estruturaId . '_' . time() . '.html';
                //file_put_contents($caminho, $html);//para teste local
            if ($estrutura) {
                \App\Jobs\SendEstruturaMailJob::dispatch($estrutura, $html, 'Novo Comentário na Estrutura: ' . ($estrutura->finalidade ?? $estrutura->id));
            }
        }

        return response()->json($comentario, 201);
    }

    public function index($estruturaId)
    {
        $comentarios = \App\Models\Comentario::where('estrutura_id', $estruturaId)
            ->orderBy('data', 'desc')
            ->get()
            ->map(function ($comentario) {
                return [
                    'id' => $comentario->id,
                    'estrutura_id' => $comentario->estrutura_id,
                    'user_id' => $comentario->user_id,
                    'data' => $comentario->data,
                    'texto' => $comentario->texto,
                    'user_nome' => $comentario->user ? $comentario->user->name : null,
                ];
            });

        return response()->json($comentarios);
    }
}
