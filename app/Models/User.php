<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- Role Helpers ---

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has curator or admin role.
     */
    public function isCurator(): bool
    {
        return in_array($this->role, ['admin', 'curator']);
    }

    /**
     * Check if user has contributor, curator, or admin role.
     */
    public function isContributor(): bool
    {
        return in_array($this->role, ['admin', 'curator', 'contributor']);
    }

    // --- Relationships ---

    /**
     * Get the items created by this user.
     */
    public function items()
    {
        return $this->hasMany(\App\Models\Item::class, 'created_by');
    }

    /**
     * Get the media uploaded by this user.
     */
    public function media()
    {
        return $this->hasMany(\App\Models\Media::class, 'uploaded_by');
    }

    /**
     * Get the collections created by this user.
     */
    public function collections()
    {
        return $this->hasMany(\App\Models\Collection::class, 'created_by');
    }

    /**
     * Get the exhibits created by this user.
     */
    public function exhibits()
    {
        return $this->hasMany(\App\Models\Exhibit::class, 'created_by');
    }
}
