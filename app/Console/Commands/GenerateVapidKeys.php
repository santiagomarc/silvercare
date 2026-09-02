<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Generates the VAPID keypair browser push requires (H8).
 *
 * Prints the lines to add to .env. Deliberately does not write to .env itself —
 * rotating these keys invalidates every existing subscription, so it should be
 * a deliberate paste, not a side effect of running a command.
 */
class GenerateVapidKeys extends Command
{
    protected $signature = 'push:generate-vapid-keys {--show-existing : Print the currently configured public key instead of generating a new pair}';

    protected $description = 'Generate a VAPID keypair for browser push notifications';

    public function handle(): int
    {
        if ($this->option('show-existing')) {
            $public = config('webpush.vapid.public_key');

            if (empty($public)) {
                $this->warn('No VAPID public key is configured.');

                return self::FAILURE;
            }

            $this->info('Configured VAPID public key:');
            $this->line($public);

            return self::SUCCESS;
        }

        if (! empty(config('webpush.vapid.private_key'))) {
            $this->warn('A VAPID keypair is already configured.');
            $this->line('Replacing it will invalidate every existing push subscription,');
            $this->line('and each caregiver will have to re-enable notifications.');

            if (! $this->confirm('Generate a new pair anyway?', false)) {
                $this->info('Cancelled. Existing keys left in place.');

                return self::SUCCESS;
            }
        }

        $keys = VAPID::createVapidKeys();

        $this->newLine();
        $this->info('Add these to your .env file:');
        $this->newLine();
        $this->line('VAPID_SUBJECT="mailto:alerts@your-domain.test"');
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->newLine();
        $this->comment('Keep the private key secret. The browser reads the public key from /api/push/config,');
        $this->comment('so no frontend rebuild is needed after changing these.');

        return self::SUCCESS;
    }
}
