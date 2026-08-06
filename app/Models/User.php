<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'attendance_required' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'user_id');
    }

    public function attempts()
    {
        return $this->hasMany(AttendanceAttempt::class, 'user_id');
    }

    public function correctedAttendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'corrected_by');
    }
}
