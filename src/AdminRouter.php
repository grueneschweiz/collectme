<?php

declare(strict_types=1);

namespace Collectme;

use Collectme\Controller\AdminController;

class AdminRouter
{
    public function __construct(
        private readonly AdminController $adminController
    ) {
    }

    public function init(): void
    {
        add_options_page(
            __('Collectme Settings', 'collectme'),
            __('Collectme', 'collectme'),
            'manage_options',
            'collectme_settings',
            [ $this->adminController, 'showSettings' ]
        );
    }
}