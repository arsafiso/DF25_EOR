<?php
namespace App\Jobs;

use App\Models\Estrutura;
use App\Services\EstruturaMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEstruturaMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $estrutura;
    public $html;
    public $assunto;

    public function __construct($estrutura, $html, $assunto)
    {
        $this->estrutura = $estrutura;
        $this->html = $html;
        $this->assunto = $assunto;
    }

    public function handle()
    {
        EstruturaMailService::enviarHtmlParaAdmins($this->estrutura, $this->html, $this->assunto);
    }
}
