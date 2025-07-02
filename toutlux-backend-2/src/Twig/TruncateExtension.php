<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Component\String\UnicodeString;

class TruncateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('truncate', [$this, 'truncate']),
        ];
    }

    public function truncate(?string $value, int $length = 30): string
    {
        if (!$value) {
            return '';
        }

        $string = new UnicodeString($value);

        return $string->length() > $length
            ? $string->truncate($length, '...')
            : (string) $string;
    }
}
