<?php

declare(strict_types=1);

namespace Collectme;

use Collectme\Controller\AuthController;
use Collectme\Controller\Http\UuidValidator;
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
                // permission checking is done in controller
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            REST_V1_NAMESPACE,
            '/sessions/(?P<uuid>[a-zA-Z0-9-]{36})/activate',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this->sessionController, 'activate'],
                'permission_callback' => '__return_true',
                'args' => [
                    'uuid' => [
                        'validate_callback' => [UuidValidator::class, 'check']
                    ]
                ],
            ]
        );

        register_rest_route(
            REST_V1_NAMESPACE,
            '/sessions/(?P<uuid>[a-zA-Z0-9-]{36})',
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this->sessionController, 'logout'],
                'permission_callback' => [$this->auth, 'isAuthenticated'],
                'args' => [
                    'uuid' => [
                        'validate_callback' => [UuidValidator::class, 'check']
                    ]
                ],
            ]
        );
    }
}