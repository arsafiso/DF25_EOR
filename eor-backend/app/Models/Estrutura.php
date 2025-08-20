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
        'nome',
        'finalidade',
        'projetistas',
        'status',
        'classificacao_federal',
        'classificacao_estadual',
        'classificacao_cda',
        'elevacao_crista',
        'unidade_elevacao_crista',
        'altura_maxima_federal',
        'unidade_altura_maxima_federal',
        'altura_maxima_estadual',
        'unidade_altura_maxima_estadual',
        'largura_coroamento',
        'area_reservatorio_crista',
        'unidade_area_reservatorio_crista',
        'area_reservatorio_soleira',
        'unidade_area_reservatorio_soleira',
        'elevacao_base',
        'unidade_elevacao_base',
        'altura_maxima_entre_bermas',
        'unidade_altura_maxima_entre_bermas',
        'largura_bermas',
        'unidade_largura_bermas',
        'tipo_secao',
        'drenagem_interna',
        'instrumentacao',
        'fundacao',
        'analises_estabilidade',
        'area_bacia_contribuicao',
        'unidade_area_bacia_contribuicao',
        'area_espelho_dagua',
        'unidade_area_espelho_dagua',
        'na_maximo_operacional',
        'unidade_na_maximo_operacional',
        'na_maximo_maximorum',
        'unidade_na_maximo_maximorum',
        'borda_livre',
        'unidade_borda_livre',
        'volume_transito_cheias',
        'unidade_volume_transito_cheias',
        'sistema_extravasor',
        'created_by',
        'updated_by',
        'company_id',
    ];

    protected $casts = [
        'elevacao_crista' => 'float',
        'elevacao_base' => 'float',
        'altura_maxima_federal' => 'float',
        'altura_maxima_estadual' => 'float',
        'area_reservatorio_crista' => 'float',
        'area_reservatorio_soleira' => 'float',
        'altura_maxima_entre_bermas' => 'float',
        'largura_bermas' => 'float',
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

        if ($this->justificativa) {
            $data['justificativa'] = $this->justificativa;
        } else {
            $data['justificativa'] = request()->input('justificativa'); //recebe justificativa (upload arquivos de classificação)
        }
        return $data;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
