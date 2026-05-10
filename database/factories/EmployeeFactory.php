<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'partner_id' => null,
            'employee_number' => 'EMP-'.now()->format('Y').'-'.Str::upper(Str::random(5)),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => $this->faker->e164PhoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'position' => $this->faker->randomElement(['courier', 'dispatcher', 'technician']),
            'status' => $this->faker->randomElement(['active', 'inactive', 'on_leave']),
            'is_verified' => $this->faker->boolean(70),
            'background_check' => $this->faker->boolean(60),
            'hire_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'skills' => [$this->faker->randomElement(['delivery', 'installation', 'support'])],
            'metadata' => ['language' => $this->faker->randomElement(['en', 'no', 'uk'])],
        ];
    }
}
