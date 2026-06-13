<?php

declare(strict_types=1);

namespace Devioz\Models;

use Illuminate\Database\Eloquent\Model;

class AdminUser extends Model
{
    protected $table = 'admin_users';

    protected $fillable = ['name', 'email', 'password_hash'];

    protected $hidden = ['password_hash'];

    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->password_hash);
    }
}
