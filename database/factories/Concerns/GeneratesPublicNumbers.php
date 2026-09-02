<?php

namespace Database\Factories\Concerns;

trait GeneratesPublicNumbers
{
    protected function dcmsPublicNumber(string $prefix): string
    {
        return sprintf(
            '%s-%s-%05d',
            $prefix,
            now()->format('Y'),
            fake()->unique()->numberBetween(1, 99999),
        );
    }

    protected function chairCode(): string
    {
        return sprintf('CHAIR-%03d', fake()->unique()->numberBetween(1, 999));
    }
}
