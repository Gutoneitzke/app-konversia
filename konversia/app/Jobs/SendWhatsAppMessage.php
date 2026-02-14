<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\WhatsAppNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Message $message,
        public WhatsAppNumber $whatsappNumber,
        public string $to
    ) {}

    public function handle(): void
    {
        try {
            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL', 'http://localhost:8080');

            // Usar nova API Go: POST /number/message com X-Number-Id header (usa o JID)
            $response = Http::withHeaders([
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
