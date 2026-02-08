<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use App\Jobs\UpdateConnectionStatus;
use App\Models\WhatsAppSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    /**
     * Receber QR Code do WhatsApp
     */
    public function receiveQR(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $qr = $request->input('qr');

            if (!$sessionId || !$qr) {
                return response()->json(['error' => 'Dados inválidos'], 400);
            }

            // Buscar sessão pelo session_id
            $session = WhatsAppSession::where('session_id', $sessionId)->first();

            if (!$session) {
                return response()->json(['error' => 'Sessão não encontrada'], 404);
            }

            // Atualizar status e salvar QR
            $session->update([
                'status' => 'connecting',
                'metadata' => array_merge($session->metadata ?? [], [
                    'qr_code' => $qr,
                    'qr_generated_at' => now()->toIso8601String()
                ])
            ]);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Erro ao receber QR', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => 'Erro ao processar QR'], 500);
        }
    }

    /**
     * Receber mensagem do WhatsApp
     */
    public function receiveMessage(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $from = $request->input('from');
            $message = $request->input('message');
            $messageId = $request->input('message_id');
            $type = $request->input('type', 'text');
            $metadata = $request->input('metadata', []);

            if (!$sessionId || !$from) {
                return response()->json(['error' => 'Dados inválidos'], 400);
            }

            // Processar mensagem em background
            ProcessIncomingMessage::dispatch(
                $sessionId,
                $from,
                $message,
                $messageId,
                $type,
                $metadata
            );

            return response()->json(['status' => 'queued']);

        } catch (\Exception $e) {
            Log::error('Erro ao receber mensagem', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => 'Erro ao processar mensagem'], 500);
        }
    }

    /**
     * Atualizar status de conexão
     */
    public function updateStatus(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $status = $request->input('status');
            $error = $request->input('error');

            if (!$sessionId || !$status) {
                return response()->json(['error' => 'Dados inválidos'], 400);
            }

            // Processar status em background
            UpdateConnectionStatus::dispatch($sessionId, $status, $error);

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar status', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => 'Erro ao atualizar status'], 500);
        }
    }
}
