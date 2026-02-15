<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WhatsAppNumber extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'whatsapp_numbers';

    protected $fillable = [
        'company_id',
        'phone_number',
        'nickname',
        'description',
        'status',
        'jid',
        'settings',
        'last_connected_at',
        'last_activity_at',
        'error_message',
        'auto_reconnect',
        'reconnect_attempts',
        'blocked_until',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_connected_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'auto_reconnect' => 'boolean',
        'blocked_until' => 'datetime',
        'reconnect_attempts' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->jid)) {
                $model->jid = Str::uuid()->toString();
            }
        });
    }

    // Relacionamentos
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(WhatsAppSession::class, 'whatsapp_number_id');
    }

    public function activeSession(): HasOne
    {
        return $this->hasOne(WhatsAppSession::class, 'whatsapp_number_id')->whereIn('status', ['connected', 'connecting']);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'whatsapp_session_id')
                    ->join('whatsapp_sessions', 'conversations.whatsapp_session_id', '=', 'whatsapp_sessions.id')
                    ->where('whatsapp_sessions.whatsapp_number_id', $this->id)
                    ->select('conversations.*');
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeNotBlocked($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('blocked_until')
              ->orWhere('blocked_until', '<=', now());
        });
    }

    public function scopeWithRecentActivity($query, $minutes = 30)
    {
        return $query->where('last_activity_at', '>=', now()->subMinutes($minutes));
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isBlocked(): bool
    {
        return $this->blocked_until && $this->blocked_until->isFuture();
    }

    public function canConnect(): bool
    {
        return $this->isActive() && !$this->isBlocked();
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'inactive' => 'gray',
            'active' => 'blue',
            'connecting' => 'yellow',
            'connected' => 'green',
            'error' => 'red',
            'blocked' => 'red',
            default => 'gray',
        };
    }

    public function getStatusText(): string
    {
        return match ($this->status) {
            'inactive' => 'Inativo',
            'active' => 'Ativo',
            'connecting' => 'Conectando',
            'connected' => 'Conectado',
            'error' => 'Erro',
            'blocked' => 'Bloqueado',
            default => 'Desconhecido',
        };
    }

    public function getFormattedPhoneNumber(): string
    {
        // Formatar número de telefone (ex: +55 11 99999-9999)
        $number = $this->phone_number;

        // Remove todos os caracteres não numéricos
        $number = preg_replace('/\D/', '', $number);

        // Brasil (55) - exemplo básico
        if (strlen($number) === 13 && str_starts_with($number, '55')) {
            return '+' . substr($number, 0, 2) . ' ' .
                   substr($number, 2, 2) . ' ' .
                   substr($number, 4, 5) . '-' .
                   substr($number, 9);
        }

        return $number; // Retorna como está se não conseguir formatar
    }

    public function updateStatus(string $status, ?string $errorMessage = null): void
    {
        $updateData = ['status' => $status];

        if ($status === 'connected') {
            $updateData['last_connected_at'] = now();
            $updateData['reconnect_attempts'] = 0;
            $updateData['error_message'] = null;
        } elseif ($status === 'error') {
            $updateData['error_message'] = $errorMessage;
            $updateData['reconnect_attempts'] = $this->reconnect_attempts + 1;
        } elseif ($status === 'blocked') {
            $updateData['blocked_until'] = now()->addHours(24); // Bloqueio padrão de 24h
        }

        $this->update($updateData);
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function incrementReconnectAttempts(): void
    {
        $this->increment('reconnect_attempts');
    }

    public function resetReconnectAttempts(): void
    {
        $this->update(['reconnect_attempts' => 0]);
    }

    public function shouldAutoReconnect(): bool
    {
        return $this->auto_reconnect &&
               !$this->isBlocked() &&
               $this->reconnect_attempts < 10; // Máximo 10 tentativas
    }

    public function getReconnectDelay(): int
    {
        // Delay exponencial: 1s, 2s, 4s, 8s, 16s, etc.
        $attempt = min($this->reconnect_attempts, 6); // Máximo 64s
        return pow(2, $attempt);
    }

    public function getSettings(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->settings ?? [];
        }

        return data_get($this->settings, $key, $default);
    }

    public function setSettings(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    public function getRouteKeyName()
    {
        return 'jid';
    }
}
