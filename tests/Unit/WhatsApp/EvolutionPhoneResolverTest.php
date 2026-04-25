<?php

namespace Tests\Unit\WhatsApp;

use App\Services\WhatsApp\EvolutionPhoneResolver;
use Tests\TestCase;

class EvolutionPhoneResolverTest extends TestCase
{
    public function test_to_phone_keeps_brazil_number_with_ninth_digit(): void
    {
        $resolver = new EvolutionPhoneResolver();

        $this->assertSame('5567993087866', $resolver->toPhone('5567993087866'));
    }

    public function test_to_phone_keeps_brazil_number_without_ninth_digit(): void
    {
        $resolver = new EvolutionPhoneResolver();

        $this->assertSame('556793087866', $resolver->toPhone('556793087866'));
    }

    public function test_to_phone_adds_country_code_for_local_brazil_mobile(): void
    {
        $resolver = new EvolutionPhoneResolver();

        $this->assertSame('5567993087866', $resolver->toPhone('67993087866'));
    }

    public function test_to_phone_keeps_non_brazil_international_number(): void
    {
        $resolver = new EvolutionPhoneResolver();

        $this->assertSame('14155552671', $resolver->toPhone('+1 (415) 555-2671'));
    }
}
