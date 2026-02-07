<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'session_id',
        'status',
        'phone_number',
        'metadata',
        'connected_at',
        'last_activity',
    ];

    protected $casts = [
        'metadata' => 'array',
        'connected_at' => 'datetime',
        'last_activity' => 'datetime',
    ];

    // Relacionamentos
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'connected');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Helpers
    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isConnecting(): bool
    {
        return $this->status === 'connecting';
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'connected' => 'green',
            'connecting' => 'yellow',
            'disconnected' => 'gray',
            'error' => 'red',
            default => 'gray',
        };
    }

    public function getStatusText(): string
    {
        return match ($this->status) {
            'connected' => 'Conectado',
            'connecting' => 'Conectando',
            'disconnected' => 'Desconectado',
            'error' => 'Erro',
            default => 'Desconhecido',
        };
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity' => now()]);
    }
}
