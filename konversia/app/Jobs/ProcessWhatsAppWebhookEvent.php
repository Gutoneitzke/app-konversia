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
        $this->onQueue('webhook');
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

            // Buscar WhatsAppNumber pelo JID com fallback inteligente
            $whatsappNumber = $this->findWhatsAppNumberByJid($this->numberId);

            if (!$whatsappNumber) {
                Log::warning('WhatsAppNumber não encontrado para JID', [
                    'jid' => $this->numberId,
                    'event_type' => $this->eventType
                ]);
                return;
            }

            $session = $this->getLastSession($whatsappNumber->id);

            if (!$session) {
                Log::warning('Sessão ativa não encontrada para WhatsAppNumber', [
                    'whatsapp_number_id' => $whatsappNumber->id,
                    'jid' => $this->numberId,
                    'event_type' => $this->eventType,
                    'total_sessions' => $whatsappNumber->sessions()->count()
                ]);
                return;
            }

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

                case 'PushName': // Mudança de nome do contato
                    $this->processPushNameEvent($session, $whatsappNumber);
                    break;

                case 'Receipt': // Confirmação de entrega/leitura
                    $this->processReceiptEvent($session, $whatsappNumber);
                    break;

                case 'HistorySync': // Sincronização de histórico de contatos
                    $this->processHistorySyncEvent($session, $whatsappNumber);
                    break;

                case 'AppStateSyncComplete': // Conclusão da sincronização de estado
                    $this->processAppStateSyncCompleteEvent($session, $whatsappNumber);
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

    private function getLastSession(string $whatsappNumberId): WhatsAppSession
    {
        return WhatsAppSession::where('whatsapp_number_id', $whatsappNumberId)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Encontrar WhatsAppNumber por JID com múltiplas estratégias
     */
    protected function findWhatsAppNumberByJid(string $jid): ?WhatsAppNumber
    {
        // 1. Tentar busca direta pelo JID completo
        $whatsappNumber = WhatsAppNumber::where('jid', $jid)->first();
        if ($whatsappNumber) {
            return $whatsappNumber;
        }

        // 2. Se não encontrou e o JID contém '@', extrair o número de telefone
        if (strpos($jid, '@') !== false) {
            $phoneNumber = explode('@', $jid)[0];

            // Para JIDs no formato "numero:device@s.whatsapp.net", pegar apenas o número
            if (strpos($phoneNumber, ':') !== false) {
                $phoneNumber = explode(':', $phoneNumber)[0];
            }

            // Buscar pelo número de telefone
            $whatsappNumber = WhatsAppNumber::where('phone_number', $phoneNumber)->first();
            if ($whatsappNumber) {
                Log::info('WhatsAppNumber encontrado por número de telefone', [
                    'original_jid' => $jid,
                    'extracted_phone' => $phoneNumber,
                    'whatsapp_number_id' => $whatsappNumber->id,
                    'stored_jid' => $whatsappNumber->jid
                ]);

                // Opcional: atualizar o JID no banco se for diferente
                if ($whatsappNumber->jid !== $jid) {
                    $whatsappNumber->update(['jid' => $jid]);
                    Log::info('JID atualizado no WhatsAppNumber', [
                        'whatsapp_number_id' => $whatsappNumber->id,
                        'old_jid' => $whatsappNumber->jid,
                        'new_jid' => $jid
                    ]);
                }

                return $whatsappNumber;
            }
        }

        return null;
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

        $whatsappService->saveQRCode($whatsappNumber->id, $qrCode);

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
            } elseif (isset($messageData['stickerMessage'])) {
                $content = '[Sticker]';
                $messageType = 'sticker';
                $mediaMetadata = $messageData['stickerMessage'];
            } elseif (isset($messageData['locationMessage'])) {
                $content = '[Localização]';
                $messageType = 'location';
                $mediaMetadata = $messageData['locationMessage'];
            } elseif (isset($messageData['contactMessage'])) {
                $content = '[Contato]';
                $messageType = 'contact';
                $mediaMetadata = $messageData['contactMessage'];
            } elseif (isset($messageData['extendedTextMessage'])) {
                // Link preview ou texto estendido
                $content = $messageData['extendedTextMessage']['text'] ?? $messageData['extendedTextMessage']['description'] ?? '[Link]';
                $messageType = 'link';
                $mediaMetadata = $messageData['extendedTextMessage'];
            } else {
                $content = '[Mensagem não suportada]';
                $messageType = 'text'; // Usar 'text' para mensagens não suportadas
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
     * Processar evento de mudança de nome do contato
     */
    protected function processPushNameEvent(WhatsAppSession $session, WhatsAppNumber $whatsappNumber): void
    {
        try {
            $jid = $this->eventData['JID'] ?? null;
            $oldPushName = $this->eventData['OldPushName'] ?? null;
            $newPushName = $this->eventData['NewPushName'] ?? null;

            if (!$jid || !$newPushName) {
                Log::warning('Dados insuficientes para processar PushName', [
                    'event_data' => $this->eventData,
                    'whatsapp_number_id' => $whatsappNumber->id
                ]);
                return;
            }

            // Buscar contato pelo JID
            $contact = Contact::where('whatsapp_number_id', $whatsappNumber->id)
                ->where('jid', $jid)
                ->first();

            if ($contact) {
                // Atualizar nome se mudou
                if ($contact->name !== $newPushName) {
                    $contact->update(['name' => $newPushName]);

                    Log::info('Nome do contato atualizado via PushName', [
                        'contact_id' => $contact->id,
                        'jid' => $jid,
                        'old_name' => $oldPushName,
                        'new_name' => $newPushName,
                        'whatsapp_number_id' => $whatsappNumber->id
                    ]);
                } else {
                    Log::info('PushName recebido mas nome já está atualizado', [
                        'contact_id' => $contact->id,
                        'jid' => $jid,
                        'name' => $newPushName,
                        'whatsapp_number_id' => $whatsappNumber->id
                    ]);
                }
            } else {
                Log::info('Contato não encontrado para PushName', [
                    'jid' => $jid,
                    'new_name' => $newPushName,
                    'whatsapp_number_id' => $whatsappNumber->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao processar PushName', [
                'session_id' => $session->id,
                'whatsapp_number_id' => $whatsappNumber->id,
                'error' => $e->getMessage(),
                'event_data' => $this->eventData
            ]);
            throw $e;
        }
    }

    /**
     * Processar evento de confirmação de entrega/leitura
     */
    protected function processReceiptEvent(WhatsAppSession $session, WhatsAppNumber $whatsappNumber): void
    {
        try {
            $messageIds = $this->eventData['MessageIDs'] ?? [];
            $receiptType = $this->eventData['Type'] ?? null;
            $timestamp = $this->eventData['Timestamp'] ?? null;

            if (empty($messageIds)) {
                Log::info('Receipt sem MessageIDs', [
                    'event_data' => $this->eventData,
                    'whatsapp_number_id' => $whatsappNumber->id
                ]);
                return;
            }

            // Determinar novo status baseado no tipo do receipt
            $newStatus = match ($receiptType) {
                'delivered', 'server' => 'delivered',
                'read', 'read-self' => 'read',
                default => null
            };

            if (!$newStatus) {
                Log::info('Tipo de receipt não reconhecido', [
                    'receipt_type' => $receiptType,
                    'message_ids' => $messageIds,
                    'whatsapp_number_id' => $whatsappNumber->id
                ]);
                return;
            }

            // Buscar mensagens pelos IDs do WhatsApp
            $messages = Message::whereIn('whatsapp_message_id', $messageIds)
                ->where('direction', 'outbound') // Apenas mensagens enviadas
                ->get();

            $updatedCount = 0;
            foreach ($messages as $message) {
                // Atualizar apenas se o status atual for inferior
                $statusHierarchy = ['sent' => 1, 'delivered' => 2, 'read' => 3];
                $currentLevel = $statusHierarchy[$message->delivery_status] ?? 0;
                $newLevel = $statusHierarchy[$newStatus] ?? 0;

                if ($newLevel > $currentLevel) {
                    $updateData = ['delivery_status' => $newStatus];

                    if ($newStatus === 'delivered' && !$message->delivered_at) {
                        $updateData['delivered_at'] = $timestamp ? now()->parse($timestamp) : now();
                    } elseif ($newStatus === 'read' && !$message->read_at) {
                        $updateData['read_at'] = $timestamp ? now()->parse($timestamp) : now();
                        // Marcar como entregue também se não estiver
                        if (!$message->delivered_at) {
                            $updateData['delivered_at'] = $updateData['read_at'];
                        }
                    }

                    $message->update($updateData);
                    $updatedCount++;
                }
            }

            if ($updatedCount > 0) {
                Log::info('Status de mensagens atualizado via Receipt', [
                    'receipt_type' => $receiptType,
                    'new_status' => $newStatus,
                    'message_ids' => $messageIds,
                    'updated_count' => $updatedCount,
                    'whatsapp_number_id' => $whatsappNumber->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao processar Receipt', [
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
        // Determinar o departamento para esta mensagem
        $department = $this->getDepartmentForMessage($session, $contact, $chatJid);

        // Buscar conversa existente por company_id, contact_jid e department_id
        $conversation = Conversation::where('company_id', $session->company_id)
            ->where('contact_jid', $chatJid)
            ->where('department_id', $department->id)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'company_id' => $session->company_id,
                'whatsapp_session_id' => $session->id,
                'department_id' => $department->id,
                'contact_id' => $contact->id,
                'contact_jid' => $chatJid,
                'contact_name' => $contact->name,
                'status' => 'pending',
                'last_message_at' => now(),
            ]);

            Log::info('Nova conversa criada', [
                'conversation_id' => $conversation->id,
                'contact_id' => $contact->id,
                'contact_name' => $contact->name,
                'contact_jid' => $chatJid,
                'department_id' => $department->id,
                'department_name' => $department->name
            ]);
        } else {
            // Se a conversa existe mas está associada a uma sessão diferente, atualizar
            if ($conversation->whatsapp_session_id !== $session->id) {
                $conversation->update([
                    'whatsapp_session_id' => $session->id,
                    'contact_id' => $contact->id, // Atualizar contact_id se necessário
                    'last_message_at' => now()
                ]);

                Log::info('Conversa existente atualizada com nova sessão', [
                    'conversation_id' => $conversation->id,
                    'old_session_id' => $conversation->whatsapp_session_id,
                    'new_session_id' => $session->id,
                    'contact_jid' => $chatJid,
                    'department_id' => $department->id
                ]);
            }
        }

        return $conversation;
    }

    /**
     * Determinar o departamento para a mensagem recebida
     */
    protected function getDepartmentForMessage(WhatsAppSession $session, Contact $contact, string $chatJid): Department
    {
        // Por enquanto, usar departamento padrão da empresa
        // TODO: Implementar lógica de roteamento baseada em regras, tags, etc.
        $department = Department::where('company_id', $session->company_id)
            ->orderBy('created_at')
            ->first();

        // Se não houver departamento, criar um padrão
        if (!$department) {
            $department = Department::create([
                'company_id' => $session->company_id,
                'name' => 'Geral',
                'description' => 'Departamento padrão',
                'is_active' => true,
            ]);

            Log::info('Departamento padrão criado', [
                'department_id' => $department->id,
                'company_id' => $session->company_id
            ]);
        }

        return $department;
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

    /**
     * Processar evento de sincronização de histórico de contatos
     */
    protected function processHistorySyncEvent(WhatsAppSession $session, WhatsAppNumber $whatsappNumber): void
    {
        try {
            $syncData = $this->eventData['Data'] ?? [];
            $pushnames = $syncData['pushnames'] ?? [];
            $syncType = $syncData['syncType'] ?? null;
            $chunkOrder = $syncData['chunkOrder'] ?? null;

            if (empty($pushnames)) {
                Log::info('HistorySync sem pushnames', [
                    'sync_type' => $syncType,
                    'chunk_order' => $chunkOrder,
                    'whatsapp_number_id' => $whatsappNumber->id
                ]);
                return;
            }

            $updatedCount = 0;
            foreach ($pushnames as $pushnameData) {
                $jid = $pushnameData['ID'] ?? null;
                $pushname = $pushnameData['pushname'] ?? null;

                if (!$jid || $pushname === null) {
                    continue; // Pular entradas inválidas
                }

                // Buscar contato existente ou criar novo
                $contact = Contact::where('whatsapp_number_id', $whatsappNumber->id)
                    ->where('jid', $jid)
                    ->first();

                if (!$contact) {
                    // Criar novo contato com o pushname
                    $phoneNumber = explode('@', $jid)[0] ?? $jid;

                    $contact = Contact::create([
                        'company_id' => $whatsappNumber->company_id,
                        'whatsapp_number_id' => $whatsappNumber->id,
                        'jid' => $jid,
                        'name' => $pushname ?: 'Contato',
                        'phone_number' => $phoneNumber,
                        'is_blocked' => false,
                        'is_business' => false,
                    ]);

                    Log::info('Novo contato criado via HistorySync', [
                        'contact_id' => $contact->id,
                        'jid' => $jid,
                        'pushname' => $pushname,
                        'whatsapp_number_id' => $whatsappNumber->id
                    ]);
                } elseif ($contact->name !== $pushname && !empty($pushname)) {
                    // Atualizar nome se mudou
                    $oldName = $contact->name;
                    $contact->update(['name' => $pushname]);

                    Log::info('Nome do contato atualizado via HistorySync', [
                        'contact_id' => $contact->id,
                        'jid' => $jid,
                        'old_name' => $oldName,
                        'new_name' => $pushname,
                        'whatsapp_number_id' => $whatsappNumber->id
                    ]);
                }

                $updatedCount++;
            }

            Log::info('HistorySync processado', [
                'sync_type' => $syncType,
                'chunk_order' => $chunkOrder,
                'pushnames_count' => count($pushnames),
                'processed_count' => $updatedCount,
                'whatsapp_number_id' => $whatsappNumber->id
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar HistorySync', [
                'session_id' => $session->id,
                'whatsapp_number_id' => $whatsappNumber->id,
                'error' => $e->getMessage(),
                'event_data' => $this->eventData
            ]);
            throw $e;
        }
    }

    /**
     * Processar evento de conclusão da sincronização de estado da aplicação
     */
    protected function processAppStateSyncCompleteEvent(WhatsAppSession $session, WhatsAppNumber $whatsappNumber): void
    {
        try {
            $name = $this->eventData['Name'] ?? null;
            $version = $this->eventData['Version'] ?? null;
            $recovery = $this->eventData['Recovery'] ?? false;

            Log::info('AppStateSyncComplete processado', [
                'name' => $name,
                'version' => $version,
                'recovery' => $recovery,
                'whatsapp_number_id' => $whatsappNumber->id,
                'session_id' => $session->id
            ]);

            // Marcar sessão como totalmente sincronizada se necessário
            $metadata = $session->metadata ?? [];
            $metadata['app_state_sync_complete'] = [
                'name' => $name,
                'version' => $version,
                'recovery' => $recovery,
                'completed_at' => now()->toISOString()
            ];

            $session->update(['metadata' => $metadata]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar AppStateSyncComplete', [
                'session_id' => $session->id,
                'whatsapp_number_id' => $whatsappNumber->id,
                'error' => $e->getMessage(),
                'event_data' => $this->eventData
            ]);
            throw $e;
        }
    }
}
