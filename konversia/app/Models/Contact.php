<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'whatsapp_number_id',
        'jid',
        'name',
        'phone_number',
        'avatar_url',
        'is_blocked',
        'is_business',
        'last_seen',
        'metadata',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'is_business' => 'boolean',
        'last_seen' => 'datetime',
        'metadata' => 'array',
    ];

    // Relacionamentos
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function whatsappNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsAppNumber::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function messages(): HasMany
    {
        return $this->hasManyThrough(Message::class, Conversation::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForWhatsAppNumber($query, $whatsappNumberId)
    {
        return $query->where('whatsapp_number_id', $whatsappNumberId);
    }

    public function scopeByJid($query, $jid)
    {
        return $query->where('jid', $jid);
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    public function scopeNotBlocked($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeBusiness($query)
    {
        return $query->where('is_business', true);
    }

    public function scopeWithRecentActivity($query, $days = 30)
    {
        return $query->whereHas('conversations', function ($q) use ($days) {
            $q->where('last_message_at', '>=', now()->subDays($days));
        });
    }

    public function scopeWithConversations($query)
    {
        return $query->has('conversations');
    }

    // Helpers
    public function getDisplayName(): string
    {
        return $this->name ?? $this->getFormattedPhoneNumber() ?? 'Contato Desconhecido';
    }

    public function getFormattedPhoneNumber(): ?string
    {
        if (!$this->phone_number) {
            return null;
        }

        // Extrair apenas números
        $number = preg_replace('/\D/', '', $this->phone_number);

        // Formatar para Brasil (exemplo)
        if (strlen($number) === 13 && str_starts_with($number, '55')) {
            return '+' . substr($number, 0, 2) . ' ' .
                   substr($number, 2, 2) . ' ' .
                   substr($number, 4, 5) . '-' .
                   substr($number, 9);
        }

        return $this->phone_number; // Retornar como está se não conseguir formatar
    }

    public function getInitials(): string
    {
        $name = $this->getDisplayName();
        $parts = explode(' ', $name);

        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }

        return strtoupper(substr($name, 0, 2));
    }

    public function getAvatarColor(): string
    {
        // Gerar cor baseada no JID para consistência
        $hash = crc32($this->jid);
        $colors = [
            '#3B82F6', // blue
            '#10B981', // emerald
            '#F59E0B', // amber
            '#EF4444', // red
            '#8B5CF6', // violet
            '#06B6D4', // cyan
            '#84CC16', // lime
            '#F97316', // orange
        ];

        return $colors[$hash % count($colors)];
    }

    public function isOnline(): bool
    {
        if (!$this->last_seen) {
            return false;
        }

        // Considerar online se visto nos últimos 5 minutos
        return $this->last_seen->diffInMinutes(now()) <= 5;
    }

    public function getLastSeenText(): ?string
    {
        if (!$this->last_seen) {
            return null;
        }

        $diff = $this->last_seen->diffForHumans();

        if ($this->isOnline()) {
            return 'Online';
        }

        return 'Visto ' . $diff;
    }

    public function getActiveConversation()
    {
        return $this->conversations()
                   ->whereIn('status', ['pending', 'in_progress'])
                   ->latest('last_message_at')
                   ->first();
    }

    public function getLatestConversation()
    {
        return $this->conversations()
                   ->latest('last_message_at')
                   ->first();
    }

    public function getConversationCount(): int
    {
        return $this->conversations()->count();
    }

    public function getMessageCount(): int
    {
        return $this->messages()->count();
    }

    public function getUnreadCount(): int
    {
        return $this->conversations()
                   ->join('messages', 'conversations.id', '=', 'messages.conversation_id')
                   ->where('messages.direction', 'inbound')
                   ->whereNull('messages.read_at')
                   ->count();
    }

    public function getLastMessage()
    {
        return $this->messages()
                   ->latest('sent_at')
                   ->first();
    }

    public function block(): void
    {
        $this->update(['is_blocked' => true]);
    }

    public function unblock(): void
    {
        $this->update(['is_blocked' => false]);
    }

    public function updateLastSeen(?string $timestamp = null): void
    {
        $this->update([
            'last_seen' => $timestamp ? $timestamp : now()
        ]);
    }

    public function updateProfile(string $name = null, string $avatarUrl = null): void
    {
        $updateData = [];

        if ($name !== null) {
            $updateData['name'] = $name;
        }

        if ($avatarUrl !== null) {
            $updateData['avatar_url'] = $avatarUrl;
        }

        if (!empty($updateData)) {
            $this->update($updateData);
        }
    }

    public function getMetadata(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->metadata ?? [];
        }

        return data_get($this->metadata, $key, $default);
    }

    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->update(['metadata' => $metadata]);
    }

    public function getRouteKeyName()
    {
        return 'jid';
    }

    // Métodos estáticos úteis
    public static function findOrCreateFromWhatsApp(
        Company $company,
        WhatsAppNumber $whatsappNumber,
        string $jid,
        array $contactData = []
    ): self {
        return static::firstOrCreate(
            [
                'company_id' => $company->id,
                'whatsapp_number_id' => $whatsappNumber->id,
                'jid' => $jid,
            ],
            array_merge([
                'name' => $contactData['name'] ?? null,
                'phone_number' => $contactData['phone_number'] ?? null,
                'avatar_url' => $contactData['avatar_url'] ?? null,
                'is_blocked' => $contactData['is_blocked'] ?? false,
                'is_business' => $contactData['is_business'] ?? false,
                'metadata' => $contactData['metadata'] ?? [],
            ], $contactData)
        );
    }

    public static function findByJidForCompany(string $jid, int $companyId): ?self
    {
        return static::where('company_id', $companyId)
                    ->where('jid', $jid)
                    ->first();
    }

    public static function searchForCompany(int $companyId, string $query, int $limit = 20)
    {
        return static::where('company_id', $companyId)
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('phone_number', 'like', "%{$query}%")
                          ->orWhere('jid', 'like', "%{$query}%");
                    })
                    ->limit($limit)
                    ->get();
    }
}
