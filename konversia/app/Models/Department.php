<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'color',
        'active',
        'order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'order' => 'integer',
    ];

    // Relacionamentos
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_departments')
                    ->withPivot('is_primary', 'active')
                    ->withTimestamps();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function conversationTransfers(): HasMany
    {
        return $this->hasMany(ConversationTransfer::class, 'from_department_id');
    }

    public function receivedTransfers(): HasMany
    {
        return $this->hasMany(ConversationTransfer::class, 'to_department_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
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

    public function getActiveUsers()
    {
        return $this->users()->wherePivot('active', true);
    }

    public function getPrimaryUsers()
    {
        return $this->users()->wherePivot('is_primary', true);
    }
}
