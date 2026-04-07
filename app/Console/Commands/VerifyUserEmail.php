<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyUserEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:verify-email {username : The username of the user to verify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually verify a user\'s email and set their status to active';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');

        $user = User::where('username', $username)->first();

        if (!$user) {
            $this->error("User with username '{$username}' not found.");
            return 1;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info("User '{$username}' email is already verified.");
            return 0;
        }

        // Mark email as verified and set status to active
        $user->markEmailAsVerified();
        $user->update(['status' => 'active']);

        $this->info("✓ User '{$username}' email has been verified!");
        $this->info("✓ User status set to 'active'");
        $this->info("✓ User can now login to the system");

        return 0;
    }
}
