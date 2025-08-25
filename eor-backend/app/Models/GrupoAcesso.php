<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoAcesso extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'descricao', 'company_id', 'tipo'];

    protected $table = 'grupos_acesso';

    public function estruturas()
    {
        return $this->hasMany(GrupoEstruturaAcesso::class, 'grupo_id');
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'usuario_grupo', 'grupo_id', 'usuario_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}