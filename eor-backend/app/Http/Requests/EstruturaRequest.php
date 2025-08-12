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
            'finalidade' => 'nullable|string|max:255',
            'projetistas' => 'nullable|string',
            'status' => 'nullable|string|max:255',
            'classificacao_federal' => 'nullable|string|max:255',
            'classificacao_estadual' => 'nullable|string|max:255',
            'classificacao_cda' => 'nullable|string|max:255',
            'elevacao_crista' => 'nullable|numeric',
            'altura_maxima_federal' => 'nullable|string|max:255',
            'altura_maxima_estadual' => 'nullable|string|max:255',
            'largura_coroamento' => 'nullable|string|max:255',
            'area_reservatorio_crista' => 'nullable|numeric',
            'area_reservatorio_soleira' => 'nullable|numeric',
            'elevacao_base' => 'nullable|string|max:255',
            'altura_maxima_entre_bermas' => 'nullable|numeric',
            'largura_bermas' => 'nullable|string|max:255',
            'tipo_secao' => 'nullable|string',
            'drenagem_interna' => 'nullable|string',
            'instrumentacao' => 'nullable|string',
            'fundacao' => 'nullable|string',
            'analises_estabilidade' => 'nullable|string',
            'area_bacia_contribuicao' => 'nullable|numeric',
            'area_espelho_dagua' => 'nullable|numeric',
            'na_maximo_operacional' => 'nullable|numeric',
            'na_maximo_maximorum' => 'nullable|numeric',
            'borda_livre' => 'nullable|numeric',
            'volume_transito_cheias' => 'nullable|numeric',
            'sistema_extravasor' => 'nullable|string',
        ];
    }
}
