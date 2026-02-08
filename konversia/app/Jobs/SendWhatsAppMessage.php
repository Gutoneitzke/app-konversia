<?php

namespace App\Jobs;

use App\Models\Message;
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
        public string $sessionId,
        public string $to
    ) {}

    public function handle(): void
    {
        try {
            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL', 'http://localhost:3001');

            $response = Http::post("{$whatsappServiceUrl}/send", [
                'session_id' => $this->sessionId,
                'to' => $this->to,
                'message' => $this->message->content,
                'type' => $this->message->type
            ]);

            if ($response->successful()) {
                $this->message->update([
                    'delivery_status' => 'sent',
                    'sent_at' => now(),
                    'whatsapp_message_id' => $response->json('message_id')
                ]);
            } else {
                $this->message->update([
                    'delivery_status' => 'failed'
                ]);
                
                Log::error('Erro ao enviar mensagem via WhatsApp', [
                    'message_id' => $this->message->id,
                    'error' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            $this->message->update([
                'delivery_status' => 'failed'
            ]);

            Log::error('Exceção ao enviar mensagem', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
