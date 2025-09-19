<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\User\Models\User;

class SalesTeam extends Model
{
    protected $fillable = [
        'name',
        'description',
        'manager_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the manager of this team.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the team members.
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(SalesTeamMember::class, 'team_id');
    }

    /**
     * Get the active team members.
     */
    public function activeMembers(): HasMany
    {
        return $this->teamMembers()->where('is_active', true);
    }

    /**
     * Get the users who are members of this team.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sales_team_members', 'team_id', 'user_id')
                    ->withPivot(['role', 'joined_at', 'left_at', 'is_active'])
                    ->wherePivot('is_active', true);
    }

    /**
     * Get the sales targets for this team.
     */
    public function salesTargets(): HasMany
    {
        return $this->hasMany(SalesTarget::class, 'assignee_id')
                    ->where('assignee_type', 'team');
    }

    /**
     * Scope for active teams.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
