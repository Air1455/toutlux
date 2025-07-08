<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TruncateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('truncate', [$this, 'truncate']),
        ];
    }

    public function truncate(?string $value, int $length = 30, string $preserve = '...', string $separator = ' '): string
    {
        if (empty($value)) {
            return '';
        }

        if (mb_strlen($value) <= $length) {
            return $value;
        }

        $truncated = mb_substr($value, 0, $length);

        // If separator is provided, try to break at word boundary
        if ($separator !== '') {
            $lastSpace = mb_strrpos($truncated, $separator);
            if ($lastSpace !== false && $lastSpace > ($length * 0.75)) {
                $truncated = mb_substr($truncated, 0, $lastSpace);
            }
        }

        return $truncated . $preserve;
    }
}
