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

        // Verificar permissão
        if ($conversation->company_id !== $user->company_id) {
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
}
