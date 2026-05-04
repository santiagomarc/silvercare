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

        $trimmed = trim($value);

        // Special case: if the value is exactly "none" (case-insensitive),
        // return ["none"] immediately without further parsing.
        // This handles the "None (Wala)" checkbox selection in profile forms.
        if (strtolower($trimmed) === 'none') {
            return ['none'];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            fn (string $item) => $item !== ''
        ));
    }
}