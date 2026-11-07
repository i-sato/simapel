<?php

namespace Tests\Unit\Domain\Training;

use App\Domain\Training\InvalidTraining;
use App\Domain\Training\TrainingDetails;
use App\Domain\Training\TrainingSchedule;
use PHPUnit\Framework\TestCase;
use Tests\Support\TrainingBuilder;

final class TrainingDetailsTest extends TestCase
{
    public function test_draft_only_needs_a_name(): void
    {
        $details = new TrainingDetails('  Draft awal  ', TrainingSchedule::empty(), partnerLocation: '  ');

        $this->assertSame('Draft awal', $details->name);
        $this->assertNull($details->partnerLocation);
        $this->assertSame(array_keys(TrainingDetails::REQUIRED_FOR_FINAL), array_keys($details->missingForFinal()));
    }

    public function test_complete_details_have_nothing_missing(): void
    {
        $this->assertSame([], TrainingBuilder::completeDetails()->missingForFinal());
    }

    public function test_name_is_required(): void
    {
        $this->expectException(InvalidTraining::class);
        new TrainingDetails('   ', TrainingSchedule::empty());
    }

    public function test_numbers_must_be_positive_integers(): void
    {
        foreach (['batch', 'lessonHours', 'participantTarget', 'registrationQuota'] as $argument) {
            foreach ([0, -1, TrainingDetails::MAX_NUMBER + 1] as $value) {
                try {
                    new TrainingDetails('Uji', TrainingSchedule::empty(), ...[$argument => $value]);
                    $this->fail("{$argument}={$value} seharusnya ditolak.");
                } catch (InvalidTraining $exception) {
                    $this->assertNotSame('name', $exception->field());
                }
            }
        }
    }

    public function test_long_partner_location_is_rejected(): void
    {
        $this->expectException(InvalidTraining::class);
        new TrainingDetails('Uji', TrainingSchedule::empty(), partnerLocation: str_repeat('a', 256));
    }
}
