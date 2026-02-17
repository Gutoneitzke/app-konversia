<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Message;
use App\Models\WhatsAppNumber;
use Illuminate\Console\Command;

class TestWhatsAppLocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test-locks {jid?} {--count=3 : Number of test messages to send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp send locks by dispatching multiple messages to the same number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jid = $this->argument('jid');
        $count = (int) $this->option('count');

        if (!$jid) {
            $this->error('JID é obrigatório. Use: php artisan whatsapp:test-locks 5511999999999@s.whatsapp.net');
            return self::FAILURE;
        }

        // Verificar se o número existe
        $whatsappNumber = WhatsAppNumber::where('jid', $jid)->first();

        if (!$whatsappNumber) {
            // Criar um número de teste se não existir
            $this->warn("Número {$jid} não encontrado. Criando um de teste...");
            $whatsappNumber = WhatsAppNumber::create([
                'company_id' => 1, // Assumindo que existe
                'phone_number' => str_replace(['@s.whatsapp.net', '+'], '', $jid),
                'nickname' => 'Test Number',
                'status' => 'connected',
                'jid' => $jid,
            ]);
            $this->info("Número de teste criado com ID: {$whatsappNumber->id}");
        }

        $this->info("🧪 Testando locks WhatsApp para: {$jid}");
        $this->info("📤 Enviando {$count} mensagens simultâneas...");
        $this->newLine();

        // Despachar múltiplas mensagens simultaneamente
        for ($i = 1; $i <= $count; $i++) {
            $message = Message::create([
                'conversation_id' => 1, // Assumindo que existe
                'user_id' => null,
                'department_id' => 1,
                'direction' => 'outbound',
                'type' => 'text',
                'content' => "Teste de lock #{$i} - " . now()->format('H:i:s.u'),
                'delivery_status' => 'pending',
            ]);

            SendWhatsAppMessage::dispatch($message, $whatsappNumber, $jid);

            $this->info("✅ Job #{$i} despachado (Message ID: {$message->id})");
        }

        $this->newLine();
        $this->info("🎯 Resultado esperado:");
        $this->info("  - Apenas 1 mensagem será processada imediatamente");
        $this->info("  - As outras aguardarão o lock ser liberado");
        $this->info("  - Jobs serão reagendados automaticamente");
        $this->newLine();

        $this->info("📊 Monitore com:");
        $this->info("  make locks-monitor        # Ver locks ativos");
        $this->info("  make queue-monitor        # Ver status das filas");
        $this->info("  make horizon-dashboard    # Ver no Horizon");

        return self::SUCCESS;
    }
}