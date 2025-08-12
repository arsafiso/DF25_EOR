<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoEstruturaAcesso extends Model
{
    use HasFactory;

    protected $fillable = ['grupo_id', 'estrutura_id', 'nivel_acesso'];

    protected $table = 'grupo_estrutura_acesso';


    public function grupo()
    {
        return $this->belongsTo(GrupoAcesso::class, 'grupo_id');
    }

    public function estrutura()
    {
        return $this->belongsTo(Estrutura::class, 'estrutura_id');
    }
}