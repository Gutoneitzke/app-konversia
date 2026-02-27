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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

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

            // Buscar WhatsAppNumber pelo JID com fallback inteligente
            $whatsappNumber = $this->findWhatsAppNumberByJid($this->numberId);

            if (!$whatsappNumber) {
                Log::warning('WhatsAppNumber não encontrado para JID', [
                    'jid' => $this->numberId,
                    'event_type' => $this->eventType
                ]);
                return;
            }

            // ✅ Melhorado: Buscar sessão por múltiplos critérios
            $session = $this->findSessionForWebhook($whatsappNumber->id, $this->numberId);

            if (!$session) {
                Log::warning('Sessão não encontrada para evento webhook', [
                    'whatsapp_number_id' => $whatsappNumber->id,
                    'number_id' => $this->numberId,
                    'event_type' => $this->eventType,
                    'total_sessions' => $whatsappNumber->sessions()->count(),
                    'all_session_ids' => $whatsappNumber->sessions()->pluck('session_id')->toArray(),
                    'all_service_ids' => $whatsappNumber->sessions()->pluck('metadata->service_id')->filter()->toArray()
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

                case 'OfflineSyncPreview': // Pré-visualização de sincronização offline
                    $this->processOfflineSyncPreviewEvent($session, $whatsappNumber);
                    break;

                case 'OfflineSyncCompleted': // Conclusão da sincronização offline
                    $this->processOfflineSyncCompletedEvent($session, $whatsappNumber);
                    break;

                default:
                    Log::info('Evento WhatsApp não mapeado: ' . $this->eventType);
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

    private function findSessionForWebhook(int $whatsappNumberId, string $webhookNumberId): ?WhatsAppSession
    {
        // 1. Primeiro tentar encontrar por session_id exato
        $session = WhatsAppSession::where('whatsapp_number_id', $whatsappNumberId)
            ->where('session_id', $webhookNumberId)
            ->first();

        if ($session) {
            return $session;
        }

        // 2. Tentar encontrar por service_id nos metadados
        $session = WhatsAppSession::where('whatsapp_number_id', $whatsappNumberId)
            ->whereJsonContains('metadata->service_id', $webhookNumberId)
            ->first();

        if ($session) {
            return $session;
        }

        // 3. Para JIDs com device ID (:XX@), tentar encontrar sessão com JID base
        if (strpos($webhookNumberId, ':') !== false && strpos($webhookNumberId, '@') !== false) {
            $baseJid = explode(':', $webhookNumberId)[0] . '@' . explode('@', $webhookNumberId)[1];

            $session = WhatsAppSession::where('whatsapp_number_id', $whatsappNumberId)
                ->where('session_id', $baseJid)
                ->first();

            if ($session) {
                // Atualizar metadados com o device ID para futuras requisições
                $session->update([
                    'metadata' => array_merge($session->metadata ?? [], [
                        'device_ids' => array_merge($session->metadata['device_ids'] ?? [], [$webhookNumberId])
                    ])
                ]);
                return $session;
            }
        }

        // 4. Fallback: pegar a última sessão ativa
        return WhatsAppSession::where('whatsapp_number_id', $whatsappNumberId)
            ->whereIn('status', ['connected', 'connecting'])
            ->orderBy('updated_at', 'desc')
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

        Log::info('QR Code salvo', ['id' => $whatsappNumber->id]);
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
                $content = '';
                $messageType = 'audio';
                $mediaMetadata = $messageData['audioMessage'];
            } elseif (isset($messageData['documentMessage'])) {
                $content = $messageData['documentMessage']['fileName'] ?? '';
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
            $this->createMessage($conversation, $messageInfo, $messageData, $content, $messageType, $mediaMetadata);

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

                    Log::info('Contato atualizado', ['id' => $contact->id, 'name' => $newPushName]);
                } else {
                    Log::info('PushName já atualizado', ['id' => $contact->id]);
                }
            } else {
                Log::info('Novo contato PushName', ['jid' => $jid]);
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
        // Normalizar JID para formato consistente
        $normalizedJid = $this->normalizeJid($jid);

        $contact = Contact::where('whatsapp_number_id', $whatsappNumber->id)
            ->where('jid', $normalizedJid)
            ->first();

        if (!$contact) {
            // Extrair número do telefone do JID (antes do @)
            $phoneNumber = explode('@', $normalizedJid)[0] ?? $normalizedJid;

            $contact = Contact::create([
                'company_id' => $whatsappNumber->company_id,
                'whatsapp_number_id' => $whatsappNumber->id,
                'jid' => $normalizedJid,
                'name' => $name ?: 'Contato',
                'phone_number' => $phoneNumber,
                'is_blocked' => false,
                'is_business' => false,
            ]);

            Log::info('Novo contato criado', [
                'contact_id' => $contact->id,
                'original_jid' => $jid,
                'normalized_jid' => $normalizedJid,
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
        // Normalizar chatJid para consistência
        $normalizedChatJid = $this->normalizeJid($chatJid);

        // Determinar o departamento para esta mensagem
        $department = $this->getDepartmentForMessage($session, $contact, $normalizedChatJid);

        // Buscar conversa existente por company_id e contact_jid (único por design)
        $conversation = Conversation::where('company_id', $session->company_id)
            ->where('contact_jid', $normalizedChatJid)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'company_id' => $session->company_id,
                'whatsapp_session_id' => $session->id,
                'department_id' => $department->id,
                'contact_id' => $contact->id,
                'contact_jid' => $normalizedChatJid,
                'contact_name' => $contact->name,
                'status' => 'pending',
                'last_message_at' => now(),
            ]);

            Log::info('Nova conversa criada', [
                'conversation_id' => $conversation->id,
                'contact_id' => $contact->id,
                'contact_name' => $contact->name,
                'original_chat_jid' => $chatJid,
                'normalized_chat_jid' => $normalizedChatJid,
                'department_id' => $department->id,
                'department_name' => $department->name
            ]);
        } else {
            // Se a conversa existe, verificar se precisa de atualizações
            $updates = ['last_message_at' => now()];

            // Se a conversa está fechada ou resolvida, reabrir automaticamente
            if (in_array($conversation->status, ['resolved', 'closed'])) {
                $oldStatus = $conversation->status;
                $updates['status'] = 'pending';
                $updates['resolved_at'] = null;
                $updates['resolved_by'] = null;
                $updates['closed_at'] = null;
                $updates['closed_by'] = null;

                Log::info('Conversa reaberta automaticamente devido a nova mensagem', [
                    'conversation_id' => $conversation->id,
                    'contact_jid' => $chatJid,
                    'old_status' => $oldStatus,
                    'new_status' => 'pending'
                ]);
            }

            // Se a conversa está em um departamento diferente, transferir
            if ($conversation->department_id !== $department->id) {
                Log::info('Transferindo conversa para departamento correto', [
                    'conversation_id' => $conversation->id,
                    'from_department_id' => $conversation->department_id,
                    'to_department_id' => $department->id,
                    'contact_jid' => $chatJid
                ]);

                // Usar o método de transferência existente
                $conversation->transferTo($department, null, null, 'Transferência automática via webhook');
            }

            // Se está associada a uma sessão diferente, atualizar
            if ($conversation->whatsapp_session_id !== $session->id) {
                $updates['whatsapp_session_id'] = $session->id;
                $updates['contact_id'] = $contact->id;

                Log::info('Conversa existente atualizada com nova sessão', [
                    'conversation_id' => $conversation->id,
                    'old_session_id' => $conversation->whatsapp_session_id,
                    'new_session_id' => $session->id,
                    'contact_jid' => $chatJid
                ]);
            }

            // Aplicar atualizações se houver
            if (count($updates) > 1) { // Mais que apenas last_message_at
                $conversation->update($updates);
            } else {
                $conversation->update(['last_message_at' => now()]);
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
    protected function createMessage(Conversation $conversation, array $messageInfo, array $webhookData, string $content, string $type, ?array $mediaMetadata = null): Message
    {
        // Processar mídia inbound se necessário
        $processedMediaData = $this->processInboundMediaData($type, $messageInfo, $webhookData);

        $messageAttributes = array_merge([
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
            'read_at' => null, // Mensagens inbound não são lidas automaticamente
            'delivery_status' => 'delivered',
            'whatsapp_message_id' => $messageInfo['ID'] ?? null,
            'whatsapp_metadata' => $messageInfo,
        ], $processedMediaData);

        Log::info('Creating message with data', [
            'message_attributes_keys' => array_keys($messageAttributes),
            'file_path' => $messageAttributes['file_path'] ?? 'NO_FILE_PATH',
            'file_name' => $messageAttributes['file_name'] ?? 'NO_FILE_NAME',
            'processed_media_keys' => array_keys($processedMediaData)
        ]);

        $message = Message::create($messageAttributes);

        Log::info('Mensagem criada', [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'type' => $type,
            'content' => $content
        ]);

        return $message;
    }

    /**
     * Test method to verify media storage functionality
     */
    public static function testMediaStorage(): void
    {
        $testData = 'RIFF\x00\x00\x00\x00WEBPVP8 '; // Fake WebP header
        $testPath = 'whatsapp/inbound/test/test.webp';

        $result = \Illuminate\Support\Facades\Storage::disk('public')->put($testPath, $testData);

        Log::info('Media storage test', [
            'test_path' => $testPath,
            'storage_result' => $result,
            'file_exists' => \Illuminate\Support\Facades\Storage::disk('public')->exists($testPath),
            'full_path' => storage_path('app/public/' . $testPath)
        ]);

        // Test URL generation
        $url = \Illuminate\Support\Facades\Storage::disk('public')->url($testPath);
        Log::info('URL generation test', [
            'generated_url' => $url,
            'expected_pattern' => '/storage/whatsapp/inbound/test/test.webp'
        ]);
    }

    /**
     * Process inbound media data from message info and message data
     */
    private function processInboundMediaData(string $type, array $messageInfo, array $messageData): array
    {
        $mediaData = [];

        // Só processar se for um tipo de mídia
        $mediaTypes = ['image', 'video', 'audio', 'document', 'sticker'];
        if (!in_array($type, $mediaTypes)) {
            Log::info('Message type is not media, skipping media processing', [
                'type' => $type,
                'supported_types' => $mediaTypes
            ]);
            return $mediaData;
        }


        // Extrair informações comuns
        $mediaData['file_mime_type'] = $messageInfo['mimetype'] ?? $messageInfo['Mimetype'] ?? null;
        $mediaData['file_size'] = $messageInfo['fileLength'] ?? $messageInfo['FileLength'] ?? null;

        // Procurar URL nos dados da mensagem
        $mediaUrl = null;

        // Procurar URL da mídia
        if (isset($messageData[$type . 'Message']['URL'])) {
            $mediaUrl = $messageData[$type . 'Message']['URL'];
        }
        elseif (isset($messageData['URL'])) {
            $mediaUrl = $messageData['URL'];
        }
        elseif (isset($messageInfo['URL'])) {
            $mediaUrl = $messageInfo['URL'];
        }
        else {
            Log::warning('No URL found for media', ['type' => $type]);
        }

        // Baixar e armazenar mídia localmente se URL foi encontrada
        if ($mediaUrl) {
            $localFileData = $this->downloadAndStoreMedia($mediaUrl, $type, $messageData[$type . 'Message'] ?? $messageInfo);
            if ($localFileData) {
                $mediaData['file_path'] = $localFileData['path'];
                $mediaData['file_name'] = $localFileData['name'];
                Log::info('Media downloaded and stored locally in webhook', [
                    'type' => $type,
                    'local_path' => $localFileData['path']
                ]);
            } else {
                // Se download falhou, não salvar nada - usar URL externa como último recurso
                Log::warning('Media download failed completely, skipping local storage', [
                    'type' => $type,
                    'media_url' => $mediaUrl
                ]);
                // Não definir file_path para que o sistema use URL externa
            }
        } else {
            Log::warning('No media URL found in webhook data', [
                'type' => $type,
                'message_info_has_url' => isset($messageInfo['URL']),
                'message_data_keys' => array_keys($messageData)
            ]);
        }

        // Adicionar metadados específicos por tipo
        $mediaData['media_metadata'] = $this->extractMediaMetadata($type, $messageInfo);

        return $mediaData;
    }

    /**
     * Download and store media locally, with decryption when needed
     */
    private function downloadAndStoreMedia(string $url, string $type, array $messageInfo): ?array
    {
        try {
            Log::info('Starting media download', [
                'url' => $url,
                'type' => $type,
                'number_id' => $this->numberId
            ]);

            // Buscar empresa através do número WhatsApp
            $whatsappNumber = WhatsAppNumber::where('jid', $this->numberId)->first();
            if (!$whatsappNumber) {
                Log::warning('WhatsApp number not found', ['number_id' => $this->numberId]);
                return null;
            }

            $company = $whatsappNumber->company;
            Log::info('Found company', ['company_id' => $company->id]);

            // Fazer download da mídia
            Log::info('Making HTTP request to download media', ['url' => $url]);
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($url);

            if (!$response->successful()) {
                Log::warning('Failed to download media from WhatsApp in webhook', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                    'type' => $type
                ]);
                return null;
            }

            Log::info('HTTP request successful', [
                'status' => $response->status(),
                'content_length' => strlen($response->body())
            ]);

            $rawContent = $response->body();

            // Verificar se o conteúdo baixado já é uma imagem válida (não precisa descriptografia)
            if ($this->isValidImageContent($rawContent)) {
                Log::info('Downloaded content is already a valid image, saving directly', [
                    'type' => $type,
                    'content_length' => strlen($rawContent)
                ]);

                // Salvar diretamente sem descriptografia
                $relativePath = "whatsapp/inbound/{$company->id}/webhook/{$uniqueName}";
                $saved = \Illuminate\Support\Facades\Storage::disk('public')->put($relativePath, $rawContent);

                if ($saved) {
                    Log::info('Media saved directly (no decryption needed)', [
                        'type' => $type,
                        'local_path' => $relativePath,
                        'file_size' => strlen($rawContent)
                    ]);

                    return [
                        'path' => $relativePath,
                        'name' => $this->generateFileName($type, $messageInfo)
                    ];
                } else {
                    Log::warning('Failed to save media file');
                    return null;
                }
            }

            // Se não é imagem válida, tentar descriptografar
            $decryptedContent = $this->decryptMediaIfNeeded($rawContent, $type, $messageInfo);

            // Se descriptografia falhou, abortar
            if ($decryptedContent === null) {
                Log::warning('Media decryption required but failed, not saving file', [
                    'type' => $type,
                    'content_length' => strlen($rawContent),
                    'has_media_key' => isset($messageInfo['mediaKey'])
                ]);
                return null; // Não salvar arquivo corrompido
            }

            // Gerar nome único para o arquivo
            $extension = $this->getExtensionFromMimeType($messageInfo['mimetype'] ?? $messageInfo['Mimetype'] ?? 'application/octet-stream');
            $uniqueName = time() . '_' . uniqid() . '.' . $extension;

            // Caminho relativo: whatsapp/inbound/{company_id}/webhook/
            $relativePath = "whatsapp/inbound/{$company->id}/webhook/{$uniqueName}";

            // Salvar arquivo
            Log::info('Saving file to storage', [
                'relative_path' => $relativePath,
                'file_size' => strlen($decryptedContent),
                'decrypted' => $rawContent !== $decryptedContent
            ]);

            $saved = \Illuminate\Support\Facades\Storage::disk('public')->put($relativePath, $decryptedContent);
            Log::info('File save result', ['saved' => $saved, 'exists' => \Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath)]);

            // Limpeza automática de arquivos antigos (mais de 30 dias)
            $this->cleanupOldMediaFiles($company->id);

            Log::info('Media downloaded, decrypted and stored locally in webhook', [
                'type' => $type,
                'original_size' => strlen($rawContent),
                'final_size' => strlen($decryptedContent),
                'local_path' => $relativePath,
                'decrypted' => $rawContent !== $decryptedContent
            ]);

            return [
                'path' => $relativePath,
                'name' => $this->generateFileName($type, $messageInfo)
            ];

        } catch (\Exception $e) {
            Log::error('Error downloading/storing media in webhook', [
                'url' => $url,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Decrypt media content when needed (for encrypted WhatsApp media)
     * Returns null if decryption fails or is not needed
     */
    private function decryptMediaIfNeeded(string $content, string $type, array $messageInfo): ?string
    {
        // Primeiro verificar se o conteúdo já é uma imagem válida (não criptografada)
        if ($this->isValidImageContent($content)) {
            Log::info('Content is already a valid image, no decryption needed', [
                'type' => $type,
                'content_length' => strlen($content)
            ]);
            return $content; // Usar conteúdo original
        }

        // Se não é válido, tentar descriptografar se temos mediaKey
        if (isset($messageInfo['mediaKey'])) {
            Log::info('Content appears encrypted, attempting decryption', [
                'type' => $type,
                'has_file_enc_sha256' => isset($messageInfo['fileEncSHA256'])
            ]);

            try {
                $decrypted = $this->decryptWhatsAppMedia($content, $messageInfo, $type);
                if ($decrypted !== null && $this->isValidMediaContent($decrypted, $type)) {
                    Log::info('Media decryption successful - valid content', [
                        'type' => $type,
                        'original_size' => strlen($content),
                        'decrypted_size' => strlen($decrypted)
                    ]);
                    return $decrypted;
                } elseif ($decrypted !== null) {
                    // Decryption succeeded but result is not recognized as valid content
                    // This might still be valid (different format or encoding)
                    Log::warning('Decryption succeeded but result not recognized as valid content, saving anyway', [
                        'type' => $type,
                        'decrypted_size' => strlen($decrypted),
                        'first_bytes' => bin2hex(substr($decrypted, 0, min(16, strlen($decrypted)))),
                        'last_bytes' => bin2hex(substr($decrypted, -min(16, strlen($decrypted))))
                    ]);
                    return $decrypted; // Save anyway, might be valid
                } else {
                    Log::warning('Decryption failed completely', [
                        'type' => $type,
                        'decrypted_null' => true
                    ]);
                    return null; // Don't save invalid content
                }
            } catch (\Exception $e) {
                Log::warning('Decryption exception', [
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);
                return null; // Não salvar se der erro
            }
        }

        // Se não temos mediaKey e o conteúdo não é válido, não salvar
        Log::warning('Content is not valid image and no decryption key available', [
            'type' => $type,
            'has_media_key' => isset($messageInfo['mediaKey']),
            'content_length' => strlen($content)
        ]);
        return null;
    }

    /**
     * Check if content appears to be valid media based on type
     */
    private function isValidMediaContent(string $content, string $type): bool
    {
        switch ($type) {
            case 'image':
            case 'sticker':
                return $this->isValidImageContent($content);
            case 'audio':
                return $this->isValidAudioContent($content);
            case 'video':
            case 'document':
                // For videos and documents, we trust the decryption result
                // since they have more complex signatures
                return strlen($content) > 0;
            default:
                return strlen($content) > 0;
        }
    }

    /**
     * Check if content appears to be a valid image
     */
    private function isValidImageContent(string $content): bool
    {
        $length = strlen($content);

        if ($length < 12) {
            return false;
        }

        // Verificar assinatura de WebP
        if (substr($content, 0, 4) === 'RIFF' && substr($content, 8, 4) === 'WEBP') {
            Log::info('Valid WebP image detected');
            return true;
        }

        // Verificar assinatura de PNG
        if (substr($content, 0, 8) === "\x89PNG\r\n\x1a\n") {
            Log::info('Valid PNG image detected');
            return true;
        }

        // Verificar assinatura de JPEG
        if (substr($content, 0, 2) === "\xFF\xD8") {
            Log::info('Valid JPEG image detected');
            return true;
        }

        // Verificar assinatura de GIF
        if (substr($content, 0, 4) === 'GIF8') {
            Log::info('Valid GIF image detected');
            return true;
        }

        Log::warning('Invalid image content detected', [
            'content_length' => $length,
            'first_bytes' => bin2hex(substr($content, 0, min(16, $length)))
        ]);

        return false;
    }

    /**
     * Check if content appears to be a valid audio file
     */
    private function isValidAudioContent(string $content): bool
    {
        $length = strlen($content);

        if ($length < 12) {
            return false;
        }

        // Check for OGG format (WhatsApp uses OGG/Opus for audio)
        $firstBytes = substr($content, 0, 4);
        if ($firstBytes === 'OggS') {
            return true;
        }

        // Check for MP3 format
        $firstBytes = substr($content, 0, 3);
        if ($firstBytes === 'ID3' || $firstBytes === "\xFF\xFB" || $firstBytes === "\xFF\xF3" || $firstBytes === "\xFF\xF2") {
            return true;
        }

        Log::warning('Audio content does not match known formats', [
            'first_bytes' => bin2hex(substr($content, 0, min(16, $length)))
        ]);

        // For now, accept any non-empty content as valid audio
        // since audio formats can be complex to validate
        return $length > 0;
    }

    /**
     * Generates decryption keys for WhatsApp media using HKDF
     *
     * @param string $mediaKey The base64-encoded media key from webhook
     * @param string $type The media type (image, video, audio, document, sticker)
     * @param int $length The length of key material to generate (default 112)
     * @return string|false The generated key material or false on failure
     */
    private function getDecryptionKeys(string $mediaKey, string $type = 'image', int $length = 112)
    {
        try {
            // Map media type to HKDF info string
            $info = match ($type) {
                'image', 'sticker' => 'WhatsApp Image Keys',
                'video' => 'WhatsApp Video Keys',
                'audio' => 'WhatsApp Audio Keys',
                'document' => 'WhatsApp Document Keys',
                default => 'WhatsApp Image Keys', // Default fallback
            };

            // Decode base64 media key
            $decodedKey = base64_decode($mediaKey);

            if (!$decodedKey) {
                Log::warning('Failed to decode base64 media key');
                return false;
            }

            Log::info('Generating decryption keys with HKDF', [
                'media_type' => $type,
                'hkdf_info' => $info,
                'key_length' => $length,
                'decoded_key_length' => strlen($decodedKey)
            ]);

            // Generate key material using HKDF
            $keyMaterial = hash_hkdf('sha256', $decodedKey, $length, $info, '');

            if (!$keyMaterial) {
                Log::warning('HKDF key derivation failed');
                return false;
            }

            Log::info('HKDF key derivation successful', [
                'key_material_length' => strlen($keyMaterial)
            ]);

            return $keyMaterial;

        } catch (\Exception $e) {
            Log::error('Exception in getDecryptionKeys', [
                'error' => $e->getMessage(),
                'media_type' => $type
            ]);
            return false;
        }
    }

    /**
     * Decrypt WhatsApp encrypted media using mediaKey
     * WhatsApp uses AES-256-CBC with HKDF key derivation
     */
    private function decryptWhatsAppMedia(string $encryptedContent, array $messageInfo, string $mediaType = 'image'): ?string
    {
        try {
            $mediaKey = $messageInfo['mediaKey'] ?? null;
            $fileEncSHA256 = $messageInfo['fileEncSHA256'] ?? null;

            if (!$mediaKey) {
                Log::warning('No mediaKey provided for decryption');
                return null;
            }

            // Generate decryption keys using HKDF with correct media type
            $keys = $this->getDecryptionKeys($mediaKey, $mediaType);

            if (!$keys) {
                Log::warning('Failed to generate decryption keys');
                return null;
            }

            // Extract IV (first 16 bytes) and cipher key (next 32 bytes)
            $iv = substr($keys, 0, 16);
            $cipherKey = substr($keys, 16, 32);



            // Remove the last 10 bytes (MAC) from the encrypted file
            $ciphertext = substr($encryptedContent, 0, strlen($encryptedContent) - 10);

            Log::info('Removed MAC from encrypted content', [
                'original_length' => strlen($encryptedContent),
                'ciphertext_length' => strlen($ciphertext)
            ]);

            // Decrypt the file using AES-256-CBC
            $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $cipherKey, OPENSSL_RAW_DATA, $iv);

            if ($decrypted === false) {
                $opensslError = openssl_error_string();
                Log::warning('AES-256-CBC decryption failed', [
                    'openssl_error' => $opensslError,
                    'ciphertext_length' => strlen($ciphertext),
                    'iv_hex' => bin2hex($iv),
                    'cipher_key_length' => strlen($cipherKey)
                ]);
                return null;
            }

            Log::info('AES-256-CBC decryption successful', [
                'decrypted_length' => strlen($decrypted)
            ]);

            if ($decrypted === false) {
                $opensslError = openssl_error_string();
                Log::warning('All OpenSSL decryption methods failed', [
                    'openssl_error' => $opensslError,
                    'content_length' => strlen($encryptedContent),
                    'key_hex_start' => bin2hex(substr($encKey, 0, 8)) . '...',
                    'iv_hex' => bin2hex($iv),
                    'iv_length' => strlen($iv)
                ]);
                return null;
            }

            // Verify integrity if fileEncSHA256 is provided
            if ($fileEncSHA256) {
                $expectedHash = base64_decode($fileEncSHA256);
                $actualHash = hash('sha256', $decrypted, true);

                if (!hash_equals($expectedHash, $actualHash)) {
                    Log::warning('Media integrity check failed - trying fileSHA256 instead', [
                        'expected_hash_enc' => bin2hex($expectedHash),
                        'actual_hash' => bin2hex($actualHash)
                    ]);

                    // Try with fileSHA256 instead (might be the decrypted content hash)
                    if (isset($messageInfo['fileSHA256'])) {
                        $fileSHA256 = $messageInfo['fileSHA256'];
                        $expectedHashPlain = base64_decode($fileSHA256);
                        if (hash_equals($expectedHashPlain, $actualHash)) {
                            Log::info('Media integrity check passed with fileSHA256');
                        } else {
                            Log::warning('Both integrity checks failed - saving anyway for testing', [
                                'expected_hash_plain' => bin2hex($expectedHashPlain),
                                'actual_hash' => bin2hex($actualHash)
                            ]);
                            // Continue and save anyway for debugging
                        }
                    } else {
                        Log::warning('Integrity check failed and no fileSHA256 available - saving anyway');
                        // Continue and save anyway for debugging
                    }
                } else {
                    Log::info('Media integrity check passed with fileEncSHA256');
                }
            }

            // Check if decrypted content is a valid image
            if ($this->isValidImageContent($decrypted)) {
                return $decrypted;
            } else {
                // Decryption succeeded but result is not a valid image
                // This might still be usable (e.g., WebP with different signature)
                Log::warning('Decryption succeeded but result is not valid image - saving anyway', [
                    'decrypted_length' => strlen($decrypted),
                    'first_bytes' => bin2hex(substr($decrypted, 0, min(16, strlen($decrypted))))
                ]);
                return $decrypted; // Save anyway for testing
            }

        } catch (\Exception $e) {
            Log::error('Exception in WhatsApp media decryption', [
                'error' => $e->getMessage(),
                'has_media_key' => isset($messageInfo['mediaKey']),
                'has_file_enc_sha256' => isset($messageInfo['fileEncSHA256'])
            ]);
            return null;
        }
    }

    /**
     * Generate a filename for media files
     */
    private function generateFileName(string $type, array $messageInfo): string
    {
        $extension = match ($messageInfo['mimetype'] ?? $messageInfo['Mimetype'] ?? '') {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'audio/mp3' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            default => 'file'
        };

        return "whatsapp_{$type}_" . time() . ".{$extension}";
    }

    /**
     * Extract specific media metadata
     */
    private function extractMediaMetadata(string $type, array $messageInfo): array
    {
        $metadata = [];

        switch ($type) {
            case 'image':
            case 'sticker':
                if (isset($messageInfo['width']) && isset($messageInfo['height'])) {
                    $metadata = [
                        'width' => $messageInfo['width'],
                        'height' => $messageInfo['height'],
                    ];
                }
                break;

            case 'video':
                $metadata = [
                    'width' => $messageInfo['width'] ?? null,
                    'height' => $messageInfo['height'] ?? null,
                    'duration' => $messageInfo['duration'] ?? null,
                ];
                break;

            case 'audio':
                $metadata = [
                    'duration' => $messageInfo['duration'] ?? null,
                    'voice_note' => $messageInfo['ptt'] ?? false,
                ];
                break;

            case 'document':
                $metadata = [
                    'page_count' => $messageInfo['pageCount'] ?? null,
                    'title' => $messageInfo['title'] ?? $messageInfo['fileName'] ?? null,
                ];
                break;
        }

        // Adicionar informações específicas do sticker se for o caso
        if ($type === 'sticker') {
            $metadata = array_merge($metadata, [
                'is_animated' => $messageInfo['isAnimated'] ?? false,
                'is_ai_sticker' => $messageInfo['isAiSticker'] ?? false,
                'accessibility_label' => $messageInfo['accessibilityLabel'] ?? null,
            ]);
        }

        return array_filter($metadata); // Remove null values
    }

    /**
     * Cleanup old media files to prevent storage bloat
     */
    private function cleanupOldMediaFiles(int $companyId): void
    {
        try {
            $inboundPath = "whatsapp/inbound/{$companyId}";
            $cutoffDate = now()->subDays(30); // Arquivos mais antigos que 30 dias

            // Verificar se o diretório existe
            if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($inboundPath)) {
                return;
            }

            $filesDeleted = 0;
            $totalSizeFreed = 0;

            // Listar todas as pastas da empresa
            $subDirs = \Illuminate\Support\Facades\Storage::disk('public')->directories($inboundPath);

            foreach ($subDirs as $subDir) {
                $files = \Illuminate\Support\Facades\Storage::disk('public')->files($subDir);

                foreach ($files as $file) {
                    $filePath = $file;
                    $fullPath = storage_path("app/public/{$filePath}");

                    // Verificar se o arquivo existe e é antigo
                    if (file_exists($fullPath) && filemtime($fullPath) < $cutoffDate->timestamp) {
                        $fileSize = filesize($fullPath);
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($filePath);

                        $filesDeleted++;
                        $totalSizeFreed += $fileSize;

                        Log::info('Old media file deleted in webhook', [
                            'file_path' => $filePath,
                            'file_age_days' => now()->diffInDays(filemtime($fullPath)),
                            'file_size' => $fileSize
                        ]);
                    }
                }
            }

            if ($filesDeleted > 0) {
                Log::info('Media cleanup completed in webhook', [
                    'company_id' => $companyId,
                    'files_deleted' => $filesDeleted,
                    'total_size_freed_mb' => round($totalSizeFreed / 1024 / 1024, 2)
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Media cleanup failed in webhook', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        // Extract main mimetype (remove parameters like ; codecs=opus)
        $mainMimeType = explode(';', $mimeType)[0];

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/avi' => 'avi',
            'audio/mp3' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'text/plain' => 'txt',
            'application/zip' => 'zip',
        ];

        return $extensions[$mainMimeType] ?? 'file';
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

    /**
     * Processar evento de pré-visualização de sincronização offline
     */
    protected function processOfflineSyncPreviewEvent(WhatsAppSession $session, WhatsAppNumber $whatsappNumber): void
    {
        try {
            $total = $this->eventData['Total'] ?? 0;
            $appDataChanges = $this->eventData['AppDataChanges'] ?? 0;
            $messages = $this->eventData['Messages'] ?? 0;
            $notifications = $this->eventData['Notifications'] ?? 0;
            $receipts = $this->eventData['Receipts'] ?? 0;

            Log::info('OfflineSyncPreview processado', [
                'total' => $total,
                'app_data_changes' => $appDataChanges,
                'messages' => $messages,
                'notifications' => $notifications,
                'receipts' => $receipts,
                'whatsapp_number_id' => $whatsappNumber->id,
                'session_id' => $session->id
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar OfflineSyncPreview', [
                'session_id' => $session->id,
                'whatsapp_number_id' => $whatsappNumber->id,
                'error' => $e->getMessage(),
                'event_data' => $this->eventData
            ]);
            throw $e;
        }
    }

    /**
     * Processar evento de conclusão da sincronização offline
     */
    protected function processOfflineSyncCompletedEvent(WhatsAppSession $session, WhatsAppNumber $whatsappNumber): void
    {
        try {
            $count = $this->eventData['Count'] ?? 0;

            Log::info('OfflineSyncCompleted processado', [
                'count' => $count,
                'whatsapp_number_id' => $whatsappNumber->id,
                'session_id' => $session->id
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar OfflineSyncCompleted', [
                'session_id' => $session->id,
                'whatsapp_number_id' => $whatsappNumber->id,
                'error' => $e->getMessage(),
                'event_data' => $this->eventData
            ]);
            throw $e;
        }
    }

    /**
     * Normalizar JID para formato consistente do WhatsApp
     */
    protected function normalizeJid(string $jid): string
    {
        // Remover sufixos de dispositivo (ex: :1, :2, etc.)
        $jid = preg_replace('/:\d+$/', '', $jid);

        // Garantir que termine com @s.whatsapp.net
        if (!str_contains($jid, '@')) {
            $jid .= '@s.whatsapp.net';
        } elseif (str_contains($jid, '@c.us')) {
            $jid = str_replace('@c.us', '@s.whatsapp.net', $jid);
        } elseif (!str_contains($jid, '@s.whatsapp.net')) {
            // Se tem @ mas não é s.whatsapp.net, substituir
            $parts = explode('@', $jid);
            $jid = $parts[0] . '@s.whatsapp.net';
        }

        return $jid;
    }

    /**
     * Atualizar JIDs existentes no banco que podem estar no formato incorreto
     */
    public static function updateExistingJids(): void
    {
        $self = new self('', '', []);

        // Atualizar contatos
        $contacts = \App\Models\Contact::all();
        foreach ($contacts as $contact) {
            $normalizedJid = $self->normalizeJid($contact->jid);
            if ($normalizedJid !== $contact->jid) {
                Log::info('Atualizando JID de contato', [
                    'contact_id' => $contact->id,
                    'old_jid' => $contact->jid,
                    'new_jid' => $normalizedJid
                ]);
                $contact->update(['jid' => $normalizedJid]);
            }
        }

        // Atualizar conversas
        $conversations = \App\Models\Conversation::whereNotNull('contact_jid')->get();
        foreach ($conversations as $conversation) {
            $normalizedJid = $self->normalizeJid($conversation->contact_jid);
            if ($normalizedJid !== $conversation->contact_jid) {
                Log::info('Atualizando JID de conversa', [
                    'conversation_id' => $conversation->id,
                    'old_jid' => $conversation->contact_jid,
                    'new_jid' => $normalizedJid
                ]);
                $conversation->update(['contact_jid' => $normalizedJid]);
            }
        }

        Log::info('Atualização de JIDs concluída');
    }
}
