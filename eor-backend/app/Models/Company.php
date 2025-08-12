<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['nome', 'descricao'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function estruturas()
    {
        return $this->hasMany(Estrutura::class);
    }

    public function gruposAcesso()
    {
        return $this->hasMany(GrupoAcesso::class);
    }
}