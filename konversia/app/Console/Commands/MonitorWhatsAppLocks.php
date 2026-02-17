<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class MonitorWhatsAppLocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:monitor-locks {--format=table : Output format (table/json)} {--stale : Show only potentially stale locks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor active WhatsApp send locks to detect potential issues';

    /**
     * Lock key prefix
     */
    private const LOCK_PREFIX = 'whatsapp:send_lock:';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format');
        $showStaleOnly = $this->option('stale');

        if ($format === 'json') {
            $this->outputJson($showStaleOnly);
        } else {
            $this->outputTable($showStaleOnly);
        }
    }

    /**
     * Output locks status as a table
     */
    private function outputTable(bool $showStaleOnly = false): void
    {
        $this->info('🔒 Monitor de Locks WhatsApp');
        $this->newLine();

        $locks = $this->getActiveLocks();

        if (empty($locks)) {
            $this->info('✅ Nenhum lock ativo encontrado');
            return;
        }

        $headers = [
            'JID',
            'Tempo Restante',
            'Status',
            'Ações'
        ];

        $rows = [];
        $staleCount = 0;

        foreach ($locks as $lock) {
            $isStale = $lock['remaining_time'] <= 0;

            if ($showStaleOnly && !$isStale) {
                continue;
            }

            if ($isStale) {
                $staleCount++;
            }

            $rows[] = [
                $lock['jid'],
                $lock['remaining_time'] > 0 ? "{$lock['remaining_time']}s" : 'Expirado',
                $isStale ? '🔴 STALE' : '✅ ATIVO',
                $isStale ? 'Liberar manualmente' : '-'
            ];
        }

        if (empty($rows)) {
            $this->info('✅ Nenhum lock stale encontrado');
            return;
        }

        $this->table($headers, $rows);

        if ($staleCount > 0) {
            $this->newLine();
            $this->error("🔴 {$staleCount} lock(s) potencialmente stale(s) detectado(s)!");
            $this->warn('Considere liberar locks expirados manualmente se necessário.');

            if ($this->confirm('Deseja liberar todos os locks expirados?')) {
                $this->releaseStaleLocks();
            }
        } else {
            $this->info('✅ Todos os locks estão funcionando normalmente');
        }
    }

    /**
     * Output locks status as JSON
     */
    private function outputJson(bool $showStaleOnly = false): void
    {
        $locks = $this->getActiveLocks();
        $data = [
            'timestamp' => now()->toISOString(),
            'total_locks' => count($locks),
            'locks' => []
        ];

        foreach ($locks as $lock) {
            $isStale = $lock['remaining_time'] <= 0;

            if ($showStaleOnly && !$isStale) {
                continue;
            }

            $data['locks'][] = array_merge($lock, [
                'is_stale' => $isStale,
                'status' => $isStale ? 'stale' : 'active'
            ]);
        }

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Get all active WhatsApp locks
     */
    private function getActiveLocks(): array
    {
        try {
            $redis = Redis::connection('cache');

            // Get all keys matching the lock pattern
            $keys = $redis->keys(self::LOCK_PREFIX . '*');

            if (empty($keys)) {
                return [];
            }

            $locks = [];

            foreach ($keys as $key) {
                $ttl = $redis->ttl($key);
                $jid = str_replace(self::LOCK_PREFIX, '', $key);

                $locks[] = [
                    'key' => $key,
                    'jid' => $jid,
                    'remaining_time' => $ttl,
                    'expires_at' => $ttl > 0 ? now()->addSeconds($ttl)->toISOString() : null
                ];
            }

            // Sort by remaining time (stale first)
            usort($locks, fn($a, $b) => $a['remaining_time'] <=> $b['remaining_time']);

            return $locks;

        } catch (\Exception $e) {
            $this->error('Erro ao acessar Redis: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Release stale locks
     */
    private function releaseStaleLocks(): void
    {
        $locks = $this->getActiveLocks();
        $released = 0;

        foreach ($locks as $lock) {
            if ($lock['remaining_time'] <= 0) {
                try {
                    Cache::lock(str_replace(self::LOCK_PREFIX, '', $lock['key']), 30)->forceRelease();
                    $this->info("Lock liberado: {$lock['jid']}");
                    $released++;
                } catch (\Exception $e) {
                    $this->error("Erro ao liberar lock {$lock['jid']}: " . $e->getMessage());
                }
            }
        }

        if ($released > 0) {
            $this->info("✅ {$released} lock(s) liberado(s) com sucesso!");
        } else {
            $this->warn('Nenhum lock foi liberado.');
        }
    }
}