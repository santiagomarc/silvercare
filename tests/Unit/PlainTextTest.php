<?php

namespace Tests\Unit;

use App\Support\PlainText;
use PHPUnit\Framework\TestCase;

class PlainTextTest extends TestCase
{
    public function test_it_strips_the_severity_emoji_the_rules_engine_prefixes(): void
    {
        $this->assertSame('SOS Emergency Alert from Marc', PlainText::title('🚨 SOS Emergency Alert from Marc'));
        $this->assertSame('Critical Blood Pressure (185/120 mmHg)', PlainText::title('🚨 Critical Blood Pressure (185/120 mmHg)'));
    }

    public function test_it_strips_the_variation_selector_that_follows_a_warning_sign(): void
    {
        // ⚠️ is two code points: U+26A0 and the invisible U+FE0F. Leaving the
        // selector behind would pass a visual check and still fail a text one.
        $this->assertSame('3 missed doses: Amlodipine', PlainText::title('⚠️ 3 missed doses: Amlodipine'));
    }

    public function test_it_strips_dingbats_that_unicode_does_not_class_as_pictographic(): void
    {
        // ✓ and ✗ render as emoji but sit outside Extended_Pictographic. The
        // notification titles use them, so a pictographic-only rule misses them.
        $this->assertSame('Medication taken', PlainText::title('✓ Medication taken'));
        $this->assertSame('Dose missed', PlainText::title('✗ Dose missed'));
    }

    public function test_it_leaves_plain_text_and_numbers_alone(): void
    {
        $this->assertSame('Temperature 37.5 °C', PlainText::title('Temperature 37.5 °C'));
        $this->assertSame('', PlainText::title(null));
    }
}
