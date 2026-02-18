<?php

namespace App\Jobs;

use App\Models\WhatsAppNumber;
use App\Models\WhatsAppSession;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckWhatsAppConnectionsStatus implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('automation');
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsappService): void
    {
        Log::info('Iniciando verificação periódica do status das conexões WhatsApp');

        // Buscar todos os números que estão marcados como conectados ou conectando
        $numbers = WhatsAppNumber::whereIn('status', ['connected', 'connecting'])
            ->with(['sessions' => function ($query) {
                $query->orderBy('id', 'desc');
            }])
            ->get();

        Log::info('Encontrados números para verificação', [
            'total_numbers' => $numbers->count()
        ]);

        foreach ($numbers as $number) {
            try {
                $this->checkNumberStatus($number, $whatsappService);
            } catch (\Exception $e) {
                Log::error('Erro ao verificar status do número WhatsApp', [
                    'whatsapp_number_id' => $number->id,
                    'jid' => $number->jid,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Verificação periódica do status das conexões WhatsApp concluída');
    }

    /**
     * Verificar o status de um número específico
     */
    private function checkNumberStatus(WhatsAppNumber $number, WhatsAppService $whatsappService): void
    {
        Log::info('Verificando status do número WhatsApp', [
            'whatsapp_number_id' => $number->id,
            'jid' => $number->jid,
            'current_status' => $number->status
        ]);

        // Verificar status no serviço Go
        $status = $whatsappService->checkStatus($number);

        if (!$status) {
            Log::warning('Não foi possível obter status do serviço Go', [
                'whatsapp_number_id' => $number->id
            ]);
            return;
        }

        $isConnected = $status['IsConnected'] ?? false;
        $isLoggedIn = $status['IsLoggedIn'] ?? false;

        Log::info('Status obtido do serviço Go', [
            'whatsapp_number_id' => $number->id,
            'IsConnected' => $isConnected,
            'IsLoggedIn' => $isLoggedIn
        ]);

        // Se está conectado no serviço Go e conectando no banco, acelerar para conectado
        if ($isLoggedIn && $number->status === 'connecting') {
            Log::info('Número conectado no serviço Go e conectando no banco - marcando como conectado', [
                'whatsapp_number_id' => $number->id
            ]);

            $number->updateStatus('connected');

            // Atualizar sessão se existir
            $session = $number->sessions->first();
            if ($session) {
                $session->update(['status' => 'connected']);
            }

            return;
        }

        // Se está desconectado no serviço Go mas conectado no banco
        if (!$isLoggedIn && in_array($number->status, ['connected', 'connecting'])) {
            Log::info('Número desconectado no serviço Go, atualizando status local', [
                'whatsapp_number_id' => $number->id,
                'old_status' => $number->status
            ]);

            $number->updateStatus('inactive');

            // Atualizar sessão se existir
            $session = $number->sessions->first();
            if ($session) {
                $session->update(['status' => 'disconnected']);
            }
        }
        // Se está conectado no serviço Go mas desconectado no banco
        elseif ($isLoggedIn && $number->status === 'inactive') {
            Log::info('Número conectado no serviço Go, mas inativo no banco - reconectando', [
                'whatsapp_number_id' => $number->id
            ]);

            // Atualizar o status para conectado
            $number->updateStatus('connected');

            // Atualizar sessão se existir
            $session = $number->sessions->first();
            if ($session) {
                $session->update(['status' => 'connected']);
            }

            Log::info('Status reconectado automaticamente', [
                'whatsapp_number_id' => $number->id
            ]);
        }
    }
}
