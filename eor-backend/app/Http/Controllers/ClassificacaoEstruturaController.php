<?php

namespace App\Http\Controllers;

use App\Models\ClassificacaoEstrutura;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClassificacaoEstruturaController extends Controller
{
    public function index()
    {
        return response()->json(ClassificacaoEstrutura::all());
    }

    public function show($id)
    {
        $item = ClassificacaoEstrutura::find($id);
        if (!$item) {
            return response()->json(['error' => 'Not Found'], 404);
        }
        return response()->json($item);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:federal,estadual',
            'descricao' => 'nullable|string',
        ]);
        $item = ClassificacaoEstrutura::create($validated);
        return response()->json($item, 201);
    }

    public function update(Request $request, $id)
    {
        $item = ClassificacaoEstrutura::find($id);
        if (!$item) {
            return response()->json(['error' => 'Not Found'], 404);
        }
        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'tipo' => 'sometimes|required|in:federal,estadual',
            'descricao' => 'nullable|string',
        ]);
        $item->update($validated);
        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = ClassificacaoEstrutura::find($id);
        if (!$item) {
            return response()->json(['error' => 'Not Found'], 404);
        }
        $item->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
