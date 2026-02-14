<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppNumber;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
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
            $numbers = WhatsAppNumber::with(['company', 'activeSession'])
                ->latest()
                ->get();
        } else {
            if (!$user->company_id) {
                abort(403, 'Usuário não possui empresa associada');
            }
            $numbers = WhatsAppNumber::where('company_id', $user->company_id)
                ->with(['activeSession'])
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
    public function showQR(WhatsAppNumber $whatsappNumber, Request $request)
    {
        $user = $request->user();

        // Verificar permissão
        if (!$user->isSuperAdmin() && $whatsappNumber->company_id !== $user->company_id) {
            abort(403);
        }

        $qrCode = $this->whatsappService->getQRCode($whatsappNumber);

        \Log::info('Carregando página QR', [
            'whatsapp_number_id' => $whatsappNumber->id,
            'has_qr' => !empty($qrCode),
            'qr_length' => $qrCode ? strlen($qrCode) : 0,
            'session_count' => $whatsappNumber->sessions()->count(),
            'active_session' => $whatsappNumber->activeSession ? 'exists' : 'none'
        ]);

        Log::info('Carregando página QR', [
            'whatsapp_number_id' => $whatsappNumber->id,
            'has_qr' => !empty($qrCode),
            'qr_length' => $qrCode ? strlen($qrCode) : 0,
            'session_count' => $whatsappNumber->sessions()->count(),
            'active_session' => $whatsappNumber->activeSession ? 'exists' : 'none',
            'jid' => $whatsappNumber->jid
        ]);

        return Inertia::render('WhatsAppNumbers/QRCode', [
            'whatsappNumber' => $whatsappNumber,
            'qrCode' => $qrCode,
        ]);
    }

    /**
     * Conectar número WhatsApp
     */
    public function connect(WhatsAppNumber $whatsappNumber, Request $request)
    {
        $user = $request->user();

        // Verificar permissão
        if (!$user->isSuperAdmin() && $whatsappNumber->company_id !== $user->company_id) {
            abort(403);
        }

        if (!$user->canManageCompany()) {
            abort(403, 'Acesso negado');
        }

        $success = $this->whatsappService->connect($whatsappNumber);

        if ($success) {
            return redirect()->route('whatsapp-numbers.qr', $whatsappNumber)
                ->with('success', 'Conexão iniciada. Aguarde o QR Code aparecer.');
        }

        return redirect()->back()->with('error', 'Erro ao iniciar conexão.');
    }

    /**
     * Desconectar número WhatsApp
     */
    public function disconnect(WhatsAppNumber $whatsappNumber, Request $request)
    {
        $user = $request->user();

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
    public function checkStatus(WhatsAppNumber $whatsappNumber, Request $request)
    {
        $user = $request->user();

        // Verificar permissão
        if (!$user->isSuperAdmin() && $whatsappNumber->company_id !== $user->company_id) {
            abort(403);
        }

        $status = $this->whatsappService->checkStatus($whatsappNumber);

        return response()->json($status);
    }
}
