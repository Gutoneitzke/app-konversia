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
        $this->serviceUrl = config('services.whatsapp.service_url', env('WHATSAPP_SERVICE_URL', 'http://localhost:8080'));
    }

    /**
     * Conectar número WhatsApp (serviço Go)
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

            // Chamar serviço Go - POST /number com X-Number-Id header
            $response = Http::withHeaders([
                'X-Number-Id' => $whatsappNumber->jid
            ])->post("{$this->serviceUrl}/number");

            if ($response->successful()) {
                $data = $response->json();
                $jid = $data['ID'] ?? null;

                // Atualizar JID real para simplificar futuras associações
                if ($jid) {
                    $whatsappNumber->update(['jid' => $jid]);
                }

                $whatsappNumber->updateStatus('connecting');
                $session->update([
                    'session_id' => $jid ?: $whatsappNumber->jid, // Atualizar session_id para o JID
                    'status' => 'connecting',
                    'metadata' => array_merge($session->metadata ?? [], [
                        'service_id' => $jid
                    ])
                ]);

                Log::info('WhatsApp conectado - JID atualizado', [
                    'whatsapp_number_id' => $whatsappNumber->id,
                    'jid' => $jid,
                    'session_id' => $session->id
                ]);

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
     * Desconectar número WhatsApp (serviço Go)
     */
    public function disconnect(WhatsAppNumber $whatsappNumber): bool
    {
        try {
            $session = $whatsappNumber->activeSession;

            if (!$session) {
                return false;
            }

            // Chamar serviço Go - DELETE /number com X-Number-Id header (usa o JID)
            $response = Http::withHeaders([
                'X-Number-Id' => $whatsappNumber->jid
            ])->delete("{$this->serviceUrl}/number");

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
     * Enviar mensagem via WhatsApp (serviço Go)
     */
    public function sendMessage(Message $message, string $to): void
    {
        $conversation = $message->conversation;
        $session = $conversation->whatsappSession;

        if (!$session || $session->status !== 'connected') {
            $message->update(['delivery_status' => 'failed']);
            return;
        }

        // Enviar via Job usando o WhatsAppNumber
        SendWhatsAppMessage::dispatch($message, $session->whatsappNumber, $to);
    }

    /**
     * Verificar status da conexão (serviço Go)
     */
    public function checkStatus(WhatsAppNumber $whatsappNumber): ?array
    {
        try {
            // Buscar sessão mais recente (mesmo que conectando)
            $session = WhatsAppSession::where('whatsapp_number_id', $whatsappNumber->id)
                ->latest('created_at')
                ->first();

            if (!$session) {
                return null;
            }

            // Chamar serviço Go - GET /number com X-Number-Id header (usa o JID)
            $response = Http::withHeaders([
                'X-Number-Id' => $whatsappNumber->jid
            ])->get("{$this->serviceUrl}/number");

            if ($response->successful()) {
                $data = $response->json();

                // Mapear status do serviço Go para status interno
                $isConnected = $data['IsConnected'] ?? false;
                $isLoggedIn = $data['IsLoggedIn'] ?? false;

                if ($isConnected && $isLoggedIn) {
                    if (!$whatsappNumber->isConnected()) {
                        $whatsappNumber->updateStatus('connected');
                        if (!$session->connected_at) {
                            $session->update(['connected_at' => now()]);
                        }
                    }

                    if ($session->status !== 'connected') {
                        $session->update(['status' => 'connected']);
                    }
                } elseif (!$isConnected || !$isLoggedIn) {
                    // Se o serviço diz que não está conectado/logado
                    if ($whatsappNumber->isConnected() || $whatsappNumber->status === 'connecting') {
                        $whatsappNumber->updateStatus('inactive');
                    }
                    if ($session->status !== 'disconnected') {
                        $session->update(['status' => 'disconnected']);
                    }
                }

                return $data;
            }

            Log::warning('Falha ao verificar status WhatsApp', [
                'whatsapp_number_id' => $whatsappNumber->id,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

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
     * Obter QR Code da sessão (recebido via webhook)
     */
    public function getQRCode(WhatsAppNumber $whatsappNumber): ?string
    {
        Log::info('Buscando QR code', [
            'whatsapp_number_id' => $whatsappNumber->id,
            'session_count' => $whatsappNumber->sessions()->count()
        ]);

        // Buscar sessão mais recente com QR code (qualquer status)
        $session = WhatsAppSession::where('whatsapp_number_id', $whatsappNumber->id)
            ->whereNotNull('metadata->qr_code')
            ->where('metadata->qr_code', '!=', '')
            ->latest('created_at')
            ->first();

        if (!$session) {
            Log::info('Nenhuma sessão com QR encontrada', [
                'whatsapp_number_id' => $whatsappNumber->id
            ]);
            return null;
        }

        $qrCode = $session->metadata['qr_code'] ?? null;

        Log::info('QR code encontrado', [
            'session_id' => $session->id,
            'session_status' => $session->status,
            'has_qr' => !empty($qrCode),
            'qr_length' => $qrCode ? strlen($qrCode) : 0
        ]);

        return $qrCode;
    }

    /**
     * Salvar QR Code recebido via webhook
     */
    public function saveQRCode(string $numberId, string $qrCode): bool
    {
        try {
            // Agora é simples - o numberId é o api_key que contém o JID correto
            $session = WhatsAppSession::where('session_id', $numberId)->first();

            if (!$session) {
                Log::warning('Sessão não encontrada para QR code', [
                    'number_id' => $numberId,
                    'total_sessions' => WhatsAppSession::count(),
                    'all_session_ids' => WhatsAppSession::pluck('session_id')->toArray()
                ]);
                return false;
            }

            Log::info('Salvando QR code', [
                'number_id' => $numberId,
                'session_id' => $session->id,
                'qr_length' => strlen($qrCode),
                'session_status' => $session->status
            ]);

            $session->update([
                'status' => 'connecting',
                'metadata' => array_merge($session->metadata ?? [], [
                    'qr_code' => $qrCode,
                    'qr_received_at' => now()->toIso8601String()
                ])
            ]);

            Log::info('QR code salvo com sucesso', [
                'session_id' => $session->id,
                'whatsapp_number_id' => $session->whatsapp_number_id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erro ao salvar QR code', [
                'number_id' => $numberId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

