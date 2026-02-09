<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\WhatsAppNumber;
use App\Models\WhatsAppSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $sessionId,
        public string $from,
        public ?string $message,
        public ?string $messageId,
        public string $type,
        public array $metadata
    ) {
    }

    public function handle(): void
    {
        try {
            DB::beginTransaction();

            // Buscar sessão
            $session = WhatsAppSession::where('session_id', $this->sessionId)->first();

            if (!$session) {
                Log::warning('Sessão não encontrada', ['session_id' => $this->sessionId]);
                return;
            }

            $whatsappNumber = $session->whatsappNumber;
            $company = $whatsappNumber->company;

            // Criar ou buscar contato
            $contact = Contact::findOrCreateFromWhatsApp(
                $company,
                $whatsappNumber,
                $this->from,
                [
                    'name' => $this->metadata['push_name'] ?? null,
                    'phone_number' => $this->extractPhoneNumber($this->from),
                    'metadata' => $this->metadata
                ]
            );

            // Buscar ou criar conversa
            $department = $company->departments()->where('slug', 'geral')->first();

            if (!$department) {
                $department = $company->departments()->first();
            }

            if (!$department) {
                Log::error('Empresa sem departamentos', ['company_id' => $company->id]);
                DB::rollBack();
                return;
            }

            $conversation = Conversation::findOrCreateForContact(
                $contact,
                $session,
                $department
            );

            // Criar mensagem
            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => null, // Mensagem do cliente
                'department_id' => $department->id,
                'direction' => 'inbound',
                'type' => $this->type,
                'content' => $this->message,
                'whatsapp_message_id' => $this->messageId,
                'whatsapp_metadata' => $this->metadata,
                'sent_at' => now(),
                'delivery_status' => 'delivered',
                'delivered_at' => now(),
            ]);

            // Atualizar última mensagem da conversa
            $conversation->update([
                'last_message_at' => now(),
                'status' => $conversation->status === 'closed' ? 'pending' : $conversation->status
            ]);

            // Atualizar atividade do número
            $whatsappNumber->updateActivity();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao processar mensagem', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => [
                    'session_id' => $this->sessionId,
                    'from' => $this->from
                ]
            ]);

            throw $e;
        }
    }

    private function extractPhoneNumber(string $jid): ?string
    {
        // Extrair número do JID (ex: 5511999999999@s.whatsapp.net -> 5511999999999)
        if (preg_match('/^(\d+)@/', $jid, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
