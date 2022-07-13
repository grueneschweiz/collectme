<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\InternalServerErrorResponseMaker;
use Collectme\Controller\Http\NotFoundResponseMaker;
use Collectme\Controller\Http\ResponseApiError;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\UnauthorizedResponseMaker;
use Collectme\Controller\Validators\TokenValidator;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Exceptions\CollectmeException;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\JsonApi\ApiError;
use WP_REST_Request;
use WP_REST_Response;

class SessionController extends \WP_REST_Controller
{
    use UnauthorizedResponseMaker;
    use NotFoundResponseMaker;
    use SuccessResponseMaker;
    use InternalServerErrorResponseMaker;

    public function __construct(
        private readonly Auth $auth
    ) {
    }

    public function getCurrent(WP_REST_Request $request): WP_REST_Response
    {
        $session = $this->auth->getPersistentSession();

        if ($session) {
            return $this->makeSuccessResponse(200, $session);
        }

        $sessionUuid = $this->auth->getClaimedSessionUuid();

        if (!$sessionUuid) {
            return $this->makeUnauthorizedResponse();
        }

        try {
            $session = PersistentSession::get($sessionUuid);
            if ($session->isClosed()) {
                return $this->makeNotFoundResponse();
            }
        } catch (CollectmeDBException) {
            return $this->makeNotFoundResponse();
        }

        return $this->makeUnauthorizedResponse();
    }

    public function logout(WP_REST_Request $request): WP_REST_Response
    {
        $sessionUuid = $request->get_param('uuid');

        $loggedInSession = $this->auth->getPersistentSession();

        if ($loggedInSession?->uuid !== $sessionUuid) {
            return $this->makeUnauthorizedResponse();
        }

        try {
            $this->auth->logout();
        } catch (CollectmeDBException|CollectmeException $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        return new WP_REST_Response(null, 204);
    }
}