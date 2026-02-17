<?php

namespace App\Jobs;

use App\Models\WhatsAppNumber;
use App\Models\WhatsAppSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateConnectionStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $sessionId,
        public string $status,
        public ?string $error = null
    ) {}

    public function handle(): void
    {
        try {
            $session = WhatsAppSession::where('session_id', $this->sessionId)->first();

            if (!$session) {
                Log::warning('Sessão não encontrada para atualizar status', [
                    'session_id' => $this->sessionId
                ]);
                return;
            }

            // Mapear status do WhatsApp para nosso enum
            $mappedStatus = match($this->status) {
                'connected', 'open' => 'connected',
                'connecting' => 'connecting',
                'disconnected', 'close' => 'disconnected',
                'error' => 'error',
                default => 'disconnected'
            };

            $session->update([
                'status' => $mappedStatus,
                'connected_at' => $mappedStatus === 'connected' ? now() : $session->connected_at,
                'last_activity' => now(),
                'metadata' => array_merge($session->metadata ?? [], [
                    'last_status_update' => now()->toIso8601String(),
                    'error' => $this->error
                ])
            ]);

            // Atualizar status do número WhatsApp
            $whatsappNumber = $session->whatsappNumber;

            if ($mappedStatus === 'connected') {
                $whatsappNumber->updateStatus('connected');
            } elseif ($mappedStatus === 'error') {
                $whatsappNumber->updateStatus('error', $this->error);
            } else {
                $whatsappNumber->updateStatus('active');
            }

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar status', [
                'error' => $e->getMessage(),
                'session_id' => $this->sessionId
            ]);
        }
    }
}
