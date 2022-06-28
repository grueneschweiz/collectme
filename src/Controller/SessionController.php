<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\NotFoundResponseMaker;
use Collectme\Controller\Http\ResponseApiError;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\UnauthorizedResponseMaker;
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

    public function activate(WP_REST_Request $request): WP_REST_Response
    {
        if (!$this->hasValidToken($request)) {
            return $this->makeInvalidTokenResponse();
        }

        $activationSecret = $request->get_param('token');
        $sessionUuid = $request->get_param('uuid');

        try {
            $session = PersistentSession::get($sessionUuid);

            if ($session->isClosed()) {
                return $this->makeNotFoundResponse();
            }

            if ($session->isActivated()) {
                return new WP_REST_Response(null, 204);
            }

            if (hash_equals($session->activationSecret, $activationSecret)) {
                $session->activated = date_create('-1 second');
                $session->save();
                return new WP_REST_Response(null, 204);
            }

            return $this->makeInvalidTokenResponse();
        } catch (CollectmeDBException) {
            return $this->makeNotFoundResponse();
        }
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
            return new ResponseApiError(
                500,
                [new ApiError(500, 'Internal Server Error', exception: $e)]
            );
        }

        return new WP_REST_Response(null, 204);
    }

    private function hasValidToken(WP_REST_Request $request): bool
    {
        if (!$request->has_param('token')) {
            return false;
        }

        $token = $request->get_param('token');

        return strlen($token) === 64
            && preg_match('/[[:alnum:]]{64}/', $token);
    }

    private function makeInvalidTokenResponse(): ResponseApiError
    {
        return new ResponseApiError(
            404,
            [new ApiError(404, 'Invalid Token', parameter: 'token')]
        );
    }
}