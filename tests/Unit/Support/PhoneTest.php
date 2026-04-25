<?php

namespace Tests\Unit\Support;

use App\Support\Phone;
use Tests\TestCase;

class PhoneTest extends TestCase
{
    public function test_normalize_for_brazil_waha_removes_ninth_digit_from_country_code_mobile(): void
    {
        $this->assertSame('556793087866', Phone::normalizeForBrazil('5567993087866', true));
    }

    public function test_normalize_for_brazil_waha_keeps_number_without_ninth_digit(): void
    {
        $this->assertSame('556793087866', Phone::normalizeForBrazil('556793087866', true));
    }

    public function test_normalize_for_brazil_waha_adds_country_code_for_local_mobile(): void
    {
        $this->assertSame('556793087866', Phone::normalizeForBrazil('67993087866', true));
    }

    public function test_normalize_for_brazil_evolution_keeps_ninth_digit(): void
    {
        $this->assertSame('5567993087866', Phone::normalizeForBrazil('5567993087866', false));
    }

    public function test_normalize_for_brazil_evolution_keeps_without_ninth_digit(): void
    {
        $this->assertSame('556793087866', Phone::normalizeForBrazil('556793087866', false));
    }

    public function test_normalize_for_brazil_evolution_adds_country_code_for_local_mobile(): void
    {
        $this->assertSame('5567993087866', Phone::normalizeForBrazil('67993087866', false));
    }

    public function test_normalize_for_brazil_falls_back_for_non_brazil_numbers(): void
    {
        $this->assertSame('14155552671', Phone::normalizeForBrazil('+1 (415) 555-2671', true));
    }
}
