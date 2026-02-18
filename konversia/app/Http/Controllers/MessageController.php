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

        $validated = $request->validate([
            'content' => 'required|string|max:4096',
        ]);

        try {
            DB::beginTransaction();

            // Criar mensagem no banco
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'department_id' => $conversation->department_id,
                'direction' => 'outbound',
                'type' => 'text',
                'content' => $validated['content'],
                'sent_at' => now(),
                'delivery_status' => 'pending',
            ]);

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
}
