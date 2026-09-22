<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
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
        'pegawai_id',
        'ruangan_id',
        'nama_ruangan',
        'departemen_id',
        'is_active',
        'password',
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
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'admin']);
    }

    public function isCssd(): bool
    {
        return $this->hasRole(['super_admin', 'admin', 'user_cssd', 'user']);
    }

    public function isPerawat(): bool
    {
        return $this->hasRole(['super_admin', 'admin', 'user_perawat']);
    }

    public function hasRole(array|string $roles): bool
    {
        $roles = (array) $roles;
        $role = $this->role;

        if ($role === 'admin' && in_array('super_admin', $roles, true)) {
            return true;
        }

        if ($role === 'user' && in_array('user_cssd', $roles, true)) {
            return true;
        }

        return in_array($role, $roles, true);
    }

    public function dibatasiRuangan(): bool
    {
        return $this->role === 'user_perawat';
    }

    public function punyaRuangan(): bool
    {
        return filled($this->ruangan_id) && filled($this->nama_ruangan);
    }

    public function bolehAksesRuangan(?string $namaRuangan): bool
    {
        return !$this->dibatasiRuangan() || ($this->punyaRuangan()
            && mb_strtolower(trim($namaRuangan ?? '')) === mb_strtolower(trim($this->nama_ruangan)));
    }

    public function batasiQueryRuangan(Builder $query, string $kolom): Builder
    {
        if (!$this->dibatasiRuangan()) {
            return $query;
        }

        if (!$this->punyaRuangan()) {
            return $query->whereRaw('1 = 0');
        }

        // Data BMHP lama menyimpan nama ruangan, bukan ID dari SIMRS.
        $kolom = $query->getGrammar()->wrap($kolom);

        return $query->whereRaw('LOWER(TRIM(' . $kolom . ')) = ?', [mb_strtolower(trim($this->nama_ruangan))]);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'super_admin', 'admin' => 'Super Admin',
            'user_cssd', 'user' => 'User CSSD',
            'user_perawat' => 'User Perawat',
            default => 'User',
        };
    }
}
