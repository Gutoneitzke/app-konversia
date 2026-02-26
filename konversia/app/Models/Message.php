<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'department_id',
        'direction',
        'type',
        'content',
        'file_path',
        'file_name',
        'file_mime_type',
        'file_size',
        'media_metadata',
        'sent_at',
        'delivered_at',
        'read_at',
        'delivery_status',
        'error_message',
        'reply_to_message_id',
        'whatsapp_message_id',
        'whatsapp_metadata',
    ];

    protected $casts = [
        'media_metadata' => 'array',
        'whatsapp_metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'file_size' => 'integer',
    ];

    protected $appends = [
        'file_url',
    ];

    // Relacionamentos
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function replyToMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }

    // Scopes
    public function scopeForConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('sent_at', '>=', now()->subHours($hours));
    }

    public function scopeWithFiles($query)
    {
        return $query->whereNotNull('file_path');
    }

    // Helpers
    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    public function isMedia(): bool
    {
        return in_array($this->type, ['image', 'video', 'audio', 'document']);
    }

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function hasFile(): bool
    {
        return !empty($this->file_path);
    }

    public function getFileUrl(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        // Return relative URL so it works in both container and host environments
        return '/storage/' . $this->file_path;
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->getFileUrl();
    }

    public function getThumbnailUrl(): ?string
    {
        if (!$this->isMedia() || !isset($this->media_metadata['thumbnail_path'])) {
            return null;
        }

        return Storage::disk('public')->url($this->media_metadata['thumbnail_path']);
    }

    public function getFormattedSize(): string
    {
        if (!$this->file_size) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < 3) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }

    public function getFormattedSentAt(): string
    {
        return $this->sent_at?->format('d/m/Y H:i') ?? '';
    }

    public function getTypeIcon(): string
    {
        return match ($this->type) {
            'text' => '💬',
            'image' => '🖼️',
            'video' => '🎥',
            'audio' => '🎵',
            'document' => '📄',
            'sticker' => '🎭',
            'location' => '📍',
            'contact' => '👤',
            'link' => '🔗',
            default => '📨',
        };
    }

    public function getTypeName(): string
    {
        return match ($this->type) {
            'text' => 'Texto',
            'image' => 'Imagem',
            'video' => 'Vídeo',
            'audio' => 'Áudio',
            'document' => 'Documento',
            'sticker' => 'Sticker',
            'location' => 'Localização',
            'contact' => 'Contato',
            'link' => 'Link',
            default => 'Mensagem',
        };
    }

    public function markAsDelivered(): void
    {
        if (!$this->delivered_at) {
            $this->update(['delivered_at' => now(), 'delivery_status' => 'delivered']);
        }
    }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now(), 'delivery_status' => 'read']);
            $this->markAsDelivered(); // Delivered implica read
        }
    }

    public function markAsFailed(): void
    {
        $this->update(['delivery_status' => 'failed']);
    }

    public function isDelivered(): bool
    {
        return in_array($this->delivery_status, ['delivered', 'read']);
    }

    public function isRead(): bool
    {
        return $this->delivery_status === 'read';
    }

    public function isFailed(): bool
    {
        return $this->delivery_status === 'failed';
    }

    // Métodos para mídia
    public function getImageDimensions(): ?array
    {
        if ($this->type !== 'image' || !isset($this->media_metadata)) {
            return null;
        }

        return [
            'width' => $this->media_metadata['width'] ?? null,
            'height' => $this->media_metadata['height'] ?? null,
        ];
    }

    public function getVideoInfo(): ?array
    {
        if ($this->type !== 'video' || !isset($this->media_metadata)) {
            return null;
        }

        return [
            'width' => $this->media_metadata['width'] ?? null,
            'height' => $this->media_metadata['height'] ?? null,
            'duration' => $this->media_metadata['duration'] ?? null,
        ];
    }

    public function getAudioInfo(): ?array
    {
        if ($this->type !== 'audio' || !isset($this->media_metadata)) {
            return null;
        }

        return [
            'duration' => $this->media_metadata['duration'] ?? null,
            'voice_note' => $this->media_metadata['voice_note'] ?? false,
        ];
    }

    public function getDocumentInfo(): ?array
    {
        if ($this->type !== 'document' || !isset($this->media_metadata)) {
            return null;
        }

        return [
            'page_count' => $this->media_metadata['page_count'] ?? null,
            'title' => $this->media_metadata['title'] ?? null,
        ];
    }

    public function getLocationInfo(): ?array
    {
        if ($this->type !== 'location' || !isset($this->media_metadata)) {
            return null;
        }

        return [
            'latitude' => $this->media_metadata['latitude'] ?? null,
            'longitude' => $this->media_metadata['longitude'] ?? null,
            'address' => $this->media_metadata['address'] ?? null,
        ];
    }

    public function getContactInfo(): ?array
    {
        if ($this->type !== 'contact' || !isset($this->media_metadata)) {
            return null;
        }

        return [
            'name' => $this->media_metadata['name'] ?? null,
            'phone' => $this->media_metadata['phone'] ?? null,
            'email' => $this->media_metadata['email'] ?? null,
            'company' => $this->media_metadata['company'] ?? null,
        ];
    }

    public function getLinkInfo(): ?array
    {
        if ($this->type !== 'link' || !isset($this->media_metadata)) {
            return null;
        }

        return [
            'title' => $this->media_metadata['title'] ?? null,
            'description' => $this->media_metadata['description'] ?? null,
            'url' => $this->media_metadata['url'] ?? null,
        ];
    }
}
