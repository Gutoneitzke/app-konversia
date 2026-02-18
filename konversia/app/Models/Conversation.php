<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'whatsapp_session_id',
        'department_id',
        'contact_id',
        'contact_jid',
        'contact_name',
        'status',
        'assigned_to',
        'transferred_from_department_id',
        'transferred_at',
        'transfer_notes',
        'last_message_at',
        'resolved_at',
        'resolved_by',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'last_message_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relacionamentos
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function whatsappSession(): BelongsTo
    {
        return $this->belongsTo(WhatsAppSession::class, 'whatsapp_session_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function transferredFromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'transferred_from_department_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('sent_at');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(ConversationTransfer::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeTransferred($query)
    {
        return $query->where('status', 'transferred');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isTransferred(): bool
    {
        return $this->status === 'transferred';
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function assignTo(User $user): bool
    {
        if (!$user->belongsToDepartment($this->department_id)) {
            return false;
        }

        $this->update([
            'assigned_to' => $user->id,
            'status' => 'in_progress',
        ]);

        return true;
    }

    public function transferTo(Department $department, ?User $transferredBy = null, ?User $assignTo = null, ?string $notes = null): bool
    {
        if ($this->department_id === $department->id) {
            return false;
        }

        // Criar registro de transferência (apenas se houver usuário)
        if ($transferredBy) {
            ConversationTransfer::create([
                'conversation_id' => $this->id,
                'from_department_id' => $this->department_id,
                'to_department_id' => $department->id,
                'from_user_id' => $transferredBy->id,
                'assigned_to_user_id' => $assignTo?->id,
                'notes' => $notes,
                'transferred_at' => now(),
            ]);
        }

        // Atualizar conversa
        $updateData = [
            'department_id' => $department->id,
            'transferred_from_department_id' => $this->department_id,
            'transferred_at' => now(),
            'transfer_notes' => $notes,
        ];

        // Só alterar assigned_to e status se houver usuário específico
        if ($assignTo) {
            $updateData['assigned_to'] = $assignTo->id;
            $updateData['status'] = 'in_progress';
        } elseif (!$transferredBy) {
            // Para transferências automáticas, manter status atual
            // Não alterar assigned_to
        } else {
            // Para transferências manuais sem assignTo, colocar como pending
            $updateData['status'] = 'pending';
        }

        $this->update($updateData);

        return true;
    }

    public function resolve(?User $user = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $user?->id,
        ]);
    }

    public function close(?User $user = null): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $user?->id,
        ]);
    }

    public function reopen(?User $user = null): void
    {
        $this->update([
            'status' => 'pending',
            'resolved_at' => null,
            'resolved_by' => null,
            'closed_at' => null,
            'closed_by' => null,
        ]);
    }

    public function getLatestMessage()
    {
        return $this->messages()->latest('sent_at')->first();
    }

    public function getUnreadCount(): int
    {
        // Implementar lógica de mensagens não lidas
        return 0;
    }

    public function updateLastMessageAt(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    // Helpers para Contact
    public function getContactDisplayName(): string
    {
        return $this->contact?->getDisplayName() ?? $this->contact_name ?? 'Contato Desconhecido';
    }

    public function getContactJid(): string
    {
        return $this->contact?->jid ?? $this->contact_jid;
    }

    public function getContactPhoneNumber(): ?string
    {
        return $this->contact?->getFormattedPhoneNumber() ?? null;
    }

    public function isContactBlocked(): bool
    {
        return $this->contact?->is_blocked ?? false;
    }

    public function isContactOnline(): bool
    {
        return $this->contact?->isOnline() ?? false;
    }

    // Métodos estáticos úteis
    public static function findOrCreateForContact(
        Contact $contact,
        WhatsAppSession $session,
        Department $department,
        array $conversationData = []
    ): self {
        // Verificar se já existe uma conversa ativa para este contato
        $existingConversation = static::where('contact_id', $contact->id)
                                     ->where('whatsapp_session_id', $session->id)
                                     ->whereIn('status', ['pending', 'in_progress'])
                                     ->first();

        if ($existingConversation) {
            return $existingConversation;
        }

        // Criar nova conversa
        return static::create(array_merge([
            'company_id' => $contact->company_id,
            'whatsapp_session_id' => $session->id,
            'department_id' => $department->id,
            'contact_id' => $contact->id,
            'contact_jid' => $contact->jid,
            'contact_name' => $contact->name,
            'status' => 'pending',
        ], $conversationData));
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany('id');
    }

    public function unreadMessages()
    {
        return $this->hasMany(Message::class)
            ->where('direction', 'inbound')
            ->whereNull('read_at');
    }
}
