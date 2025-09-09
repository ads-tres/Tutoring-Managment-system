<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\Attendance;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create Roles
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'subordinate']);
        Role::firstOrCreate(['name' => 'tutor']);
        Role::firstOrCreate(['name' => 'parent']);
        Role::firstOrCreate(['name' => 'accountant']);
        Role::firstOrCreate(['name' => 'super-admin']);

        // 2. Create users with roles and their specific data from migrations
        $manager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Main Manager',
                'password' => Hash::make('password'),
                'phone' => '0912345678',
            ]
        )->assignRole('manager');

        $subordinate = User::firstOrCreate(
            ['email' => 'sub@example.com'],
            [
                'name' => 'Regional Supervisor',
                'password' => Hash::make('password'),
                'phone' => '0987654321',
            ]
        )->assignRole('subordinate');
        
        $superAdmin = User::firstOrCreate(
            ['email' => 'asratabel03@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('1234567'),
                'phone' => '0900000000',
            ]
        )->assignRole('super-admin');
        

        // Existing Tutor
        $tutor1 = User::firstOrCreate(
            ['email' => 'tutor@example.com'],
            [
                'name' => 'Sample Tutor',
                'password' => Hash::make('password'),
                'dob' => '1995-05-20',
                'monthly_target_hours' => 160,
                'salary_per_hour' => 150.00,
            ]
        )->assignRole('tutor');

        // New Tutor 2
        $tutor2 = User::firstOrCreate(
            ['email' => 'tutor2@example.com'],
            [
                'name' => 'Math Tutor',
                'password' => Hash::make('password'),
                'dob' => '1992-03-10',
                'monthly_target_hours' => 120,
                'salary_per_hour' => 165.00,
            ]
        )->assignRole('tutor');

        // Existing Parent
        $parent1 = User::firstOrCreate(
            ['email' => 'parent@example.com'],
            [
                'name' => 'Sample Parent',
                'password' => Hash::make('password'),
                'phone' => '0976543210',
            ]
        )->assignRole('parent');

        // New Parent 2
        $parent2 = User::firstOrCreate(
            ['email' => 'parent2@example.com'],
            [
                'name' => 'New Parent',
                'password' => Hash::make('password'),
                'phone' => '0966554433',
            ]
        )->assignRole('parent');

        // 3. Create students linked to parent users
        // Existing Student
        $student1 = Student::firstOrCreate(
            ['full_name' => 'Student One'],
            [
                'parent_id' => $parent1->id,
                'student_phone' => '0911223344',
                'sex' => 'M',
                'dob' => '2015-06-15',
                'father_name' => 'Father Name',
                'father_phone' => '0987654321',
                'mother_name' => 'Mother Name',
                'mother_phone' => '0976543210',
                'region' => 'Addis Ababa',
                'city' => 'Addis Ababa',
                'subcity' => 'Bole',
                'district' => 'Woreda 3',
                'kebele' => 'Kebele 10',
                'house_number' => '12',
                'street' => 'Main Street',
                'landmark' => 'Near Park',
                'school_name' => 'Demo School',
                'school_type' => 'private',
                'grade' => '5',
                'status' => 'active',
                'scheduled_days' => json_encode(['monday', 'wednesday', 'friday']),
                'start_time' => '16:00:00',
                'session_length_minutes' => 90,
                'session_duration' => 90,
                'start_date' => now()->subMonths(3)->toDateString(),
            ]
        );
        
        // New Student 2
        $student2 = Student::firstOrCreate(
            ['full_name' => 'Student Two'],
            [
                'parent_id' => $parent2->id,
                'student_phone' => '0955667788',
                'sex' => 'F',
                'dob' => '2012-01-25',
                'father_name' => 'John Doe',
                'mother_name' => 'Jane Doe',
                'region' => 'Oromia',
                'city' => 'Adama',
                'subcity' => 'Kebele 01',
                'school_name' => 'Public High School',
                'school_type' => 'public',
                'grade' => '8',
                'status' => 'active',
                'scheduled_days' => json_encode(['tuesday', 'thursday']),
                'start_time' => '15:30:00',
                'session_length_minutes' => 60,
                'session_duration' => 60,
                'start_date' => now()->subMonths(1)->toDateString(),
            ]
        );

        // 4. Create sample attendance records
        // For Student 1 & Tutor 1
        Attendance::firstOrCreate(
            [
                'student_id' => $student1->id,
                'tutor_id' => $tutor1->id,
                'scheduled_date' => now()->subDays(2)->toDateString(),
            ],
            [
                'type' => 'on-schedule',
                'actual_date' => now()->subDays(2)->toDateString(),
                'subject' => 'Math',
                'topic' => 'Algebra Basics',
                'duration' => 2,
                'comment1' => 'Covered basic algebra concepts.',
                'status' => 'filled',
                'payment_status' => 'unpaid',
            ]
        );

        // For Student 1 & Tutor 1 (additional)
        Attendance::firstOrCreate(
            [
                'student_id' => $student1->id,
                'tutor_id' => $tutor1->id,
                'scheduled_date' => now()->subDay()->toDateString(),
            ],
            [
                'type' => 'additional',
                'actual_date' => now()->subDay()->toDateString(),
                'subject' => 'English',
                'topic' => 'Grammar',
                'duration' => 1,
                'comment1' => 'Extra practice session on verb tenses.',
                'status' => 'approved',
                'payment_status' => 'unpaid',
            ]
        );

        // For Student 2 & Tutor 2
        Attendance::firstOrCreate(
            [
                'student_id' => $student2->id,
                'tutor_id' => $tutor2->id,
                'scheduled_date' => now()->subDays(1)->toDateString(),
            ],
            [
                'type' => 'on-schedule',
                'actual_date' => now()->subDays(1)->toDateString(),
                'subject' => 'Physics',
                'topic' => 'Kinematics',
                'duration' => 1,
                'comment1' => 'Introduced motion concepts.',
                'status' => 'approved',
                'payment_status' => 'unpaid',
            ]
        );
        
        // For Student 2 & Tutor 2 (rescheduled)
        Attendance::firstOrCreate(
            [
                'student_id' => $student2->id,
                'tutor_id' => $tutor2->id,
                'scheduled_date' => now()->subDays(3)->toDateString(),
            ],
            [
                'type' => 'rescheduled',
                'actual_date' => now()->subDays(2)->toDateString(),
                'subject' => 'Biology',
                'topic' => 'Cell Structure',
                'reason' => 'Student was sick.',
                'duration' => 1,
                'comment1' => 'Reviewed cell organelles.',
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]
        );

    }
}
