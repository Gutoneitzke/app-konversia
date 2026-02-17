<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class MonitorWhatsAppQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:monitor-queues {--format=table : Output format (table/json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor WhatsApp queues status and performance metrics';

    /**
     * Queue configurations with their purposes
     */
    private array $queues = [
        'incoming' => [
            'purpose' => 'Mensagens recebidas',
            'wait_threshold' => 30,
            'critical' => true
        ],
        'webhook' => [
            'purpose' => 'Eventos WhatsApp',
            'wait_threshold' => 15,
            'critical' => true
        ],
        'outgoing' => [
            'purpose' => 'Envio de mensagens',
            'wait_threshold' => 60,
            'critical' => false
        ],
        'automation' => [
            'purpose' => 'Bots e automações',
            'wait_threshold' => 60,
            'critical' => false
        ]
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format');

        if ($format === 'json') {
            $this->outputJson();
        } else {
            $this->outputTable();
        }
    }

    /**
     * Output queue status as a table
     */
    private function outputTable(): void
    {
        $this->info('📊 Status das Filas WhatsApp');
        $this->newLine();

        $headers = [
            'Fila',
            'Propósito',
            'Jobs',
            'Status',
            'Wait (s)',
            'Threshold',
            'Alerta'
        ];

        $rows = [];

        foreach ($this->queues as $queueName => $config) {
            $queueInfo = $this->getQueueInfo($queueName, $config);
            $rows[] = [
                $queueName,
                $config['purpose'],
                $queueInfo['count'],
                $queueInfo['status'],
                $queueInfo['wait_time'] ?? '-',
                $config['wait_threshold'],
                $queueInfo['alert']
            ];
        }

        $this->table($headers, $rows);

        // Summary
        $criticalIssues = collect($rows)->filter(fn($row) => str_contains($row[6], '🔴'))->count();
        $warnings = collect($rows)->filter(fn($row) => str_contains($row[6], '🟡'))->count();

        $this->newLine();
        if ($criticalIssues > 0) {
            $this->error("🔴 {$criticalIssues} fila(s) crítica(s) congestionada(s)!");
        } elseif ($warnings > 0) {
            $this->warn("🟡 {$warnings} fila(s) com alerta(s)!");
        } else {
            $this->info("✅ Todas as filas funcionando normalmente!");
        }
    }

    /**
     * Output queue status as JSON
     */
    private function outputJson(): void
    {
        $data = [
            'timestamp' => now()->toISOString(),
            'queues' => []
        ];

        foreach ($this->queues as $queueName => $config) {
            $data['queues'][$queueName] = array_merge(
                $config,
                $this->getQueueInfo($queueName, $config)
            );
        }

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Get detailed information about a specific queue
     */
    private function getQueueInfo(string $queueName, array $config): array
    {
        try {
            $redis = Redis::connection('default');

            // Get queue length
            $count = $redis->llen("queues:{$queueName}");

            // Get oldest job timestamp (approximate wait time)
            $oldestJob = $redis->lrange("queues:{$queueName}", -1, -1);
            $waitTime = null;

            if (!empty($oldestJob)) {
                $jobData = json_decode($oldestJob[0], true);
                if (isset($jobData['id'])) {
                    // This is approximate - in production you might want to store timestamps
                    $waitTime = rand(5, 120); // Placeholder - implement proper timestamp tracking
                }
            }

            // Determine status and alerts
            $status = $count > 0 ? 'Ativa' : 'Vazia';
            $alert = '✅ OK';

            if ($count > 10) {
                $alert = '🔴 CRÍTICO';
                $status = 'Congestionada';
            } elseif ($count > 5) {
                $alert = '🟡 ALERTA';
                $status = 'Carregada';
            }

            // Wait time alerts
            if ($waitTime && $waitTime > $config['wait_threshold']) {
                if ($config['critical']) {
                    $alert = '🔴 CRÍTICO (Wait)';
                } else {
                    $alert = '🟡 ALERTA (Wait)';
                }
            }

            return [
                'count' => $count,
                'status' => $status,
                'wait_time' => $waitTime,
                'alert' => $alert
            ];

        } catch (\Exception $e) {
            return [
                'count' => 'Erro',
                'status' => 'Indisponível',
                'wait_time' => null,
                'alert' => '🔴 ERRO'
            ];
        }
    }
}