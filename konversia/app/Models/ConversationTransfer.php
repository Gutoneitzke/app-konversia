<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'from_department_id',
        'to_department_id',
        'from_user_id',
        'assigned_to_user_id',
        'notes',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    // Relacionamentos
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    // Scopes
    public function scopeForConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeFromDepartment($query, $departmentId)
    {
        return $query->where('from_department_id', $departmentId);
    }

    public function scopeToDepartment($query, $departmentId)
    {
        return $query->where('to_department_id', $departmentId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('from_user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('transferred_at', '>=', now()->subDays($days));
    }

    // Helpers
    public function getTransferSummary(): string
    {
        return "De {$this->fromDepartment->name} para {$this->toDepartment->name}";
    }

    public function wasAssigned(): bool
    {
        return !is_null($this->assigned_to_user_id);
    }
}
