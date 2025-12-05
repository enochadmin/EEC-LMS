<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class UserHasRole extends Model
{
    use HasFactory;

    protected $table = 'user_has_role';

    protected $fillable = [
        'user_id',
        'role_id',
    ];

    /**
     * Get the user that owns this role assignment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role assigned to the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
