<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\InternalServerErrorResponseMaker;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\ValidationErrorResponseMaker;
use Collectme\Controller\Validators\CauseUuidValidator;
use Collectme\Controller\Validators\EmailValidator;
use Collectme\Controller\Validators\StringValidator;
use Collectme\Controller\Validators\UrlValidator;
use Collectme\Email\LoginEmail;
use Collectme\Exceptions\CollectmeException;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\EnumLang;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class AuthController extends WP_REST_Controller
{
    use SuccessResponseMaker;
    use InternalServerErrorResponseMaker;
    use ValidationErrorResponseMaker;

    public function __construct(
        private readonly Auth $auth,
        private readonly LoginEmail $loginEmail,
    ) {
    }

    public function loginWithFormData(WP_REST_Request $request): WP_REST_Response
    {
        // START: user data validation
        if (!$request->has_param('data')) {
            return $this->makeValidationErrorResponse(['/data']);
        }

        $data = $request->get_param('data');

        if (!is_array($data) || empty($data['attributes']) || !is_array($data['attributes'])) {
            return $this->makeValidationErrorResponse(['/data/attributes']);
        }

        $attributes = array_map(static fn($item) => trim((string)$item), $data['attributes']);

        $errors = [];

        if (!EmailValidator::check($attributes['email'] ?? null)) {
            $errors[] = '/data/attributes/email';
        }

        if (!StringValidator::check($attributes['firstName'] ?? null, 2, 45)) {
            $errors[] = "/data/attributes/firstName";
        }

        if (!StringValidator::check($attributes['lastName'] ?? null, 2, 45)) {
            $errors[] = "/data/attributes/lastName";
        }

        if (!StringValidator::check($attributes['urlAuth'] ?? null, 32, 32)) {
            $errors[] = '/data/attributes/urlAuth';
        }

        if (!UrlValidator::check($attributes['appUrl'] ?? null, 'http')) {
            $errors[] = '/data/attributes/appUrl';
        }

        if (!CauseUuidValidator::check($data['relationships']['cause']['data']['id'] ?? null)) {
            $errors[] = '/data/relationships/cause/data/id';
        }

        if ($errors) {
            return $this->makeValidationErrorResponse($errors);
        }

        $urlAuth = $attributes['urlAuth'];
        $appUrlHash = wp_hash($attributes['appUrl'], 'nonce');
        if (!hash_equals($urlAuth, $appUrlHash)) {
            $errors[] = '/data/attributes/urlAuth';
            $errors[] = '/data/attributes/appUrl';
            return $this->makeValidationErrorResponse($errors);
        }
        // END: user data validation

        $email = $attributes['email'];
        $firstName = $attributes['firstName'];
        $lastName = $attributes['lastName'];
        $causeUuid = $data['relationships']['cause']['data']['id'];
        $lang = $this->getLang();

        try {
            $user = $this->auth->getOrSetupUser(
                $email,
                $firstName,
                $lastName,
                $lang,
                'app',
                $causeUuid
            );
            $this->auth->createPersistentSession($user, false);
        } catch (\Exception $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        $session = $this->auth->getPersistentSession();

        if (!$session) {
            // this should never ever happen
            return $this->makeInternalServerErrorResponse(new CollectmeException('Missing session'));
        }

        try {
            $this->loginEmail->user = $user;
            $this->loginEmail->session = $session;
            $this->loginEmail->appUrl = $attributes['appUrl'];
            $this->loginEmail->causeUuid = $causeUuid;

            $this->loginEmail->send();
        } catch (CollectmeException $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        return $this->makeSuccessResponse(201, $session);
    }

    private function getLang(): EnumLang
    {
        return EnumLang::tryFrom(substr(get_locale(), 0, 1)) ?? EnumLang::EN;
    }
}