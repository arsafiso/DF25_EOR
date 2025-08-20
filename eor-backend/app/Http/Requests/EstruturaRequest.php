<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EstruturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:255',
            'finalidade' => 'nullable|string|max:255',
            'projetistas' => 'nullable|string',
            'status' => 'nullable|string|max:255',
            'classificacao_federal' => 'nullable|string|max:255',
            'classificacao_estadual' => 'nullable|string|max:255',
            'classificacao_cda' => 'nullable|string|max:255',
            'elevacao_crista' => 'nullable|numeric',
            'unidade_elevacao_crista' => 'nullable|in:mm,cm,dm,m,dam,hm,km',
            'altura_maxima_federal' => 'nullable|numeric',
            'unidade_altura_maxima_federal' => 'nullable|in:mm,cm,dm,m,dam,hm,km',
            'altura_maxima_estadual' => 'nullable|numeric',
            'unidade_altura_maxima_estadual' => 'nullable|in:mm,cm,dm,m,dam,hm,km',
            'largura_coroamento' => 'nullable|string|max:255',
            'area_reservatorio_crista' => 'nullable|numeric',
            'unidade_area_reservatorio_crista' => 'nullable|in:mm²,cm²,dm²,m²,dam²,hm²,km²',
            'area_reservatorio_soleira' => 'nullable|numeric',
            'unidade_area_reservatorio_soleira' => 'nullable|in:mm²,cm²,dm²,m²,dam²,hm²,km²',
            'elevacao_base' => 'nullable|numeric',
            'unidade_elevacao_base' => 'nullable|in:mm,cm,dm,m,dam,hm,km',
            'altura_maxima_entre_bermas' => 'nullable|numeric',
            'unidade_altura_maxima_entre_bermas' => 'nullable|in:mm,cm,dm,m,dam,hm,km',
            'largura_bermas' => 'nullable|numeric',
            'unidade_largura_bermas' => 'nullable|in:mm,cm,dm,m,dam,hm,km',
            'tipo_secao' => 'nullable|string',
            'drenagem_interna' => 'nullable|string',
            'instrumentacao' => 'nullable|string',
            'fundacao' => 'nullable|string',
            'analises_estabilidade' => 'nullable|string',
            'area_bacia_contribuicao' => 'nullable|numeric',
            'unidade_area_bacia_contribuicao' => 'nullable|in:mm²,cm²,dm²,m²,dam²,hm²,km²',
            'area_espelho_dagua' => 'nullable|numeric',
            'unidade_area_espelho_dagua' => 'nullable|in:mm²,cm²,dm²,m²,dam²,hm²,km²',
            'na_maximo_operacional' => 'nullable|numeric',
            'unidade_na_maximo_operacional' => 'nullable|in:mm,cm,dm,m,dam,hm,km',
            'na_maximo_maximorum' => 'nullable|numeric',
            'unidade_na_maximo_maximorum' => 'nullable|in:mm,cm,dm,m,dam,hm,km',
            'borda_livre' => 'nullable|numeric',
            'unidade_borda_livre' => 'nullable|in:mm,cm,dm,m,dam,hm,km',
            'volume_transito_cheias' => 'nullable|numeric',
            'unidade_volume_transito_cheias' => 'nullable|in:mm³,cm³,dm³,m³,dam³,hm³,km³',
            'sistema_extravasor' => 'nullable|string',
        ];
    }
}
