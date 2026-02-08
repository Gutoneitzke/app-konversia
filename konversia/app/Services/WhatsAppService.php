<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Message;
use App\Models\WhatsAppNumber;
use App\Models\WhatsAppSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $serviceUrl;

    public function __construct()
    {
        $this->serviceUrl = config('services.whatsapp.service_url', env('WHATSAPP_SERVICE_URL', 'http://localhost:3001'));
    }

    /**
     * Conectar número WhatsApp
     */
    public function connect(WhatsAppNumber $whatsappNumber): bool
    {
        try {
            // Criar ou buscar sessão
            $session = WhatsAppSession::firstOrCreate(
                [
                    'company_id' => $whatsappNumber->company_id,
                    'whatsapp_number_id' => $whatsappNumber->id,
                ],
                [
                    'session_id' => $whatsappNumber->api_key,
                    'status' => 'disconnected',
                ]
            );

            // Atualizar session_id se necessário
            if ($session->session_id !== $whatsappNumber->api_key) {
                $session->update(['session_id' => $whatsappNumber->api_key]);
            }

            // Chamar serviço Node.js
            $response = Http::post("{$this->serviceUrl}/connect", [
                'session_id' => $session->session_id
            ]);

            if ($response->successful()) {
                $whatsappNumber->updateStatus('connecting');
                $session->update(['status' => 'connecting']);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Erro ao conectar WhatsApp', [
                'whatsapp_number_id' => $whatsappNumber->id,
                'error' => $e->getMessage()
            ]);

            $whatsappNumber->updateStatus('error', $e->getMessage());
            return false;
        }
    }

    /**
     * Desconectar número WhatsApp
     */
    public function disconnect(WhatsAppNumber $whatsappNumber): bool
    {
        try {
            $session = $whatsappNumber->activeSession;

            if (!$session) {
                return false;
            }

            $response = Http::post("{$this->serviceUrl}/disconnect", [
                'session_id' => $session->session_id
            ]);

            if ($response->successful()) {
                $whatsappNumber->updateStatus('inactive');
                $session->update(['status' => 'disconnected']);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Erro ao desconectar WhatsApp', [
                'whatsapp_number_id' => $whatsappNumber->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Enviar mensagem via WhatsApp
     */
    public function sendMessage(Message $message, string $to): void
    {
        $conversation = $message->conversation;
        $session = $conversation->whatsappSession;

        if (!$session || $session->status !== 'connected') {
            $message->update(['delivery_status' => 'failed']);
            return;
        }

        // Enviar via Job
        SendWhatsAppMessage::dispatch($message, $session->session_id, $to);
    }

    /**
     * Verificar status da conexão
     */
    public function checkStatus(WhatsAppNumber $whatsappNumber): ?array
    {
        try {
            $session = $whatsappNumber->activeSession;

            if (!$session) {
                return null;
            }

            $response = Http::get("{$this->serviceUrl}/status/{$session->session_id}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Erro ao verificar status', [
                'whatsapp_number_id' => $whatsappNumber->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Obter QR Code da sessão
     */
    public function getQRCode(WhatsAppNumber $whatsappNumber): ?string
    {
        $session = WhatsAppSession::where('whatsapp_number_id', $whatsappNumber->id)
                                  ->where('status', 'connecting')
                                  ->first();

        if (!$session || !isset($session->metadata['qr_code'])) {
            return null;
        }

        return $session->metadata['qr_code'];
    }
}

