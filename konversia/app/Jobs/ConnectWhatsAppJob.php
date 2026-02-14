<?php

namespace App\Jobs;

use App\Models\WhatsAppNumber;
use App\Models\WhatsAppSession;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConnectWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected WhatsAppNumber $whatsappNumber;

    /**
     * Create a new job instance.
     */
    public function __construct(WhatsAppNumber $whatsappNumber)
    {
        $this->whatsappNumber = $whatsappNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsappService): void
    {
        try {
            Log::info('Executando ConnectWhatsAppJob', [
                'whatsapp_number_id' => $this->whatsappNumber->id,
                'jid' => $this->whatsappNumber->jid
            ]);

            // Chama o serviço WhatsApp com timeout longo
            $success = $whatsappService->connect($this->whatsappNumber);

            if ($success) {
                Log::info('ConnectWhatsAppJob concluído com sucesso', [
                    'whatsapp_number_id' => $this->whatsappNumber->id
                ]);
            } else {
                Log::warning('ConnectWhatsAppJob falhou', [
                    'whatsapp_number_id' => $this->whatsappNumber->id
                ]);

                $this->whatsappNumber->updateStatus('error', 'Falha na conexão com serviço WhatsApp');
            }

        } catch (\Exception $e) {
            Log::error('Erro no ConnectWhatsAppJob', [
                'whatsapp_number_id' => $this->whatsappNumber->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->whatsappNumber->updateStatus('error', $e->getMessage());
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ConnectWhatsAppJob falhou definitivamente', [
            'whatsapp_number_id' => $this->whatsappNumber->id,
            'error' => $exception->getMessage()
        ]);

        $this->whatsappNumber->updateStatus('error', 'Job de conexão falhou: ' . $exception->getMessage());
    }
}