<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    protected $fillable = ['estrutura_id', 'user_id', 'data', 'texto'];
    
    public function estrutura() 
    {
        return $this->belongsTo(Estrutura::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
