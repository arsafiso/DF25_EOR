<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioGrupo extends Model
{
    use HasFactory;

    protected $fillable = ['usuario_id', 'grupo_id'];

    public function grupo()
    {
        return $this->belongsTo(GrupoAcesso::class, 'grupo_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}