<?php

namespace Webkul\User\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'api_token' => Str::random(80),
            'status' => 1,
            'role_id' => Role::factory(),
            'view_permission' => 'global',
        ];
    }
}
