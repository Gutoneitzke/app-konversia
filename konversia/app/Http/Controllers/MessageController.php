<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Enviar mensagem em uma conversa
     */
    public function store(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        // Se não há usuário autenticado e é uma requisição Inertia/AJAX, retornar erro adequado
        if (!$user) {
            if ($request->header('X-Inertia') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Não autenticado. Faça login novamente.',
                    'redirect' => route('login')
                ], 401);
            }
            return redirect()->route('login');
        }

        // Verificar permissão
        if ($conversation->company_id !== $user->company_id) {
            if ($request->header('X-Inertia') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Acesso negado a esta conversa.'
                ], 403);
            }
            abort(403);
        }

        // Validar entrada - pode ser texto ou arquivo
        $request->validate([
            'content' => 'required_without:file|string|max:4096',
            'file' => 'nullable|file|max:15360|mimes:jpg,jpeg,png,gif,mp4,mp3,wav,pdf,doc,docx,txt,zip',
        ]);

        $validated = $request->only(['content', 'file']);

        try {
            DB::beginTransaction();

            // Determinar tipo de mensagem e processar conteúdo
            $messageData = $this->processMessageContent($validated, $conversation, $user);

            // Criar mensagem no banco
            $message = Message::create(array_merge([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'department_id' => $conversation->department_id,
                'direction' => 'outbound',
                'sent_at' => now(),
                'delivery_status' => 'pending',
            ], $messageData));

            // Atualizar conversa
            $conversation->update([
                'last_message_at' => now(),
                'status' => $conversation->status === 'pending' ? 'in_progress' : $conversation->status,
                // Se não tiver atendente, atribuir ao usuário que respondeu
                'assigned_to' => $conversation->assigned_to ?? $user->id,
            ]);

            DB::commit();

            // Enviar via WhatsApp Service
            try {
                // Obter numero de destino (do contato)
                $to = $conversation->getContactJid();

                Log::info('MessageController - Enviando mensagem', [
                    'message_id' => $message->id,
                    'conversation_id' => $conversation->id,
                    'contact_jid' => $to,
                    'message_type' => $message->type
                ]);

                $this->whatsappService->sendMessage($message, $to);
            } catch (\Exception $e) {
                Log::error('Erro ao enviar mensagem WhatsApp', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage()
                ]);
                // Não falhamos a requisição se o envio falhar, pois a mensagem já está salva
                // O job de envio tratará o erro e atualizará o status
            }

            return redirect()->back()->with('success', 'Mensagem enviada');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar mensagem', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['content' => 'Erro ao enviar mensagem: ' . $e->getMessage()]);
        }
    }

    /**
     * Reenviar mensagem falhada
     */
    public function retry(Request $request, Message $message)
    {
        $user = $request->user();

        // Verificar permissão
        if ($message->user_id !== $user->id) {
            abort(403, 'Acesso negado a esta mensagem.');
        }

        // Validar se pode reenviar
        if ($message->direction !== 'outbound' || $message->delivery_status !== 'failed') {
            return response()->json([
                'message' => 'Esta mensagem não pode ser reenviada.'
            ], 400);
        }

        // Evita reenvio duplo
        if ($message->delivery_status === 'pending') {
            return response()->json([
                'message' => 'Mensagem já está em tentativa de envio.'
            ], 400);
        }

        try {
            // Marca como pendente
            $message->update([
                'delivery_status' => 'pending',
            ]);

            $conversation = $message->conversation;
            $to = $conversation->getContactJid();

            $this->whatsappService->sendMessage($message, $to);

            return response()->json([
                'message' => 'Mensagem reenviada com sucesso.',
                'data' => $message,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erro ao reenviar mensagem', [
                'message_id' => $message->id,
                'exception' => $e,
            ]);

            $message->update([
                'delivery_status' => 'failed',
            ]);

            return response()->json([
                'message' => 'Não foi possível reenviar a mensagem. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Process message content - can be text or file
     */
    private function processMessageContent(array $validated, $conversation, $user): array
    {
        if (isset($validated['content'])) {
            // Mensagem de texto
            return [
                'type' => 'text',
                'content' => $validated['content'],
            ];
        }

        if (isset($validated['file'])) {
            $file = $validated['file'];

            // Determinar tipo baseado no MIME type
            $mimeType = $file->getMimeType();
            $type = $this->determineMessageType($mimeType);

            // Gerar nome único para o arquivo
            $fileName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $uniqueName = time() . '_' . uniqid() . '.' . $extension;

            // Caminho relativo: whatsapp/{company_id}/{conversation_id}/
            $relativePath = "whatsapp/{$conversation->company_id}/{$conversation->id}/{$uniqueName}";

            // Salvar arquivo
            $file->storeAs("public/whatsapp/{$conversation->company_id}/{$conversation->id}", $uniqueName);

            // Metadados específicos do tipo
            $mediaMetadata = $this->extractMediaMetadata($file, $type);

            return [
                'type' => $type,
                'content' => $validated['content'] ?? null, // caption opcional
                'file_path' => $relativePath,
                'file_name' => $fileName,
                'file_mime_type' => $mimeType,
                'file_size' => $file->getSize(),
                'media_metadata' => $mediaMetadata,
            ];
        }

        throw new \InvalidArgumentException('Conteúdo da mensagem inválido');
    }

    /**
     * Determine message type based on MIME type
     */
    private function determineMessageType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        // Document types
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'application/zip',
            'application/x-zip-compressed',
        ];

        if (in_array($mimeType, $documentTypes)) {
            return 'document';
        }

        // Default to document for unknown types
        return 'document';
    }

    /**
     * Extract media-specific metadata
     */
    private function extractMediaMetadata($file, string $type): array
    {
        $metadata = [];

        try {
            switch ($type) {
                case 'image':
                    $imageInfo = getimagesize($file->getRealPath());
                    if ($imageInfo) {
                        $metadata = [
                            'width' => $imageInfo[0],
                            'height' => $imageInfo[1],
                        ];
                    }
                    break;

                case 'video':
                    // Basic video metadata - could be extended with FFmpeg
                    $metadata = [
                        'duration' => null, // Would need FFmpeg or similar
                    ];
                    break;

                case 'audio':
                    // Basic audio metadata
                    $metadata = [
                        'duration' => null, // Would need audio processing library
                        'voice_note' => false, // Default assumption
                    ];
                    break;

                case 'document':
                    // Document metadata
                    $metadata = [
                        'page_count' => null, // Would need PDF processing library
                        'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    ];
                    break;
            }
        } catch (\Exception $e) {
            // Ignore metadata extraction errors
            Log::warning('Failed to extract media metadata', [
                'error' => $e->getMessage(),
                'type' => $type,
                'file' => $file->getClientOriginalName(),
            ]);
        }

        return $metadata;
    }
}
