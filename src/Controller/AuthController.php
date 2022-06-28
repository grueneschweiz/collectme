<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\ResponseApiError;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\UuidValidator;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\Cause;
use Collectme\Model\JsonApi\ApiError;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class AuthController extends WP_REST_Controller
{
    use SuccessResponseMaker;

    public function __construct(
        private readonly Auth $auth
    ) {
    }

    public function loginWithToken(WP_REST_Request $request): WP_REST_Response
    {
        if (!$this->hasValidEmail($request)) {
            return $this->makeInvalidTokenResponse();
        }

        if (!$this->hasValidToken($request)) {
            return $this->makeInvalidTokenResponse();
        }

        if (!$this->hasValidCause($request)) {
            return new ResponseApiError(
                404,
                [new ApiError(404, 'Invalid Cause', parameter: 'cause')]
            );
        }

        $token = $request->get_param('token');
        $email = $request->get_param('email');
        $causeUuid = $request->get_param('cause');

        try {
            $accountToken = AccountToken::getByEmailAndToken($email, $token);
            $user = $this->auth->getOrCreateUserFromAccountToken($accountToken);
            $user->addCause($causeUuid);
            $this->auth->createPersistentSession($user, true);
        } catch (CollectmeDBException $e) {
            // token is not valid
        }

        $session = $this->auth->getPersistentSession();

        if (!$session) {
            return $this->makeInvalidTokenResponse();
        }

        return $this->makeSuccessResponse(200, $session);
    }

    private function hasValidEmail(WP_REST_Request $request): bool
    {
        if (!$request->has_param('email')) {
            return false;
        }

        $email = $request->get_param('email');

        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function makeInvalidTokenResponse(): ResponseApiError
    {
        return new ResponseApiError(
            404,
            [new ApiError(404, 'Invalid Token', parameter: 'token')]
        );
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

    private function hasValidCause(WP_REST_Request $request): bool
    {
        if (!$request->has_param('cause')) {
            return false;
        }

        $cause = $request->get_param('cause');

        if (!UuidValidator::check($cause)) {
            return false;
        }

        try {
            Cause::get($cause);
        } catch (CollectmeDBException) {
            return false;
        }

        return true;
    }
}