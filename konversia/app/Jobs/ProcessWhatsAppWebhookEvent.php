<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\WhatsAppNumber;
use App\Models\WhatsAppSession;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $numberId;
    protected string $eventType;
    protected array $eventData;

    /**
     * Create a new job instance.
     */
    public function __construct(string $numberId, string $eventType, array $eventData)
    {
        $this->numberId = $numberId;
        $this->eventType = $eventType;
        $this->eventData = $eventData;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsappService): void
    {
        try {
            Log::info('Processando evento WhatsApp webhook', [
                'number_id' => $this->numberId,
                'event_type' => $this->eventType,
                'data' => $this->eventData
            ]);

            // Buscar WhatsAppNumber pelo JID e pegar a sessão ativa
            $whatsappNumber = WhatsAppNumber::where('jid', $this->numberId)->first();

            if (!$whatsappNumber) {
                Log::warning('WhatsAppNumber não encontrado para JID', [
                    'jid' => $this->numberId,
                    'event_type' => $this->eventType
                ]);
                return;
            }

            $session = $whatsappNumber->activeSession;

            if (!$session) {
                Log::warning('Sessão ativa não encontrada para WhatsAppNumber', [
                    'whatsapp_number_id' => $whatsappNumber->id,
                    'jid' => $this->numberId,
                    'event_type' => $this->eventType,
                    'total_sessions' => $whatsappNumber->sessions()->count()
                ]);
                return;
            }

            Log::info('Sessão encontrada via WhatsAppNumber JID', [
                'jid' => $this->numberId,
                'whatsapp_number_id' => $whatsappNumber->id,
                'session_id' => $session->id,
                'event_type' => $this->eventType
            ]);

            $whatsappNumber = $session->whatsappNumber;

            if (!$whatsappNumber) {
                Log::warning('WhatsAppNumber não encontrado para sessão', [
                    'session_id' => $session->id,
                    'number_id' => $this->numberId
                ]);
                return;
            }

            // Processar evento baseado no tipo
            switch ($this->eventType) {
                case 'QR':
                case 'QRChannelItem': // Evento de QR do whatsmeow
                    $this->processQREvent($whatsappService, $session, $whatsappNumber);
                    break;

                case 'Connected':
                    $this->processConnectedEvent($session, $whatsappNumber);
                    break;

                case 'LoggedOut':
                    $this->processLoggedOutEvent($session, $whatsappNumber);
                    break;

                case 'AppState': // Estado da aplicação (conectado/desconectado)
                    $this->processAppStateEvent($session, $whatsappNumber);
                    break;

                case 'Message': // Mensagem recebida
                    $this->processMessageEvent($session, $whatsappNumber);
                    break;

                default:
                    Log::info('Evento WhatsApp não mapeado', [
                        'event_type' => $this->eventType,
                        'data' => $this->eventData
                    ]);
                    break;
            }

        } catch (\Exception $e) {
            Log::error('Erro ao processar evento WhatsApp webhook', [
                'number_id' => $this->numberId,
                'event_type' => $this->eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Processar evento de QR Code
     */
    protected function processQREvent(WhatsAppService $whatsappService, WhatsAppSession $session, WhatsAppNumber $whatsappNumber): void
    {
        // Para QRChannelItem, verificar se é um evento "code"
        if ($this->eventType === 'QRChannelItem') {
            $event = $this->eventData['Event'] ?? null;
            if ($event !== 'code') {
                Log::info('Evento QRChannelItem ignorado (não é code)', [
                    'event' => $event,
                    'number_id' => $this->numberId
                ]);
                return;
            }
            $qrCode = $this->eventData['Code'] ?? null;
        } else {
            // Para outros tipos de evento QR
            $qrCode = $this->eventData['Code'] ?? $this->eventData['code'] ?? null;
        }

        if (!$qrCode) {
            Log::warning('QR Code não encontrado no evento', [
                'event_data' => $this->eventData,
                'event_type' => $this->eventType,
                'number_id' => $this->numberId
            ]);
            return;
        }

        Log::info('Processando QR Code', [
            'number_id' => $this->numberId,
            'session_id' => $session->id,
            'qr_code_length' => strlen($qrCode),
            'event_type' => $this->eventType
        ]);

        $whatsappService->saveQRCode($this->numberId, $qrCode);

        Log::info('QR Code salvo para WhatsApp', [
            'whatsapp_number_id' => $whatsappNumber->id,
            'session_id' => $session->id
        ]);
    }

    /**
     * Processar evento de conexão
     */
    protected function processConnectedEvent(WhatsAppSession $session, WhatsAppNumber $whatsappNumber): void
    {
        // Salvar JID real se for identificado
        $metadata = $session->metadata ?? [];
        if (strpos($this->numberId, '@') !== false) {
            $metadata['jid'] = $this->numberId;
        }

        $session->update([
            'status' => 'connected',
            'connected_at' => now(),
            'metadata' => $metadata
        ]);

        $whatsappNumber->updateStatus('connected');

        Log::info('WhatsApp conectado', [
            'whatsapp_number_id' => $whatsappNumber->id,
            'session_id' => $session->id,
            'jid' => $this->numberId,
            'metadata' => $metadata
        ]);
    }

    /**
     * Processar evento de desconexão
     */
    protected function processLoggedOutEvent(WhatsAppSession $session, WhatsAppNumber $whatsappNumber): void
    {
        $session->update(['status' => 'disconnected']);
        $whatsappNumber->updateStatus('inactive');

        Log::info('WhatsApp desconectado', [
            'whatsapp_number_id' => $whatsappNumber->id,
            'session_id' => $session->id
        ]);
    }

    /**
     * Processar evento de estado da aplicação
     */
    protected function processAppStateEvent(WhatsAppSession $session, WhatsAppNumber $whatsappNumber): void
    {
        // AppState indica mudanças no estado da aplicação
        // Podemos usar isso para detectar desconexões ou mudanças de estado

        $action = $this->eventData['agentAction'] ?? null;
        $isDeleted = $action['isDeleted'] ?? false;

        if ($isDeleted) {
            // Agente foi removido - desconectar
            $session->update(['status' => 'disconnected']);
            $whatsappNumber->updateStatus('inactive');

            Log::info('WhatsApp desconectado via AppState', [
                'whatsapp_number_id' => $whatsappNumber->id,
                'session_id' => $session->id
            ]);
        } else {
            // Agente ativo - pode indicar reconexão
            Log::info('AppState atualizado', [
                'whatsapp_number_id' => $whatsappNumber->id,
                'session_id' => $session->id,
                'action' => $action
            ]);
        }
    }

    /**
     * Processar evento de mensagem recebida
     */
    protected function processMessageEvent(WhatsAppSession $session, WhatsAppNumber $whatsappNumber): void
    {
        try {
            $messageInfo = $this->eventData['Info'] ?? [];
            $messageData = $this->eventData['Message'] ?? [];

            // Verificar se é mensagem de usuário (não do próprio número)
            $isFromMe = $messageInfo['IsFromMe'] ?? false;
            if ($isFromMe) {
                Log::info('Mensagem própria ignorada', [
                    'whatsapp_number_id' => $whatsappNumber->id,
                    'message_id' => $messageInfo['ID'] ?? ''
                ]);
                return; // Ignorar mensagens enviadas pelo próprio número
            }

            $from = $messageInfo['Sender'] ?? $messageInfo['Chat'] ?? '';
            $chat = $messageInfo['Chat'] ?? $from;
            $messageId = $messageInfo['ID'] ?? '';
            $timestamp = $messageInfo['Timestamp'] ?? now();
            $pushName = $messageInfo['PushName'] ?? '';

            // Extrair conteúdo da mensagem
            $content = '';
            $messageType = 'text';
            $mediaMetadata = null;

            if (isset($messageData['conversation'])) {
                $content = $messageData['conversation'];
                $messageType = 'text';
            } elseif (isset($messageData['imageMessage'])) {
                $content = $messageData['imageMessage']['caption'] ?? '[Imagem]';
                $messageType = 'image';
                $mediaMetadata = $messageData['imageMessage'];
            } elseif (isset($messageData['videoMessage'])) {
                $content = $messageData['videoMessage']['caption'] ?? '[Vídeo]';
                $messageType = 'video';
                $mediaMetadata = $messageData['videoMessage'];
            } elseif (isset($messageData['audioMessage'])) {
                $content = '[Áudio]';
                $messageType = 'audio';
                $mediaMetadata = $messageData['audioMessage'];
            } elseif (isset($messageData['documentMessage'])) {
                $content = $messageData['documentMessage']['fileName'] ?? '[Documento]';
                $messageType = 'document';
                $mediaMetadata = $messageData['documentMessage'];
            } else {
                $content = '[Mensagem não suportada]';
                $messageType = 'unknown';
            }

            // Garantir que estamos marcados como conectados
            if ($session->status !== 'connected') {
                $session->update(['status' => 'connected', 'connected_at' => now()]);
                $whatsappNumber->updateStatus('connected');
            }

            // 1. Criar ou encontrar o contato
            $contact = $this->findOrCreateContact($whatsappNumber, $from, $pushName);

            // 2. Criar ou encontrar a conversa
            $conversation = $this->findOrCreateConversation($session, $contact, $chat);

            // 3. Criar a mensagem
            $this->createMessage($conversation, $messageInfo, $content, $messageType, $mediaMetadata);

            // 4. Atualizar timestamps
            $conversation->update(['last_message_at' => now()]);
            $contact->update(['last_seen' => now()]);

            Log::info('Mensagem WhatsApp processada com sucesso', [
                'whatsapp_number_id' => $whatsappNumber->id,
                'session_id' => $session->id,
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'from' => $from,
                'chat' => $chat,
                'content' => $content,
                'type' => $messageType,
                'push_name' => $pushName,
                'message_id' => $messageId
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar mensagem WhatsApp', [
                'session_id' => $session->id,
                'whatsapp_number_id' => $whatsappNumber->id,
                'error' => $e->getMessage(),
                'event_data' => $this->eventData
            ]);
            throw $e;
        }
    }

    /**
     * Criar ou encontrar contato
     */
    protected function findOrCreateContact(WhatsAppNumber $whatsappNumber, string $jid, string $name): Contact
    {
        $contact = Contact::where('whatsapp_number_id', $whatsappNumber->id)
            ->where('jid', $jid)
            ->first();

        if (!$contact) {
            // Extrair número do telefone do JID (antes do @)
            $phoneNumber = explode('@', $jid)[0] ?? $jid;

            $contact = Contact::create([
                'company_id' => $whatsappNumber->company_id,
                'whatsapp_number_id' => $whatsappNumber->id,
                'jid' => $jid,
                'name' => $name ?: 'Contato',
                'phone_number' => $phoneNumber,
                'is_blocked' => false,
                'is_business' => false,
            ]);

            Log::info('Novo contato criado', [
                'contact_id' => $contact->id,
                'jid' => $jid,
                'name' => $name,
                'phone_number' => $phoneNumber
            ]);
        } elseif ($contact->name !== $name && !empty($name)) {
            // Atualizar nome se mudou
            $contact->update(['name' => $name]);
        }

        return $contact;
    }

    /**
     * Criar ou encontrar conversa
     */
    protected function findOrCreateConversation(WhatsAppSession $session, Contact $contact, string $chatJid): Conversation
    {
        $conversation = Conversation::where('whatsapp_session_id', $session->id)
            ->where('contact_id', $contact->id)
            ->first();

        if (!$conversation) {
            // Buscar departamento padrão da empresa ou primeiro disponível
            $defaultDepartment = Department::where('company_id', $session->company_id)
                ->orderBy('created_at')
                ->first();

            // Se não houver departamento, criar um padrão
            if (!$defaultDepartment) {
                $defaultDepartment = Department::create([
                    'company_id' => $session->company_id,
                    'name' => 'Geral',
                    'description' => 'Departamento padrão',
                    'is_active' => true,
                ]);

                Log::info('Departamento padrão criado', [
                    'department_id' => $defaultDepartment->id,
                    'company_id' => $session->company_id
                ]);
            }

            $conversation = Conversation::create([
                'company_id' => $session->company_id,
                'whatsapp_session_id' => $session->id,
                'department_id' => $defaultDepartment->id,
                'contact_id' => $contact->id,
                'contact_jid' => $chatJid,
                'contact_name' => $contact->name,
                'status' => 'pending',
                'last_message_at' => now(),
            ]);

            Log::info('Nova conversa criada', [
                'conversation_id' => $conversation->id,
                'contact_id' => $contact->id,
                'contact_name' => $contact->name
            ]);
        }

        return $conversation;
    }

    /**
     * Criar mensagem
     */
    protected function createMessage(Conversation $conversation, array $messageInfo, string $content, string $type, ?array $mediaMetadata = null): Message
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'type' => $type,
            'content' => $content,
            'media_metadata' => $mediaMetadata,
            'sent_at' => isset($messageInfo['Timestamp']) ?
                (is_numeric($messageInfo['Timestamp']) ?
                    now()->createFromTimestamp($messageInfo['Timestamp']) :
                    now()->parse($messageInfo['Timestamp'])) :
                now(),
            'delivered_at' => now(),
            'read_at' => now(),
            'delivery_status' => 'delivered',
            'whatsapp_message_id' => $messageInfo['ID'] ?? null,
            'whatsapp_metadata' => $messageInfo,
        ]);

        Log::info('Mensagem criada', [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'type' => $type,
            'content' => $content
        ]);

        return $message;
    }
}
