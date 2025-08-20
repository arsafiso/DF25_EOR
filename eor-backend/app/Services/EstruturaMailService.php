<?php
namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class EstruturaMailService
{
    public static function enviarHtmlParaAdmins($estrutura, $html, $assunto)
    {
        $company = $estrutura->company;
        if (!$company) return;
        $admins = $company->users()->where('role', 'admin')->get();
        if ($admins->isEmpty()) return;
        $emails = $admins->pluck('email')->toArray();
        try {
            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($emails, $assunto, $html) {
                $message->to($emails)
                    ->subject($assunto)
                    ->from('contato@ahubtech.com', 'EOR Digital')
                    ->html($html);
            });
        } catch (\Exception $e) {
            Log::error('Erro ao enviar e-mail de alteração/comentário: ' . $e->getMessage());
        }
    }
}
