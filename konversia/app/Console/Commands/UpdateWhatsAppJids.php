<?php

namespace App\Console\Commands;

use App\Jobs\ProcessWhatsAppWebhookEvent;
use Illuminate\Console\Command;

class UpdateWhatsAppJids extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:update-jids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualizar JIDs do WhatsApp para formato consistente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando atualização de JIDs do WhatsApp...');

        ProcessWhatsAppWebhookEvent::updateExistingJids();

        $this->info('Atualização concluída com sucesso!');
    }
}