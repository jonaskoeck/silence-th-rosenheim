<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Weekday;
use PHPUnit\Framework\TestCase;

class WeekdayTest extends TestCase
{
    public function test_combine_returns_zero_for_empty_input(): void
    {
        $this->assertSame(0, Weekday::combine([]));
    }

    public function test_combine_ors_each_case_value(): void
    {
        $this->assertSame(
            Weekday::MONDAY->value | Weekday::FRIDAY->value,
            Weekday::combine([Weekday::MONDAY, Weekday::FRIDAY]),
        );
    }

    public function test_combine_all_cases_equals_one_hundred_twenty_seven(): void
    {
        $this->assertSame(127, Weekday::combine(Weekday::cases()));
    }

    public function test_unpack_returns_empty_array_for_zero(): void
    {
        $this->assertSame([], Weekday::unpack(0));
    }

    public function test_unpack_returns_correct_cases_for_mixed_bitmask(): void
    {
        $this->assertSame(
            [Weekday::MONDAY, Weekday::WEDNESDAY, Weekday::FRIDAY],
            Weekday::unpack(Weekday::MONDAY->value | Weekday::WEDNESDAY->value | Weekday::FRIDAY->value),
        );
    }

    public function test_unpack_ignores_bits_outside_defined_cases(): void
    {
        $this->assertSame(
            Weekday::cases(),
            Weekday::unpack(255),
        );
    }

    public function test_combine_and_unpack_round_trip(): void
    {
        $cases = [Weekday::TUESDAY, Weekday::THURSDAY, Weekday::SUNDAY];

        $this->assertSame($cases, Weekday::unpack(Weekday::combine($cases)));
    }
}
