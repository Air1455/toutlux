<?php

namespace App\Twig;

use App\Service\Admin\AdminStatsProvider;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Extension Twig pour injecter les stats admin dans tous les templates admin
 */
class AdminTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private AdminStatsProvider $statsProvider
    ) {}

    public function getGlobals(): array
    {
        return [
            'admin_stats' => $this->statsProvider,
        ];
    }
}
