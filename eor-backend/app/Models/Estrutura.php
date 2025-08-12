<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Estrutura extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $fillable = [
        'finalidade',
        'projetistas',
        'status',
        'classificacao_federal',
        'classificacao_estadual',
        'classificacao_cda',
        'elevacao_crista',
        'altura_maxima_federal',
        'altura_maxima_estadual',
        'largura_coroamento',
        'area_reservatorio_crista',
        'area_reservatorio_soleira',
        'elevacao_base',
        'altura_maxima_entre_bermas',
        'largura_bermas',
        'tipo_secao',
        'drenagem_interna',
        'instrumentacao',
        'fundacao',
        'analises_estabilidade',
        'area_bacia_contribuicao',
        'area_espelho_dagua',
        'na_maximo_operacional',
        'na_maximo_maximorum',
        'borda_livre',
        'volume_transito_cheias',
        'sistema_extravasor',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'elevacao_crista' => 'float',
        'area_reservatorio_crista' => 'float',
        'area_reservatorio_soleira' => 'float',
        'altura_maxima_entre_bermas' => 'float',
        'area_bacia_contribuicao' => 'float',
        'area_espelho_dagua' => 'float',
        'na_maximo_operacional' => 'float',
        'na_maximo_maximorum' => 'float',
        'borda_livre' => 'float',
        'volume_transito_cheias' => 'float',
    ];

    public function grupos()
    {
        return $this->hasMany(GrupoEstruturaAcesso::class, 'estrutura_id');
    }

    public function scopeComAcesso($query, $userId)
    {
        return $query->whereHas('grupos', function ($query) use ($userId) {
            $query->whereHas('grupo', function ($subQuery) use ($userId) {
                $subQuery->whereHas('usuarios', function ($userQuery) use ($userId) {
                    $userQuery->where('usuario_id', $userId);
                });
            });
        });
    }

    public function comentarios()
    {
        return $this->hasMany(Comentario::class);
    }

    public function transformAudit(array $data): array
    {
        $data['justificativa'] = request()->input('justificativa');
        //$data['justificativa'] = "teste";
        return $data;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
