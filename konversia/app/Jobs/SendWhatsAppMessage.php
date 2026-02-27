<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\WhatsAppNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Lock configuration for preventing concurrent sends to the same number
     */
    private const LOCK_TIMEOUT = 30; // seconds
    private const LOCK_KEY_PREFIX = 'whatsapp:send_lock:';
    private const MAX_RETRY_ATTEMPTS = 3;
    private const RETRY_DELAY = 10; // seconds

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = self::MAX_RETRY_ATTEMPTS;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    public function __construct(
        public Message $message,
        public WhatsAppNumber $whatsappNumber,
        public string $to
    ) {
        $this->onQueue('outgoing');
    }

    public function handle(): void
    {
        $lockKey = self::LOCK_KEY_PREFIX . $this->whatsappNumber->jid;

        Log::info('Iniciando envio de mensagem WhatsApp', [
            'message_id' => $this->message->id,
            'whatsapp_number_id' => $this->whatsappNumber->id,
            'jid' => $this->whatsappNumber->jid,
            'lock_key' => $lockKey
        ]);

        // Tentar adquirir lock para este número WhatsApp
        $lock = Cache::lock($lockKey, self::LOCK_TIMEOUT);

        try {
            // Aguardar até 5 segundos para adquirir o lock
            $lockAcquired = $lock->block(5);

            if (!$lockAcquired) {
                Log::warning('Lock não disponível para envio de mensagem WhatsApp', [
                    'message_id' => $this->message->id,
                    'whatsapp_number_id' => $this->whatsappNumber->id,
                    'jid' => $this->whatsappNumber->jid,
                    'lock_key' => $lockKey
                ]);

                // Liberar o job para tentar novamente depois
                $this->release(10); // Tentar novamente em 10 segundos
                return;
            }

            Log::info('Lock adquirido, processando envio de mensagem', [
                'message_id' => $this->message->id,
                'jid' => $this->whatsappNumber->jid
            ]);

            // Processar o envio da mensagem
            $this->processMessageSend();

        } catch (\Exception $e) {
            Log::error('Exceção durante envio de mensagem WhatsApp', [
                'message_id' => $this->message->id,
                'whatsapp_number_id' => $this->whatsappNumber->id,
                'jid' => $this->whatsappNumber->jid,
                'error' => $e->getMessage()
            ]);

            throw $e;
        } finally {
            // Sempre liberar o lock, mesmo em caso de erro
            try {
                $lock->release();
                Log::debug('Lock liberado para WhatsApp', [
                    'jid' => $this->whatsappNumber->jid,
                    'lock_key' => $lockKey
                ]);
            } catch (\Exception $e) {
                Log::warning('Erro ao liberar lock WhatsApp', [
                    'jid' => $this->whatsappNumber->jid,
                    'lock_key' => $lockKey,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Process the actual message sending logic
     */
    private function processMessageSend(): void
    {
        try {
            $whatsappServiceUrl = config('services.whatsapp.service_url');

            // Debug: Verificar JID sendo usado
            Log::info('SendWhatsAppMessage - Verificação de JID', [
                'message_id' => $this->message->id,
                'whatsapp_number_jid' => $this->whatsappNumber->jid,
                'contact_jid_from_conversation' => $this->to,
                'service_url' => $whatsappServiceUrl
            ]);

            // Preparar payload baseado no tipo de mensagem
            $messagePayload = $this->prepareMessagePayload();

            // Usar nova API Go: POST /number/message com X-Number-Id header (usa o JID)
            Log::info('Enviando mensagem para serviço WhatsApp', [
                'service_url' => $whatsappServiceUrl,
                'x_number_id' => $this->whatsappNumber->jid,
                'to' => $this->to,
                'message_type' => $this->message->type,
                'payload' => $messagePayload
            ]);

            $response = Http::timeout(25)->retry(2, 100)->withHeaders([
                'X-Number-Id' => $this->whatsappNumber->jid
            ])->post("{$whatsappServiceUrl}/number/message", [
                'To' => $this->to,
                'Message' => $messagePayload
            ]);

            if ($response->successful()) {
                $this->message->update([
                    'delivery_status' => 'sent',
                    'sent_at' => now()
                    // O serviço Go não retorna message_id, então removemos essa parte
                ]);

                Log::info('Mensagem WhatsApp enviada com sucesso', [
                    'message_id' => $this->message->id,
                    'whatsapp_number_id' => $this->whatsappNumber->id,
                    'jid' => $this->whatsappNumber->jid
                ]);
            } else {
                // Extrair mensagem de erro específica da resposta
                $errorMessage = $this->extractErrorMessage($response);
                $responseBody = $response->body();
                $responseStatus = $response->status();

                Log::error('Erro ao enviar mensagem via WhatsApp', [
                    'message_id' => $this->message->id,
                    'whatsapp_number_id' => $this->whatsappNumber->id,
                    'jid' => $this->whatsappNumber->jid,
                    'service_url' => $whatsappServiceUrl,
                    'request_payload' => ['To' => $this->to, 'Message' => $messagePayload],
                    'response_status' => $responseStatus,
                    'response_body' => $responseBody,
                    'extracted_error' => $errorMessage
                ]);

                $this->message->update([
                    'delivery_status' => 'failed',
                    'error_message' => $errorMessage ?: "HTTP {$responseStatus}: {$responseBody}"
                ]);

                // Para erros críticos, pode ser útil tentar novamente
                if ($response->status() >= 500) {
                    throw new \Exception("Erro do servidor WhatsApp: " . $response->body());
                }
            }

        } catch (\Exception $e) {
            $this->message->update([
                'delivery_status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            Log::error('Exceção ao enviar mensagem', [
                'message_id' => $this->message->id,
                'whatsapp_number_id' => $this->whatsappNumber->id,
                'jid' => $this->whatsappNumber->jid,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Prepare message payload based on message type
     */
    private function prepareMessagePayload(): array
    {
        // Para mídias outbound, usar URL configurada para o whatsapp-service
        // Esta URL deve ser acessível pelo container do whatsapp-service
        $baseUrl = config('services.whatsapp.laravel_url', config('app.url'));

        switch ($this->message->type) {
            case 'text':
                return [
                    'Conversation' => $this->message->content
                ];

            case 'image':
                return [
                    'ImageMessage' => [
                        'Caption' => $this->message->content, // optional caption
                        'Mimetype' => $this->message->file_mime_type,
                        'URL' => $baseUrl . $this->message->getFileUrl()
                    ]
                ];

            case 'video':
                return [
                    'VideoMessage' => [
                        'Caption' => $this->message->content, // optional caption
                        'Mimetype' => $this->message->file_mime_type,
                        'URL' => $baseUrl . $this->message->getFileUrl()
                    ]
                ];

            case 'audio':
                return [
                    'AudioMessage' => [
                        'Mimetype' => $this->message->file_mime_type,
                        'URL' => $baseUrl . $this->message->getFileUrl(),
                        'PTT' => $this->message->media_metadata['voice_note'] ?? false
                    ]
                ];

            case 'document':
                return [
                    'DocumentMessage' => [
                        'Title' => $this->message->media_metadata['title'] ?? null,
                        'FileName' => $this->message->file_name,
                        'Mimetype' => $this->message->file_mime_type,
                        'URL' => $baseUrl . $this->message->getFileUrl()
                    ]
                ];

            default:
                // Fallback to text message
                return [
                    'Conversation' => $this->message->content ?: 'Mensagem não suportada'
                ];
        }
    }

    /**
     * Extract specific error message from WhatsApp API response
     */
    private function extractErrorMessage(\Illuminate\Http\Client\Response $response): string
    {
        try {
            $body = $response->body();
            $data = $response->json();

            // Para respostas JSON com campo "message"
            if (isset($data['message'])) {
                return $data['message'];
            }

            // Para respostas JSON com campo "error"
            if (isset($data['error'])) {
                return is_array($data['error']) ? json_encode($data['error']) : $data['error'];
            }

            // Para outros formatos JSON
            if (is_array($data) && !empty($data)) {
                return json_encode($data);
            }

            // Fallback para o corpo da resposta
            return $body ?: 'Erro desconhecido no envio da mensagem';

        } catch (\Exception $e) {
            // Se não conseguir fazer parse, retorna o corpo bruto
            return $response->body() ?: 'Erro desconhecido no envio da mensagem';
        }
    }
}
