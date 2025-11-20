<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user with prompts for all fields';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Create a new user');
        $this->newLine();

        // Get user details
        $name = $this->ask('Name');
        
        $email = $this->ask('Email');
        
        // Validate email
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            $this->error('Error: ' . $validator->errors()->first('email'));
            return 1;
        }

        // Get role
        $role = $this->choice(
            'User role',
            ['standard', 'admin'],
            0
        );

        // Get password
        $password = $this->secret('Password (min 8 characters)');
        
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.');
            return 1;
        }

        $passwordConfirm = $this->secret('Confirm password');
        
        if ($password !== $passwordConfirm) {
            $this->error('Passwords do not match.');
            return 1;
        }

        // Confirm creation
        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $name],
                ['Email', $email],
                ['Role', $role],
            ]
        );

        if (!$this->confirm('Create this user?', true)) {
            $this->info('User creation cancelled.');
            return 0;
        }

        // Create user
        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'password' => Hash::make($password),
            ]);

            $this->newLine();
            $this->info('✓ User created successfully!');
            $this->table(
                ['ID', 'Name', 'Email', 'Role'],
                [[$user->id, $user->name, $user->email, $user->role]]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to create user: ' . $e->getMessage());
            return 1;
        }
    }
}
