<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

/**
 * The sign-in accounts for local development.
 *
 * DemoDataSeeder attaches vitals, medications and checklists to an account
 * that already exists — it does not create one. This seeder is that missing
 * first step, so a fresh database can be brought back to a state you can log
 * into with:
 *
 *     php artisan db:seed
 *
 * It is idempotent: running it again will not duplicate anything, and it will
 * not overwrite the password of an account that already exists (pass
 * SEED_RESET_PASSWORD=true to force that).
 *
 * Edit the constants below to match the accounts you actually sign in with.
 */
class AccountsSeeder extends Seeder
{
    /** The account you sign in as. DemoDataSeeder looks for this exact email. */
    private const OWNER_EMAIL = 'santiagomarcstephen@gmail.com';
    private const OWNER_NAME  = 'Marc Santiago';

    /**
     * A caregiver to link the owner account to. DemoDataSeeder passes
     * $profile->caregiver_id into the medication and checklist seeding, so
     * without one those records are created unattributed.
     */
    private const CAREGIVER_EMAIL = 'caregiver@silvercare.test';
    private const CAREGIVER_NAME  = 'Care Partner';

    public function run(): void
    {
        $password = env('SEED_PASSWORD', 'password');
        $reset    = filter_var(env('SEED_RESET_PASSWORD', false), FILTER_VALIDATE_BOOL);

        // Caregiver first: the owner's profile references it.
        $caregiverUser = $this->account(self::CAREGIVER_EMAIL, self::CAREGIVER_NAME, $password, $reset);

        $caregiverProfile = UserProfile::updateOrCreate(
            ['user_id' => $caregiverUser->id],
            [
                'user_type'         => 'caregiver',
                'username'          => 'care-partner',
                'phone_number'      => '+639170000002',
                'relationship'      => 'Daughter',
                'profile_completed' => true,
                'profile_skipped'   => false,
                'is_active'         => true,
            ],
        );

        $ownerUser = $this->account(self::OWNER_EMAIL, self::OWNER_NAME, $password, $reset);

        UserProfile::updateOrCreate(
            ['user_id' => $ownerUser->id],
            [
                'user_type'              => 'elderly',
                'username'               => 'marc',
                'caregiver_id'           => $caregiverProfile->id,
                'phone_number'           => '+639170000001',
                // Placeholders so the profile does not read as empty — edit
                // them to whatever you want to develop against.
                'age'                    => 70,
                'height'                 => 170,
                'weight'                 => 70,
                'medical_conditions'     => ['Hypertension'],
                'medications'            => ['Amlodipine'],
                'allergies'              => [],
                'emergency_name'         => self::CAREGIVER_NAME,
                'emergency_phone'        => '+639170000002',
                'emergency_relationship' => 'Daughter',
                'profile_completed'      => true,
                'profile_skipped'        => false,
                'is_active'              => true,
            ],
        );

        $this->command->info('Accounts ready:');
        $this->command->line('  ' . self::OWNER_EMAIL . '  (elderly)');
        $this->command->line('  ' . self::CAREGIVER_EMAIL . '  (caregiver)');
        $this->command->line('  password: ' . $password);
    }

    /**
     * Create the account if it is missing, and leave an existing one alone
     * unless a password reset was explicitly asked for.
     */
    private function account(string $email, string $name, string $password, bool $reset): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => $password, // hashed by the model's cast
                'email_verified_at' => now(),
            ],
        );

        if ($reset && ! $user->wasRecentlyCreated) {
            $user->forceFill(['password' => $password])->save();
            $this->command->warn("Password reset for {$email}");
        }

        return $user;
    }
}
