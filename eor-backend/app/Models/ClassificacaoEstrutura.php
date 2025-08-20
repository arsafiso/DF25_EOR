<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassificacaoEstrutura extends Model
{
    use HasFactory;

    protected $table = 'classificacao_estrutura';

    protected $fillable = [
        'nome',
        'tipo',
        'descricao',
    ];
}
