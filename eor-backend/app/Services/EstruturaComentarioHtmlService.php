<?php

namespace App\Services;

class EstruturaComentarioHtmlService
{
    public static function gerarHtmlComentarioEstrutura($dados)
    {
        $html = "<!DOCTYPE html>
<html lang='pt-br'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <meta http-equiv='X-UA-Compatible' content='ie=edge'>
  <title>Novo Comentário na Estrutura: {$dados['nome_estrutura']}</title>
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
              <p style='margin: 5px 0 0 0; font-size: 14px;'>Notificação de Novo Comentário</p>
            </td>
          </tr>
          <tr>
            <td style='padding: 30px; color: #333333; font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 1.5;'>
              <p style='margin: 0 0 20px 0;'>Olá,</p>
              <p style='margin: 0 0 25px 0;'>Este é um comunicado automático para informar que um novo comentário foi adicionado em uma das estruturas sob sua administração no sistema EOR Digital.</p>
              <p style='margin: 0;'><strong>Estrutura Comentada:</strong> {$dados['nome_estrutura']}</p>
              <p style='margin: 0;'><strong>Comentado por:</strong> {$dados['usuario']}</p>
              <p style='margin: 0 0 25px 0;'><strong>Data e Hora:</strong> {$dados['data_hora']}</p>
              <h3 style='margin: 20px 0 10px 0; font-size: 18px; border-bottom: 2px solid #eeeeee; padding-bottom: 5px;'>Comentário:</h3>
              <p style='margin: 0; padding: 15px; background-color: #f7f7f7; border-left: 4px solid #10b981; font-style: italic;'>
                {$dados['comentario']}
              </p>
              
              <table border='0' cellpadding='0' cellspacing='0' width='100%' style='border-collapse: collapse;'>
                <tr>
                  <td align='center' style='padding: 30px 0 10px 0;'>
                    <table border='0' cellspacing='0' cellpadding='0' width='280' style='width:210pt;border:solid #10b981 1.5pt;background:#10b981;padding:7.5pt 0cm 7.5pt 0cm;'>
                      <tr>
                        <td align='center'>
                          <a href='https://app.eordigital.com/' target='_blank' role='button'
                             style='font-size:14pt;font-family:Arial,sans-serif;color:white;text-decoration:none;'>
                            Visualizar comentário no sistema
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
