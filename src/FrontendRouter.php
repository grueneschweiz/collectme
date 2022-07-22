<?php

declare(strict_types=1);

namespace Collectme;

use Collectme\Controller\HtmlController;

class FrontendRouter
{
    private array $args = [];

    public function __construct(
        private readonly HtmlController $htmlController
    )
    {
    }

    /**
     * @throws \JsonException
     */
    public function init(array $args): string
    {
        $this->args = $args;

        return match($_REQUEST['action'] ?? null) {
            'create' => $this->create(),
            'activate' => $this->activate(),
            default => $this->index(),
        };
    }

    /**
     * @throws \JsonException
     */
    private function index(): string
    {
        return $this->htmlController->index($this->args['causeuuid']);
    }

    /**
     * @throws \JsonException
     */
    private function create(): string
    {
        return $this->htmlController->createUserFromToken($this->args['causeuuid']);
    }

    /**
     * @throws \JsonException
     */
    private function activate(): string
    {
        return $this->htmlController->activateSession($this->args['causeuuid']);
    }
}