<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppWebhookEvent;
use App\Models\WhatsAppNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    /**
     * Receber eventos do serviço WhatsApp Go via webhook
     *
     * Formato esperado:
     * {
     *   "ID": "session_id",
     *   "Type": "event_type",
     *   "Data": {...}
     * }
     */
    public function receiveEvent(Request $request)
    {
        try {
            $numberId = $request->input('ID');
            $eventType = $request->input('Type');
            $eventData = $request->input('Data', []);

            if (!$numberId || !$eventType) {
                Log::warning('Dados inválidos no webhook WhatsApp', $request->all());
                return response()->json(['error' => 'Dados inválidos'], 400);
            }

            Log::info('Evento WhatsApp recebido', [
                'number_id' => $numberId,
                'event_type' => $eventType,
                'has_data' => !empty($eventData)
            ]);

            // Processar evento em background
            ProcessWhatsAppWebhookEvent::dispatch($numberId, $eventType, $eventData);

            return response()->json(['status' => 'queued']);

        } catch (\Exception $e) {
            Log::error('Erro ao receber evento WhatsApp', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => 'Erro ao processar evento'], 500);
        }
    }

    /**
     * Buscar status atual do número WhatsApp da companhia
     */
    public function getStatus(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->isSuperAdmin()) {
            return response()->json(['error' => 'Acesso não autorizado'], 403);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json(['error' => 'Usuário não possui empresa associada'], 403);
        }

        $whatsappNumber = $company->whatsappNumbers()->first();

        if (!$whatsappNumber) {
            return response()->json(['whatsapp_number' => null]);
        }

        return response()->json([
            'whatsapp_number' => [
                'id' => $whatsappNumber->id,
                'phone_number' => $whatsappNumber->phone_number,
                'status' => $whatsappNumber->status,
                'last_connected_at' => $whatsappNumber->last_connected_at,
                'last_activity_at' => $whatsappNumber->last_activity_at,
                'error_message' => $whatsappNumber->error_message,
            ]
        ]);
    }
}
