<?php

namespace App\Services;

class EstruturaHtmlService
{
    public static function gerarHtmlAlteracaoEstrutura($dados)
    {
    // Mapeamento dos nomes dos campos para exibição amigável
    $auditFieldLabels = [
      'finalidade' => 'Finalidade',
      'projetistas' => 'Projetistas',
      'status' => 'Status',
      'classificacao_federal' => 'Classificação Federal',
      'classificacao_estadual' => 'Classificação Estadual',
      'classificacao_cda' => 'Classificação CDA',
      'elevacao_crista' => 'Elevação da Crista',
      'unidade_elevacao_crista' => 'Unidade da Elevação da Crista',
      'altura_maxima_federal' => 'Altura máxima da barragem (Lei Federal 14.066/2020)',
      'unidade_altura_maxima_federal' => 'Unidade da Altura Máxima Federal',
      'altura_maxima_estadual' => 'Altura máxima da barragem (Lei Estadual 23.291/2019)',
      'unidade_altura_maxima_estadual' => 'Unidade da Altura Máxima Estadual',
      'largura_coroamento' => 'Largura/comprimento do coroamento',
      'area_reservatorio_crista' => 'Área do reservatório (até a crista)',
      'unidade_area_reservatorio_crista' => 'Unidade da Área do Reservatório (Crista)',
      'area_reservatorio_soleira' => 'Área do reservatório (até a soleira)',
      'unidade_area_reservatorio_soleira' => 'Unidade da Área do Reservatório (Soleira)',
      'elevacao_base' => 'Elevação da Base',
      'unidade_elevacao_base' => 'Unidade da Elevação da Base',
      'altura_maxima_entre_bermas' => 'Altura Máxima entre Bermas',
      'unidade_altura_maxima_entre_bermas' => 'Unidade da Altura Máxima entre Bermas',
      'largura_bermas' => 'Largura das bermas',
      'unidade_largura_bermas' => 'Unidade da Largura das Bermas',
      'tipo_secao' => 'Tipo de seção',
      'drenagem_interna' => 'Drenagem interna',
      'instrumentacao' => 'Instrumentação',
      'fundacao' => 'Fundação',
      'analises_estabilidade' => 'Análises de Estabilidade e Percolação',
      'area_bacia_contribuicao' => 'Área da Bacia de Contribuição',
      'unidade_area_bacia_contribuicao' => 'Unidade da Área da Bacia de Contribuição',
      'area_espelho_dagua' => "Área do Espelho d'água",
      'unidade_area_espelho_dagua' => "Unidade da Área do Espelho d'água",
      'na_maximo_operacional' => 'NA Máximo Operacional',
      'unidade_na_maximo_operacional' => 'Unidade do NA Máximo Operacional',
      'na_maximo_maximorum' => 'NA Máximo Maximorum',
      'unidade_na_maximo_maximorum' => 'Unidade do NA Máximo Maximorum',
      'borda_livre' => 'Borda Livre (NA Máximo Maximorum)',
      'unidade_borda_livre' => 'Unidade da Borda Livre',
      'volume_transito_cheias' => 'Volume disponível para trânsito de cheias',
      'unidade_volume_transito_cheias' => 'Unidade do Volume para Trânsito de Cheias',
      'sistema_extravasor' => 'Sistema Extravasor',
      'file_upload' => 'Upload de Arquivo',
    ];
    $campos = '';
    $nivelMap = [
      'low' => 'Baixa',
      'medium' => 'Média',
      'high' => 'Alta',
    ];
    foreach ($dados['alteracoes'] as $alteracao) {
      $label = $auditFieldLabels[$alteracao['campo']] ?? $alteracao['campo'];
      $valor_anterior = $nivelMap[$alteracao['valor_anterior']] ?? $alteracao['valor_anterior'];
      $novo_valor = $nivelMap[$alteracao['novo_valor']] ?? $alteracao['novo_valor'];
      $campos .= "<tr>
        <td style='padding: 12px; border-bottom: 1px solid #eeeeee;'>{$label}</td>
        <td style='padding: 12px; border-bottom: 1px solid #eeeeee;'>{$valor_anterior}</td>
        <td style='padding: 12px; border-bottom: 1px solid #eeeeee;'>{$novo_valor}</td>
      </tr>";
    }
        $html = "<!DOCTYPE html>
<html lang='pt-br'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <meta http-equiv='X-UA-Compatible' content='ie=edge'>
  <title>Alteração na Estrutura: {$dados['nome_estrutura']}</title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; }
  </style>
</head>
<body style='margin: 0; padding: 0; background-color: #f4f4f4;'>
  <table border='0' cellpadding='0' cellspacing='0' width='100%'>
    <tr>
      <td style='padding: 20px 0;'>
        <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; border-collapse: collapse; background-color: #ffffff; border: 1px solid #dddddd;'>
          <tr>
            <td align='left' style='padding: 20px 30px; background-color: #10b981; color: #ffffff;'>
              <h1 style='margin: 0; font-size: 24px;'>EOR Digital</h1>
              <p style='margin: 5px 0 0 0; font-size: 14px;'>Notificação de Alteração de Estrutura</p>
            </td>
          </tr>
          <tr>
            <td style='padding: 30px; color: #333333; font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 1.5;'>
              <p style='margin: 0 0 20px 0;'>Olá,</p>
              <p style='margin: 0 0 25px 0;'>Este é um comunicado automático para informar que uma alteração foi realizada em uma das estruturas sob sua administração no sistema EOR Digital.</p>
              <p style='margin: 0;'><strong>Estrutura Modificada:</strong> {$dados['nome_estrutura']}</p>
              <p style='margin: 0;'><strong>Alterado por:</strong> {$dados['usuario']}</p>
              <p style='margin: 0 0 25px 0;'><strong>Data e Hora:</strong> {$dados['data_hora']}</p>
              <h3 style='margin: 0 0 10px 0; font-size: 18px; border-bottom: 2px solid #eeeeee; padding-bottom: 5px;'>Alterações Realizadas:</h3>
              <table border='0' cellpadding='0' cellspacing='0' width='100%' style='border-collapse: collapse; border: 1px solid #dddddd;'>
                <thead>
                  <tr>
                    <th align='left' style='background-color: #f7f7f7; padding: 12px; font-size: 14px; border-bottom: 1px solid #dddddd;'>Campo Modificado</th>
                    <th align='left' style='background-color: #f7f7f7; padding: 12px; font-size: 14px; border-bottom: 1px solid #dddddd;'>Valor Anterior</th>
                    <th align='left' style='background-color: #f7f7f7; padding: 12px; font-size: 14px; border-bottom: 1px solid #dddddd;'>Novo Valor</th>
                  </tr>
                </thead>
                <tbody>
                  $campos
                </tbody>
              </table>
              <h3 style='margin: 30px 0 10px 0; font-size: 18px; border-bottom: 2px solid #eeeeee; padding-bottom: 5px;'>Justificativa da Alteração:</h3>
              <p style='margin: 0; padding: 15px; background-color: #f7f7f7; border-left: 4px solid #10b981; font-style: italic;'>
                {$dados['justificativa']}
              </p>
              <table border='0' cellpadding='0' cellspacing='0' width='100%' style='border-collapse: collapse;'>
                <tr>
                  <td align='center' style='padding: 30px 0 10px 0;'>
                    <table border='0' cellspacing='0' cellpadding='0' width='280' style='width:210pt;border:solid #10b981 1.5pt;background:#10b981;padding:7.5pt 0cm 7.5pt 0cm;'>
                      <tr>
                        <td align='center'>
                          <a href='https://app.eordigital.com/' target='_blank' role='button'
                             style='font-size:14pt;font-family:Arial,sans-serif;color:white;text-decoration:none;'>
                            Verificar alteração no sistema
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align='center' style='padding: 20px; background-color: #f4f4f4; color: #888888; font-size: 12px; font-family: Arial, Helvetica, sans-serif;'>
              <p style='margin: 0;'>Esta é uma mensagem automática. Por favor, não responda a este e-mail.</p>
            </td>
          </tr>
        </table>
        </td>
    </tr>
  </table>
</body>
</html>";
        return $html;
    }
}
