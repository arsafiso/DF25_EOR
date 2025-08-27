<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArquivoEstrutura extends Model
{
    protected $table = 'arquivos_estrutura';

    protected $fillable = [
        'estrutura_id',
        'nome_arquivo',
        'tipo',
        'categoria',
        'tamanho',
        'manter_historico',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'manter_historico' => 'boolean',
        'uploaded_at' => 'datetime',
        'tamanho' => 'integer',
    ];

    public function estrutura()
    {
        return $this->belongsTo(Estrutura::class, 'estrutura_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
