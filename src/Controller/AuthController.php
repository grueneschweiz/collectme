<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\InternalServerErrorResponseMaker;
use Collectme\Controller\Http\ResponseApiError;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\UuidValidator;
use Collectme\Controller\Http\ValidationErrorResponseMaker;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Exceptions\CollectmeException;
use Collectme\Misc\Auth;
use Collectme\Misc\LoginEmail;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\JsonApi\ApiError;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class AuthController extends WP_REST_Controller
{
    use SuccessResponseMaker;
    use InternalServerErrorResponseMaker;

    public function __construct(
        private readonly Auth $auth
    ) {
    }

    public function loginWithToken(WP_REST_Request $request): WP_REST_Response
    {
        $token = $request->get_param('token');
        $email = $request->get_param('email');
        $causeUuid = $request->get_param('cause');

        if (!$this->isValidEmail($email)) {
            return $this->makeInvalidTokenResponse();
        }

        if (!$this->isValidTokenFormat($token)) {
            return $this->makeInvalidTokenResponse();
        }

        if (!$this->isValidCause($causeUuid)) {
            return new ResponseApiError(
                404,
                [new ApiError(404, 'Invalid Cause', parameter: 'cause')]
            );
        }

        try {
            $accountToken = AccountToken::getByEmailAndToken($email, $token);
        } catch (CollectmeDBException $e) {
            // token not found / invalid
            return $this->makeInvalidTokenResponse();
        }

        try {
            $user = $this->auth->getOrSetupUserFromAccountToken($accountToken, $causeUuid);
            $this->auth->createPersistentSession($user, true);
        } catch (\Exception $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        $session = $this->auth->getPersistentSession();

        if (!$session) {
            return $this->makeInvalidTokenResponse();
        }

        return $this->makeSuccessResponse(200, $session);
    }

    private function isValidEmail(?string $email): bool
    {
        if (!$email) {
            return false;
        }

        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function makeInvalidTokenResponse(): ResponseApiError
    {
        return new ResponseApiError(
            404,
            [new ApiError(404, 'Invalid Token', parameter: 'token')]
        );
    }

    private function isValidTokenFormat(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        return strlen($token) === 64
            && preg_match('/[[:alnum:]]{64}/', $token);
    }

    private function isValidCause(?string $causeUuid): bool
    {
        if (!$causeUuid) {
            return false;
        }

        if (!UuidValidator::check($causeUuid)) {
            return false;
        }

        try {
            Cause::get($causeUuid);
        } catch (CollectmeDBException) {
            return false;
        }

        return true;
    }
}