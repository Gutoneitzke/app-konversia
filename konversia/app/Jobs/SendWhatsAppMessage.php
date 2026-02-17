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
            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL', 'http://localhost:8080');

            // Usar nova API Go: POST /number/message com X-Number-Id header (usa o JID)
            $response = Http::timeout(25)->retry(2, 100)->withHeaders([
                'X-Number-Id' => $this->whatsappNumber->jid
            ])->post("{$whatsappServiceUrl}/number/message", [
                'To' => $this->to,
                'Message' => $this->message->content
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
                $this->message->update([
                    'delivery_status' => 'failed'
                ]);

                Log::error('Erro ao enviar mensagem via WhatsApp', [
                    'message_id' => $this->message->id,
                    'whatsapp_number_id' => $this->whatsappNumber->id,
                    'jid' => $this->whatsappNumber->jid,
                    'error' => $response->body(),
                    'status' => $response->status()
                ]);

                // Para erros críticos, pode ser útil tentar novamente
                if ($response->status() >= 500) {
                    throw new \Exception("Erro do servidor WhatsApp: " . $response->body());
                }
            }

        } catch (\Exception $e) {
            $this->message->update([
                'delivery_status' => 'failed'
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
}
