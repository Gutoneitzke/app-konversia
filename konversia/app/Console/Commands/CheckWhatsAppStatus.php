<?php

namespace App\Console\Commands;

use App\Jobs\CheckWhatsAppConnectionsStatus;
use Illuminate\Console\Command;

class CheckWhatsAppStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:check-status {--sync : Executar imediatamente em vez de enfileirar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar o status das conexões WhatsApp e sincronizar com o banco de dados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando status das conexões WhatsApp...');

        if ($this->option('sync')) {
            // Executar imediatamente
            $job = new CheckWhatsAppConnectionsStatus();
            $job->handle(app(\App\Services\WhatsAppService::class));
            $this->info('Verificação concluída!');
        } else {
            // Enfileirar o job
            CheckWhatsAppConnectionsStatus::dispatch();
            $this->info('Job de verificação enfileirado!');
        }

        return self::SUCCESS;
    }
}
