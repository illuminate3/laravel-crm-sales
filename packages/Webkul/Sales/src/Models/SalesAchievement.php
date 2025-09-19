<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesAchievement extends Model
{
    protected $fillable = [
        'title',
        'description',
        'badge_icon',
        'badge_color',
        'type',
        'criteria',
        'points',
        'is_active',
        'is_repeatable',
        'max_awards',
        'metadata',
    ];

    protected $casts = [
        'criteria'      => 'array',
        'metadata'      => 'array',
        'is_active'     => 'boolean',
        'is_repeatable' => 'boolean',
    ];

    /**
     * Get the user achievements for this achievement.
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class, 'achievement_id');
    }

    /**
     * Check if a user has earned this achievement.
     */
    public function hasBeenEarnedBy(int $userId): bool
    {
        return $this->userAchievements()
                    ->where('user_id', $userId)
                    ->exists();
    }

    /**
     * Get the number of times this achievement has been earned by a user.
     */
    public function getEarnedCountByUser(int $userId): int
    {
        return $this->userAchievements()
                    ->where('user_id', $userId)
                    ->count();
    }

    /**
     * Check if this achievement can be earned by a user.
     */
    public function canBeEarnedBy(int $userId): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->is_repeatable && $this->hasBeenEarnedBy($userId)) {
            return false;
        }

        if ($this->max_awards && $this->getEarnedCountByUser($userId) >= $this->max_awards) {
            return false;
        }

        return true;
    }

    /**
     * Scope for active achievements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for achievements by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
