<?php

declare(strict_types=1);

namespace Collectme;

use Collectme\Controller\ActivityLogController;
use Collectme\Controller\AuthController;
use Collectme\Controller\GroupController;
use Collectme\Controller\Http\UuidValidator;
use Collectme\Controller\ObjectiveController;
use Collectme\Controller\SessionController;
use Collectme\Controller\SignatureController;
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
        private readonly GroupController $groupController,
        private readonly SignatureController $signatureController,
        private readonly ObjectiveController $objectiveController,
        private readonly ActivityLogController $activityLogController,
    ) {
    }

    public function init(): void
    {
        $this->registerUserRoutes();
        $this->registerSessionRoutes();
        $this->registerGroupRoutes();
        $this->registerSignatureRoutes();
        $this->registerObjectiveRoutes();
        $this->registerActivityLogRoutes();
    }

    private function registerUserRoutes(): void
    {
        register_rest_route(
            REST_V1_NAMESPACE,
            '/users/current',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this->userController, 'getCurrent'],
                'permission_callback' => [$this->auth, 'isAuthenticatedAndHasValidNonce'],
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
                'permission_callback' => [$this->auth, 'isAuthenticatedAndHasValidNonce'],
                'args' => [
                    'uuid' => [
                        'validate_callback' => [UuidValidator::class, 'check']
                    ]
                ],
            ]
        );
    }

    public function registerGroupRoutes(): void
    {
        register_rest_route(
            REST_V1_NAMESPACE,
            '/causes/(?P<uuid>[a-zA-Z0-9-]{36})/groups',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this->groupController, 'findByCause'],
                'permission_callback' => [$this->auth, 'isAuthenticatedAndHasValidNonce'],
                'args' => [
                    'uuid' => [
                        'validate_callback' => [UuidValidator::class, 'check']
                    ]
                ],
            ]
        );
    }

    public function registerSignatureRoutes(): void
    {
        register_rest_route(
            REST_V1_NAMESPACE,
            '/signatures',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this->signatureController, 'add'],
                'permission_callback' => [$this->auth, 'isAuthenticatedAndHasValidNonce'],
            ]
        );

        register_rest_route(
            REST_V1_NAMESPACE,
            '/signatures/(?P<uuid>[a-zA-Z0-9-]{36})',
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this->signatureController, 'delete'],
                'permission_callback' => [$this->auth, 'isAuthenticatedAndHasValidNonce'],
                'args' => [
                    'uuid' => [
                        'validate_callback' => [UuidValidator::class, 'check']
                    ]
                ],
            ]
        );
    }

    public function registerObjectiveRoutes(): void
    {
        register_rest_route(
            REST_V1_NAMESPACE,
            '/signatures',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this->objectiveController, 'add'],
                'permission_callback' => [$this->auth, 'isAuthenticatedAndHasValidNonce'],
            ]
        );
    }

    public function registerActivityLogRoutes()
    {
        register_rest_route(
            REST_V1_NAMESPACE,
            '/causes/(?P<uuid>[a-zA-Z0-9-]{36})/activity',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this->activityLogController, 'index'],
                'permission_callback' => [$this->auth, 'isAuthenticatedAndHasValidNonce'],
                'args' => [
                    'uuid' => [
                        'validate_callback' => [UuidValidator::class, 'check']
                    ]
                ],
            ]
        );
    }
}