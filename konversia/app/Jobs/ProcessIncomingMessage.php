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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
        $this->onQueue('incoming');
    }

    public function handle(): void
    {
        Log::info('ProcessIncomingMessage job started', [
            'session_id' => $this->sessionId,
            'from' => $this->from,
            'type' => $this->type,
            'message_length' => strlen($this->message ?? ''),
            'metadata_keys' => array_keys($this->metadata)
        ]);

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

            // Processar dados da mídia se for o caso
            $mediaData = $this->processInboundMediaData();

            // Criar mensagem
            Message::create(array_merge([
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
            ], $mediaData));

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

    /**
     * Process inbound media data from metadata
     */
    private function processInboundMediaData(): array
    {
        $mediaData = [];

        // Só processar se for um tipo de mídia
        $mediaTypes = ['image', 'video', 'audio', 'document', 'sticker'];
        if (!in_array($this->type, $mediaTypes)) {
            return $mediaData;
        }

        // Log processing for debugging
        Log::info('Processing inbound media', [
            'type' => $this->type,
            'has_url' => isset($this->metadata['URL']),
            'content_length' => strlen($this->message ?? '')
        ]);

        // Extrair informações comuns
        $mediaData['file_mime_type'] = $this->metadata['mimetype'] ?? null;
        $mediaData['file_size'] = $this->metadata['fileLength'] ?? null;

        // Baixar e armazenar mídia localmente para URLs temporárias do WhatsApp
        if (isset($this->metadata['URL'])) {
            $localFileData = $this->downloadAndStoreMedia($this->metadata['URL']);
            if ($localFileData) {
                $mediaData['file_path'] = $localFileData['path'];
                $mediaData['file_name'] = $localFileData['name'];
                Log::info('Media downloaded and stored locally', [
                    'type' => $this->type,
                    'local_path' => $localFileData['path']
                ]);
            } else {
                // Fallback para URL externa se download falhar
                Log::warning('Download failed, using external URL as fallback', [
                    'type' => $this->type,
                    'external_url' => $this->metadata['URL']
                ]);
                $mediaData['file_path'] = $this->metadata['URL'];
                $mediaData['file_name'] = $this->generateFileName($this->type, $this->metadata);
            }
        }

        // Adicionar metadados específicos por tipo
        $mediaData['media_metadata'] = $this->extractMediaMetadata();

        Log::info('Final media data', [
            'file_path' => $mediaData['file_path'] ?? null,
            'file_name' => $mediaData['file_name'] ?? null
        ]);

        return $mediaData;
    }

    /**
     * Generate a filename for media files
     */
    private function generateFileName(string $type, array $metadata): string
    {
        $extension = match ($metadata['mimetype'] ?? '') {
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
    private function extractMediaMetadata(): array
    {
        $metadata = [];

        switch ($this->type) {
            case 'image':
            case 'sticker':
                if (isset($this->metadata['width']) && isset($this->metadata['height'])) {
                    $metadata = [
                        'width' => $this->metadata['width'],
                        'height' => $this->metadata['height'],
                    ];
                }
                break;

            case 'video':
                $metadata = [
                    'width' => $this->metadata['width'] ?? null,
                    'height' => $this->metadata['height'] ?? null,
                    'duration' => $this->metadata['duration'] ?? null,
                ];
                break;

            case 'audio':
                $metadata = [
                    'duration' => $this->metadata['duration'] ?? null,
                    'voice_note' => $this->metadata['ptt'] ?? false,
                ];
                break;

            case 'document':
                $metadata = [
                    'page_count' => $this->metadata['pageCount'] ?? null,
                    'title' => $this->metadata['title'] ?? $this->metadata['fileName'] ?? null,
                ];
                break;
        }

        // Adicionar informações específicas do sticker se for o caso
        if ($this->type === 'sticker') {
            $metadata = array_merge($metadata, [
                'is_animated' => $this->metadata['isAnimated'] ?? false,
                'is_ai_sticker' => $this->metadata['isAiSticker'] ?? false,
                'accessibility_label' => $this->metadata['accessibilityLabel'] ?? null,
            ]);
        }

        return array_filter($metadata); // Remove null values
    }

    /**
     * Download and store media locally, with decryption when needed
     */
    private function downloadAndStoreMedia(string $url): ?array
    {
        try {
            // Buscar sessão para determinar a empresa
            $session = WhatsAppSession::where('session_id', $this->sessionId)->first();
            if (!$session) {
                return null;
            }

            $company = $session->whatsappNumber->company;

            // Fazer download da mídia
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                Log::warning('Failed to download media from WhatsApp', [
                    'url' => $url,
                    'status' => $response->status(),
                    'type' => $this->type
                ]);
                return null;
            }

            $rawContent = $response->body();

            // Descriptografar se necessário (stickers e algumas mídias)
            $decryptedContent = $this->decryptMediaIfNeeded($rawContent);

            // Gerar nome único para o arquivo
            $extension = $this->getExtensionFromMimeType($this->metadata['mimetype'] ?? 'application/octet-stream');
            $uniqueName = time() . '_' . uniqid() . '.' . $extension;

            // Caminho relativo: whatsapp/inbound/{company_id}/{conversation_id}/
            // Estrutura organizada: whatsapp/inbound/{company_id}/{conversation_id}/{timestamp}_{unique}.{ext}
            $conversation = $this->findOrCreateConversation($session);
            $relativePath = "whatsapp/inbound/{$company->id}/{$conversation->id}/{$uniqueName}";

            // Salvar arquivo
            Storage::disk('public')->put($relativePath, $decryptedContent);

            // Limpeza automática de arquivos antigos (mais de 30 dias)
            $this->cleanupOldMediaFiles($company->id);

            Log::info('Media downloaded, decrypted and stored locally', [
                'type' => $this->type,
                'original_size' => strlen($rawContent),
                'final_size' => strlen($decryptedContent),
                'local_path' => $relativePath,
                'decrypted' => $rawContent !== $decryptedContent,
                'company_id' => $company->id,
                'conversation_id' => $conversation->id
            ]);

            return [
                'path' => $relativePath,
                'name' => $this->generateFileName($this->type, $this->metadata)
            ];

        } catch (\Exception $e) {
            Log::error('Error downloading/storing media', [
                'url' => $url,
                'type' => $this->type,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Decrypt media content when needed (for encrypted WhatsApp media)
     */
    private function decryptMediaIfNeeded(string $content): string
    {
        // Verificar se é mídia criptografada (stickers geralmente são)
        if (isset($this->metadata['mediaKey']) && isset($this->metadata['fileEncSHA256'])) {
            try {
                $decrypted = $this->decryptWhatsAppMedia($content);
                if ($decrypted !== null) {
                    Log::info('Media decrypted successfully', [
                        'type' => $this->type,
                        'original_size' => strlen($content),
                        'decrypted_size' => strlen($decrypted)
                    ]);
                    return $decrypted;
                }
            } catch (\Exception $e) {
                Log::warning('Media decryption failed, using original content', [
                    'type' => $this->type,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $content;
    }

    /**
     * Decrypt WhatsApp encrypted media using mediaKey
     */
    private function decryptWhatsAppMedia(string $encryptedContent): ?string
    {
        try {
            $mediaKey = $this->metadata['mediaKey'] ?? null;
            $fileEncSHA256 = $this->metadata['fileEncSHA256'] ?? null;

            if (!$mediaKey || !$fileEncSHA256) {
                return null;
            }

            // Decode base64 media key
            $mediaKeyDecoded = base64_decode($mediaKey);
            if (!$mediaKeyDecoded || strlen($mediaKeyDecoded) < 32) {
                return null;
            }

            // Extract encryption key and IV (WhatsApp uses AES-256-CBC)
            $encKey = substr($mediaKeyDecoded, 0, 32);
            $iv = substr($mediaKeyDecoded, 32, 16);

            // Decrypt content
            $decrypted = openssl_decrypt(
                $encryptedContent,
                'aes-256-cbc',
                $encKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                Log::warning('OpenSSL decryption failed', [
                    'openssl_error' => openssl_error_string(),
                    'content_length' => strlen($encryptedContent)
                ]);
                return null;
            }

            // Verify integrity if fileEncSHA256 is provided
            if ($fileEncSHA256) {
                $expectedHash = base64_decode($fileEncSHA256);
                $actualHash = hash('sha256', $decrypted, true);

                if (!hash_equals($expectedHash, $actualHash)) {
                    Log::warning('Media integrity check failed', [
                        'type' => $this->type
                    ]);
                    return null;
                }
            }

            return $decrypted;

        } catch (\Exception $e) {
            Log::error('Error in WhatsApp media decryption', [
                'type' => $this->type,
                'error' => $e->getMessage(),
                'has_media_key' => isset($this->metadata['mediaKey']),
                'has_file_enc_sha256' => isset($this->metadata['fileEncSHA256'])
            ]);
            return null;
        }
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
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

        return $extensions[$mimeType] ?? 'file';
    }

    /**
     * Find or create conversation for the message
     */
    private function findOrCreateConversation($session): Conversation
    {
        $contact = Contact::findOrCreateFromWhatsApp(
            $session->whatsappNumber->company,
            $session->whatsappNumber,
            $this->from,
            [
                'name' => $this->metadata['push_name'] ?? null,
                'phone_number' => $this->extractPhoneNumber($this->from),
                'metadata' => $this->metadata
            ]
        );

        $department = $session->whatsappNumber->company->departments()->where('slug', 'geral')->first()
            ?? $session->whatsappNumber->company->departments()->first();

        return Conversation::findOrCreateForContact($contact, $session, $department);
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
            if (!Storage::disk('public')->exists($inboundPath)) {
                return;
            }

            $filesDeleted = 0;
            $totalSizeFreed = 0;

            // Listar todas as conversas da empresa
            $conversationDirs = Storage::disk('public')->directories($inboundPath);

            foreach ($conversationDirs as $conversationDir) {
                $files = Storage::disk('public')->files($conversationDir);

                foreach ($files as $file) {
                    $filePath = $file;
                    $fullPath = storage_path("app/public/{$filePath}");

                    // Verificar se o arquivo existe e é antigo
                    if (file_exists($fullPath) && filemtime($fullPath) < $cutoffDate->timestamp) {
                        $fileSize = filesize($fullPath);
                        Storage::disk('public')->delete($filePath);

                        $filesDeleted++;
                        $totalSizeFreed += $fileSize;

                        Log::info('Old media file deleted', [
                            'file_path' => $filePath,
                            'file_age_days' => now()->diffInDays(filemtime($fullPath)),
                            'file_size' => $fileSize
                        ]);
                    }
                }
            }

            if ($filesDeleted > 0) {
                Log::info('Media cleanup completed', [
                    'company_id' => $companyId,
                    'files_deleted' => $filesDeleted,
                    'total_size_freed_mb' => round($totalSizeFreed / 1024 / 1024, 2)
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Media cleanup failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
