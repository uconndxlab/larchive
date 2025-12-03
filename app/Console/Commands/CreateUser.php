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
    protected $signature = 'user:create
                            {--name= : The name of the user}
                            {--email= : The email address of the user}
                            {--password= : The password for the user}
                            {--role=contributor : The role of the user (contributor, curator, or admin)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user (interactive or with options)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if running in non-interactive mode (all options provided)
        $nonInteractive = $this->option('name') && $this->option('email') && $this->option('password');

        if ($nonInteractive) {
            return $this->createUserNonInteractive();
        }

        return $this->createUserInteractive();
    }

    /**
     * Create user in interactive mode.
     */
    protected function createUserInteractive()
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
            ['contributor', 'curator', 'admin'],
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

        return $this->createUser($name, $email, $role, $password);
    }

    /**
     * Create user in non-interactive mode.
     */
    protected function createUserNonInteractive()
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');
        $role = $this->option('role');

        // Validate inputs
        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
            ],
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
                'role' => 'required|in:contributor,curator,admin',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        return $this->createUser($name, $email, $role, $password);
    }

    /**
     * Create the user in the database.
     */
    protected function createUser(string $name, string $email, string $role, string $password)
    {
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
