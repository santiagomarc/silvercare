<?php

namespace App\Support;

class CommaSeparatedValueParser
{
    /**
     * Normalize a comma-separated string into a trimmed string array.
     *
     * @return array<int, string>
     */
    public static function parse(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            fn (string $item) => $item !== ''
        ));
    }
}