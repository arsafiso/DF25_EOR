import * as XLSX from 'xlsx';
import { exportStructureWord } from '@/utils/wordExport';

/**
 * Exporta a estrutura em dois arquivos: Excel e Word.
 * @param {Object} structure - Objeto da estrutura (valores dos campos)
 */
export function exportarEstrutura(structure) {
    const abas = {
        informacoes_basicas: [
            'finalidade', 'projetistas', 'status', 'classificacao_federal', 'classificacao_estadual', 'classificacao_cda'
        ],
        medidas_tecnicas: [
            'elevacao_crista', 'altura_maxima_federal', 'altura_maxima_estadual', 'largura_coroamento',
            'area_reservatorio_crista', 'area_reservatorio_soleira', 'elevacao_base', 'altura_maxima_entre_bermas', 'largura_bermas'
        ],
        detalhes_estruturais: [
            'tipo_secao', 'drenagem_interna', 'instrumentacao', 'fundacao', 'analises_estabilidade'
        ],
        parametros_hidraulicos: [
            'area_bacia_contribuicao', 'area_espelho_dagua', 'na_maximo_operacional', 'na_maximo_maximorum',
            'borda_livre', 'volume_transito_cheias', 'sistema_extravasor'
        ]
    };

    // Lista de campos que possuem unidade
    const camposComUnidade = [
        'elevacao_crista',
        'elevacao_base',
        'altura_maxima_federal',
        'altura_maxima_estadual',
        'area_reservatorio_crista',
        'area_reservatorio_soleira',
        'altura_maxima_entre_bermas',
        'largura_bermas',
        'area_bacia_contribuicao',
        'area_espelho_dagua',
        'na_maximo_operacional',
        'na_maximo_maximorum',
        'borda_livre',
        'volume_transito_cheias',
    ];
    const sheets = {};
    for (const [aba, campos] of Object.entries(abas)) {
        const row = {};
        campos.forEach(campo => {
            row[campo] = structure[campo];
            if (camposComUnidade.includes(campo)) {
                // nome da coluna de unidade
                const unidadeCampo = 'unidade_' + campo;
                row[unidadeCampo] = structure[unidadeCampo] || '';
            }
        });
        sheets[aba] = XLSX.utils.json_to_sheet([row]);
    }

    const wb = XLSX.utils.book_new();
    for (const [aba, sheet] of Object.entries(sheets)) {
        XLSX.utils.book_append_sheet(wb, sheet, aba);
    }

    const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
    const blob = new Blob([wbout], { type: 'application/octet-stream' });
    const url = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = url;
    a.download = `Estrutura_${structure.finalidade || 'null'}.xlsx`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    // Define o layout completo da tabela
    const layoutTabela = [
        { type: 'header', label: 'Dados Gerais' },
        { type: 'data', label: 'Finalidade', key: 'finalidade', rastreabilidade: { text: '', rowSpan: 2 } },
        { type: 'data', label: 'Projetista', key: 'projetistas' },
        { type: 'data', label: 'Status', key: 'status', rastreabilidade: { text: '' } },

        { type: 'header', label: 'Classificação da Estrutura' },
        { type: 'data', label: 'Classificação Federal', key: 'classificacao_federal', rastreabilidade: { text: '', rowSpan: 4 } },
        { type: 'data', label: 'Classificação Estadual', key: 'classificacao_estadual' },
        { type: 'data', label: 'Classificação CDA', key: 'classificacao_cda' },
        { type: 'data', label: 'Classificação GISTM', key: 'classificacao_gistm' },

        { type: 'header', label: 'Dados Geométricos' },
        { type: 'data', label: 'Elevação do coroamento', key: 'elevacao_crista', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Altura máxima da barragem \n(Lei Federal 14.066/2020)', key: 'altura_maxima_federal', rastreabilidade: { text: '', rowSpan: 2 } },
        { type: 'data', label: 'Altura máxima da barragem \n(Lei Estadual 23.291/2019)', key: 'altura_maxima_estadual' },
        { type: 'data', label: 'Largura/comprimento do coroamento', key: 'largura_coroamento', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Área do reservatório na El. do coroamento', key: 'area_reservatorio_crista', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Área do reservatório até a soleira', key: 'area_reservatorio_soleira', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Elevação da base', key: 'elevacao_base', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Altura máxima entre bermas', key: 'altura_maxima_entre_bermas', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Largura média das bermas', key: 'largura_bermas', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Inclinação do talude de montante', key: 'inclinacao_talude_montante', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Inclinação dos taludes de jusante', key: 'inclinacao_taludes_jusante', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Volume do maciço \n(Elevação do coroamento)', key: 'volume_macico', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Volume útil do reservatório \n(até a crista) (1)', key: 'volume_util_crista', rastreabilidade: { text: '', rowSpan: 2 } },
        { type: 'data', label: 'Volume útil do reservatório \n(até a soleira do extravasor) \n(1)', key: 'volume_util_soleira' },
        { type: 'data', label: 'Volume total do reservatório \n(até o coroamento)', key: 'volume_total_coroamento', rastreabilidade: { text: '', rowSpan: 2 } },
        { type: 'data', label: 'Volume total do reservatório \n(até a soleira do extravasor)', key: 'volume_total_soleira' },
        { type: 'data', label: 'Volume de água', key: 'volume_agua', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Volume de rejeito disposto', key: 'volume_rejeito', rastreabilidade: { text: '' } },

        { type: 'header', label: 'Aspectos Geológico-Geotécnicos' },
        { type: 'data', label: 'Tipo de seção', key: 'tipo_secao', rastreabilidade: { text: '', rowSpan: 2 } },
        { type: 'data', label: 'Drenagem interna', key: 'drenagem_interna' },
        { type: 'data', label: 'Instrumentação', key: 'instrumentacao', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Fundação', key: 'fundacao', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Análises de Estabilidade e Percolação', key: 'analises_estabilidade', rastreabilidade: { text: '' } },

        { type: 'header', label: 'Aspectos Hidráulicos-Hidrológicos' },
        { type: 'data', label: 'Área da Bacia de contribuição', key: 'area_bacia_contribuicao', rastreabilidade: { text: '' } },
        { type: 'data', label: "Área do espelho d’água El. 1.121,00 m (crista)", key: 'area_espelho_dagua', rastreabilidade: { text: '' } },
        { type: 'data', label: 'Vazão de projeto (PMP)', key: 'vazao_projeto_pmp', rastreabilidade: { text: '', rowSpan: 4 } },
        { type: 'data', label: 'N.A. Máximo Operacional', key: 'na_maximo_operacional' },
        { type: 'data', label: 'N.A.  Maximum   Maximorum  (PMP)', key: 'na_maximo_maximorum' },
        { type: 'data', label: 'Borda Livre (PMP)', key: 'borda_livre' },
        { type: 'data', label: 'Volume de amortecimento \n(disponível para trânsito de cheias)', key: 'volume_transito_cheias', rastreabilidade: { text: '' } },
        
        { type: 'header', label: 'Estruturas Vertentes' },
        { type: 'data', label: 'Vertedouro de Concreto Armado', key: 'sistema_extravasor', rastreabilidade: { text: '' } },
    ];

    exportStructureWord(structure, layoutTabela);
}