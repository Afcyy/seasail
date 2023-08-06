<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user['name'] = $this->ask('Name: ');
        $user['email'] = $this->ask('Email: ');
        $user['password'] = $this->secret('Password: ');
        $user['role'] = $this->choice('Role: ', ['admin', 'editor'], 1);

        $validator = Validator::make($user, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Password::default()],
            'role' => ['required', 'exists:roles,name'],
        ]);

        if($validator->fails()) {
            foreach ($validator->errors()->all() as $error){
                $this->error($error);
            }

            return -1;
        }

        DB::transaction(function () use($user) {
            $role = Role::where('name', $user['role'])->first();
            $user['password'] = Hash::make($user['password']);

            $newUser = User::create($user);
            $newUser->roles()->attach($role->id);
        });

        $this->info('User created successfully');

        return 0;
    }
}
