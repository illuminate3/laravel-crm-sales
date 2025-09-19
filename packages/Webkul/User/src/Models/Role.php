<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\Role as RoleContract;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webkul\User\Database\Factories\RoleFactory;

class Role extends Model implements RoleContract
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'permission_type',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get the users.
     */
    public function users()
    {
        return $this->hasMany(UserProxy::modelClass());
    }

    protected static function newFactory()
    {
        return RoleFactory::new();
    }
}
