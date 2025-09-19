<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

class SalesTeamMember extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'role_name',
        'joined_at',
        'left_at',
        'is_active',
        'contribution_percentage',
    ];

    protected $casts = [
        'joined_at' => 'date',
        'left_at'   => 'date',
        'is_active' => 'boolean',
        'contribution_percentage' => 'decimal:2',
    ];

    /**
     * Get the team this member belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(SalesTeam::class, 'team_id');
    }

    /**
     * Get the user for this team member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role for this team member.
     */
    public function userRole(): BelongsTo
    {
        return $this->user->role();
    }

    /**
     * Scope for active members.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for members by role name.
     */
    public function scopeByRoleName($query, string $roleName)
    {
        return $query->where('role_name', $roleName);
    }

    /**
     * Update role name from user's role.
     */
    public function updateRoleName(): void
    {
        if ($this->user && $this->user->role) {
            $this->role_name = $this->user->role->name;
            $this->save();
        }
    }
}
