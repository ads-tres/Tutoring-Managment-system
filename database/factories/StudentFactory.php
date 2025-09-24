<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        // Get a random parent and a random tutor from the existing users table.
        $parentId = User::whereHas('roles', function ($query) {
            $query->where('name', 'parent');
        })->inRandomOrder()->first()->id ?? User::factory()->create()->id;

        $tutorId = User::whereHas('roles', function ($query) {
            $query->where('name', 'tutor');
        })->inRandomOrder()->first()->id ?? User::factory()->create()->id;
        
        // Define a list of possible scheduled days for the fake data.
        $possibleDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        // Select a random number of days for the student's schedule.
        $scheduledDays = fake()->randomElements($possibleDays, fake()->numberBetween(1, 4));

        return [
            'parent_id' => $parentId,
            'tutor_id' => $tutorId,
            'full_name' => fake()->name(),
            'student_phone' => fake()->phoneNumber(),
            'sex' => fake()->randomElement(['M', 'F']),
            'dob' => fake()->date(),
            'initial_skills' => json_encode(['reading', 'writing', 'math']), // Use a simple JSON array.
            'father_name' => fake()->name('male'),
            'father_phone' => fake()->phoneNumber(),
            'mother_name' => fake()->name('female'),
            'mother_phone' => fake()->phoneNumber(),
            'region' => fake()->state(),
            'city' => fake()->city(),
            'subcity' => fake()->city(),
            'district' => fake()->city(),
            'kebele' => fake()->word(),
            'house_number' => fake()->buildingNumber(),
            'street' => fake()->streetName(),
            'landmark' => fake()->streetAddress(),
            'school_name' => fake()->company(),
            'school_type' => fake()->randomElement(['private', 'public', 'international']),
            'grade' => fake()->numberBetween(1, 12),
            'frequency' => fake()->randomElement(['Once a week', 'Twice a week']),
            'scheduled_days' => $scheduledDays,
            'start_time' => fake()->time(),
            'session_length_minutes' => fake()->numberBetween(30, 120),
            'end_time' => fake()->time(),
            'session_duration' => fake()->numberBetween(30, 120),
            'status' => fake()->randomElement(['active', 'inactive']),
            'start_date' => fake()->date(),
            'student_image' => fake()->imageUrl(),
            'parents_image' => fake()->imageUrl(),
        ];
    }
}
