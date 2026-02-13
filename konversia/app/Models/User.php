<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'department_id',
        'role',
        'is_owner',
        'active',
    ];

    // Super admin pode não ter company_id
    public static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            // Super admin pode ter company_id null
            if ($user->role === 'super_admin') {
                // Permite company_id null para super admin
            } elseif ($user->role !== 'super_admin' && !$user->company_id) {
                throw new \Exception('Usuários não-admin devem pertencer a uma empresa');
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_owner' => 'boolean',
            'active' => 'boolean',
        ];
    }

    // Relacionamentos
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'user_departments')
                    ->withPivot('is_primary', 'active')
                    ->withTimestamps();
    }

    public function assignedConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assigned_to');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function conversationTransfers(): HasMany
    {
        return $this->hasMany(ConversationTransfer::class, 'from_user_id');
    }

    // Helpers
    public function primaryDepartment()
    {
        return $this->departments()->wherePivot('is_primary', true)->first();
    }

    public function getActiveDepartments()
    {
        return $this->departments()->wherePivot('active', true);
    }

    public function belongsToDepartment($departmentId): bool
    {
        return $this->departments()->where('departments.id', $departmentId)->exists();
    }

    public function canAccessConversation($conversation): bool
    {
        // Usuário pode acessar conversa se ela for da mesma empresa
        // e ele pertencer ao departamento da conversa ou ter acesso admin
        return $conversation->company_id === $this->company_id &&
               ($this->belongsToDepartment($conversation->department_id) || $this->hasRole('admin'));
    }


    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeOwners($query)
    {
        return $query->where('is_owner', true);
    }

    // Helpers para roles
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isCompanyOwner(): bool
    {
        return $this->role === 'company_owner' && $this->is_owner;
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function canManageCompany(): bool
    {
        return $this->isSuperAdmin() || $this->isCompanyOwner();
    }

    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin() || $this->isCompanyOwner();
    }

    public function canViewAllCompanies(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageDepartments(): bool
    {
        return $this->isSuperAdmin() || $this->isCompanyOwner();
    }

    public function getRoleDisplayName(): string
    {
        return match ($this->role) {
            'super_admin' => 'Administrador Geral',
            'company_owner' => 'Dono da Empresa',
            'employee' => 'Funcionário',
            default => 'Usuário',
        };
    }
}
