<?php

namespace Tests\Feature;

use App\Mail\WeeklyHealthReport;
use App\Models\User;
use App\Models\UserProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class WeeklyHealthReportsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_weekly_report_command_sends_email_to_caregiver_for_linked_patient(): void
    {
        $caregiverUser = User::factory()->create([
            'email' => 'caregiver@example.com',
        ]);

        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'caregiver-weekly',
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);

        $elderlyUser = User::factory()->create();
        $elderlyProfile = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'elderly-weekly',
            'profile_completed' => true,
            'profile_skipped' => false,
            'caregiver_id' => $caregiverProfile->id,
        ]);

        Mail::fake();

        $pdfMock = Mockery::mock(DomPdf::class);
        $pdfMock->shouldReceive('output')->andReturn('fake-pdf-binary');
        Pdf::shouldReceive('loadView')->once()->andReturn($pdfMock);

        Artisan::call('reports:send-weekly-health');

        Mail::assertSent(WeeklyHealthReport::class, function (WeeklyHealthReport $mail) use ($caregiverUser, $elderlyProfile) {
            return $mail->hasTo($caregiverUser->email)
                && $mail->elderlyProfile->id === $elderlyProfile->id;
        });
    }
}
