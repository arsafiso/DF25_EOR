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
