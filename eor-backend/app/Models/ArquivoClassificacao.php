<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArquivoClassificacao extends Model
{
    protected $table = 'arquivos_classificacao';

    protected $fillable = [
        'estrutura_id',
        'nome_arquivo',
        'tipo',
        'tamanho',
    ];

    public function estrutura()
    {
        return $this->belongsTo(Estrutura::class, 'estrutura_id');
    }
}