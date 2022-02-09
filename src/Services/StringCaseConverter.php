<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\String\UnicodeString;

class StringCaseConverter
{
    private const UPPERCASE_WORD_SPLIT_PATTERN = '#[a-z]{1}[A-Z]{1}#';

    public function convertCamelCaseToKebabCase(string $input): string
    {
        $output = (new UnicodeString($input))->replaceMatches(self::UPPERCASE_WORD_SPLIT_PATTERN, function ($match) {
            if (is_array($match) && is_string($match[0])) {
                $matchString = strtolower($match[0]);
                $characters = str_split($matchString);

                return implode('-', $characters);
            }

            return '';
        });

        if ((string) $output !== $input) {
            $output->lower();
        }

        return (string) $output;
    }
}
