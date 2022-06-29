<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\NotFoundResponseMaker;
use Collectme\Controller\Http\ResponseApiError;
use Collectme\Controller\Http\ResponseApiSuccess;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\UnauthorizedResponseMaker;
use Collectme\Controller\Http\UuidValidator;
use Collectme\Controller\Http\ValidationErrorResponseMaker;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Exceptions\CollectmeException;
use Collectme\Misc\Auth;
use Collectme\Misc\DB;
use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\SignatureEntry;
use Collectme\Model\JsonApi\ApiError;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class SignatureController extends WP_REST_Controller
{
    use UnauthorizedResponseMaker;
    use NotFoundResponseMaker;
    use SuccessResponseMaker;
    use ValidationErrorResponseMaker;

    public function __construct(
        private readonly Auth $auth
    ) {
    }

    public function add(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $userUuid = $this->auth->getUserUuid();
        } catch (CollectmeException) {
            return $this->makeUnauthorizedResponse();
        }

        $apiData = $request->get_json_params();

        if (empty($apiData['data'])) {
            return $this->makeValidationErrorResponse(['data']);
        }

        try {
            $entryProps = SignatureEntry::fromApiModelToPropsArray($apiData['data']);
        } catch (\Exception $e) {
            return new ResponseApiError(
                500,
                [new ApiError(500, 'Internal Server Error', exception: $e)]
            );
        }

        $errors = [];
        if (!array_key_exists('groupUuid', $entryProps) || !UuidValidator::check($entryProps['groupUuid'])) {
            $errors[] = '/data/relationships/group/data/id';
        }

        if (!array_key_exists('userUuid', $entryProps) || !UuidValidator::check($entryProps['userUuid'])) {
            $errors[] = '/data/relationships/user/data/id';
        }

        if (!array_key_exists('count', $entryProps)
            || !is_int($entryProps['count'])
            || 0 === $entryProps['count']
            || $entryProps['count'] > 10000
            || $entryProps['count'] < -10000
        ) {
            $errors[] = '/data/attributes/count';
        }

        if ($errors) {
            return $this->makeValidationErrorResponse($errors);
        }

        if ($entryProps['userUuid'] !== $userUuid) {
            return $this->makeUnauthorizedResponse();
        }

        try {
            $group = Group::get($entryProps['groupUuid']);
        } catch (CollectmeDBException) {
            return $this->makeValidationErrorResponse(['/data/relationships/group/data/id']);
        }

        if (!$group->userCanWrite($userUuid)) {
            return $this->makeUnauthorizedResponse();
        }

        unset(
            $entryProps['uuid'],
            $entryProps['activityLogUuid'],
        );

        try {
            $entry = DB::transactional(static function () use ($entryProps, $group) {
                $log = new ActivityLog(
                    null,
                    $group->type === EnumGroupType::PERSON ? EnumActivityType::PERSONAL_SIGNATURE : EnumActivityType::ORGANIZATION_SIGNATURE,
                    $entryProps['count'],
                    $group->causeUuid,
                    $group->uuid,
                );
                $log->save();

                /** @noinspection PhpParamsInspection */
                $entry = new SignatureEntry(
                    null,
                    ...$entryProps,
                    activityLogUuid: $log->uuid
                );
                return $entry->save();
            });
        } catch (\Exception $e) {
            return new ResponseApiError(
                500,
                [new ApiError(500, 'Internal Server Error', exception: $e)]
            );
        }

        return $this->makeSuccessResponse(201, $entry);
    }

    public function delete(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $userUuid = $this->auth->getUserUuid();
        } catch (CollectmeException) {
            return $this->makeUnauthorizedResponse();
        }

        $entryUuid = $request->get_param('uuid');

        try {
            $signatureEntry = SignatureEntry::get($entryUuid);
        } catch (CollectmeDBException $e) {
            return new ResponseApiSuccess(204, null);
        }

        if ($signatureEntry->userUuid !== $userUuid) {
            return $this->makeUnauthorizedResponse();
        }

        try {
            DB::transactional(static function () use ($signatureEntry) {
                $log = ActivityLog::get($signatureEntry->activityLogUuid);
                $log->delete();
                $signatureEntry->delete();
            });
        } catch (\Exception $e) {
            return new ResponseApiError(
                500,
                [new ApiError(500, 'Internal Server Error', exception: $e)]
            );
        }

        return new ResponseApiSuccess(204, null);
    }
}