<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'active',
        'settings',
    ];

    protected $casts = [
        'active' => 'boolean',
        'settings' => 'array',
    ];

    // Relacionamentos
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function whatsappSessions(): HasMany
    {
        return $this->hasMany(WhatsAppSession::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(Message::class, Conversation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    // Helpers
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
