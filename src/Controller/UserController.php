<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\NotFoundResponseMaker;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\UnauthorizedResponseMaker;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\User;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class UserController extends WP_REST_Controller
{
    use UnauthorizedResponseMaker;
    use NotFoundResponseMaker;
    use SuccessResponseMaker;

    public function __construct(
        private readonly Auth $auth
    ) {
    }

    public function getCurrent(WP_REST_Request $request): WP_REST_Response
    {
        $session = $this->auth->getPersistentSession();
        if (!$session) {
            return $this->makeUnauthorizedResponse();
        }

        try {
            $user = User::get($session->userUuid);
        } catch (CollectmeDBException $e) {
            return $this->makeNotFoundResponse();
        }

        return $this->makeSuccessResponse(200, $user);
    }
}