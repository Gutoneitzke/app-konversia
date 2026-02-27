<?php

namespace App\Http\Controllers;

use App\Jobs\ConnectWhatsAppJob;
use App\Models\WhatsAppNumber;
use App\Models\WhatsAppSession;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class WhatsAppNumberController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Lista de números WhatsApp
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $numbers = WhatsAppNumber::with(['company'])
                ->latest()
                ->get();
        } else {
            if (!$user->company_id) {
                abort(403, 'Usuário não possui empresa associada');
            }
            $numbers = WhatsAppNumber::where('company_id', $user->company_id)
                ->latest()
                ->get();
        }

        return Inertia::render('WhatsAppNumbers/Index', [
            'numbers' => $numbers,
        ]);
    }

    /**
     * Exibir QR Code
     */
    public function showQR(Request $request)
    {
        $user = $request->user();
        $identifier = $request->route('whatsappNumber');

        $whatsappNumber = WhatsAppNumber::find($identifier);

        if (!$whatsappNumber) {
            abort(404, 'WhatsApp Number not found');
        }

        if (!$user->isSuperAdmin() && $whatsappNumber->company_id !== $user->company_id) {
            abort(403);
        }

        $qrCode = $this->whatsappService->getQRCode($whatsappNumber);

        return Inertia::render('WhatsAppNumbers/QRCode', [
            'whatsappNumber' => $whatsappNumber,
            'qrCode' => $qrCode,
        ]);
    }

    /**
     * Conectar número WhatsApp
     */
    public function connect(Request $request)
    {
        $user = $request->user();
        $identifier = $request->route('whatsappNumber');

        $whatsappNumber = WhatsAppNumber::find($identifier);

        if (!$whatsappNumber) {
            abort(404, 'WhatsApp Number not found');
        }

        // Verificar permissão
        if (!$user->isSuperAdmin() && $whatsappNumber->company_id !== $user->company_id) {
            abort(403);
        }

        if (!$user->canManageCompany()) {
            abort(403, 'Acesso negado');
        }

        try {
            // Atualizar status para connecting antes de iniciar
            $whatsappNumber->updateStatus('connecting');

            // Criar/atualizar sessão
            $session = WhatsAppSession::firstOrCreate(
                [
                    'company_id' => $whatsappNumber->company_id,
                    'whatsapp_number_id' => $whatsappNumber->id,
                ],
                [
                    'session_id' => $whatsappNumber->jid,
                    'status' => 'connecting',
                ]
            );

            if ($session->session_id !== $whatsappNumber->jid) {
                $session->update(['session_id' => $whatsappNumber->jid]);
            }

            // Despachar job para fazer a conexão (não bloqueia)
            ConnectWhatsAppJob::dispatch($whatsappNumber);

            Log::info('Conexão WhatsApp iniciada', [
                'whatsapp_number_id' => $whatsappNumber->id,
                'jid' => $whatsappNumber->jid
            ]);

            return redirect()->route('whatsapp-numbers.qr', $whatsappNumber)
                ->with('success', 'Conexão iniciada. Aguarde o QR Code aparecer.');

        } catch (\Exception $e) {
            Log::error('Erro ao iniciar conexão WhatsApp', [
                'whatsapp_number_id' => $whatsappNumber->id,
                'error' => $e->getMessage()
            ]);

            $whatsappNumber->updateStatus('error', $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao iniciar conexão.');
        }
    }

    /**
     * Desconectar número WhatsApp
     */
    public function disconnect(Request $request)
    {
        $user = $request->user();
        $identifier = $request->route('whatsappNumber');

        $whatsappNumber = WhatsAppNumber::find($identifier);

        if (!$whatsappNumber) {
            abort(404, 'WhatsApp Number not found');
        }

        // Verificar permissão
        if (!$user->isSuperAdmin() && $whatsappNumber->company_id !== $user->company_id) {
            abort(403);
        }

        if (!$user->canManageCompany()) {
            abort(403, 'Acesso negado');
        }

        $success = $this->whatsappService->disconnect($whatsappNumber);

        if ($success) {
            return redirect()->back()->with('success', 'Número desconectado com sucesso.');
        }

        return redirect()->back()->with('error', 'Erro ao desconectar número.');
    }

    /**
     * Verificar status da conexão
     */
    public function checkStatus(Request $request)
    {
        $user = $request->user();
        $identifier = $request->route('whatsappNumber');

        $whatsappNumber = WhatsAppNumber::find($identifier);

        if (!$whatsappNumber) {
            abort(404, 'WhatsApp Number not found');
        }

        // Verificar permissão
        if (!$user->isSuperAdmin() && $whatsappNumber->company_id !== $user->company_id) {
            abort(403);
        }

        $status = $this->whatsappService->checkStatus($whatsappNumber);

        return response()->json($status);
    }
}
