<?php

declare(strict_types=1);

namespace Collectme;

use Collectme\Controller\AuthController;
use Collectme\Controller\SessionController;
use Collectme\Controller\UserController;
use Collectme\Misc\Auth;
use WP_REST_Server;

class RestRouterV1
{
    public function __construct(
        private readonly Auth $auth,
        private readonly AuthController $authController,
        private readonly UserController $userController,
        private readonly SessionController $sessionController,
    ) {
    }

    public function init(): void
    {
        $this->registerUserRoutes();
        $this->registerSessionRoutes();
    }

    private function registerUserRoutes(): void
    {
        register_rest_route(
            REST_V1_NAMESPACE,
            '/users/current',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this->userController, 'getCurrent'],
                'permission_callback' => [$this->auth, 'isAuthenticated'],
            ]
        );

        // todo: /users/form-auth

        register_rest_route(
            REST_V1_NAMESPACE,
            '/users/link-auth',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this->authController, 'loginWithToken'],
                'permission_callback' => '__return_true',
            ]
        );
    }

    private function registerSessionRoutes(): void
    {
        register_rest_route(
            REST_V1_NAMESPACE,
            '/sessions/current',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this->sessionController, 'getCurrent'],
                'permission_callback' => '__return_true',
            ]
        );
    }
}