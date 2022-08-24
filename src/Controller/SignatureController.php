<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\InternalServerErrorResponseMaker;
use Collectme\Controller\Http\NotFoundResponseMaker;
use Collectme\Controller\Http\ResponseApiSuccess;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\UnauthorizedResponseMaker;
use Collectme\Controller\Http\ValidationErrorResponseMaker;
use Collectme\Controller\Validators\UuidValidator;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Exceptions\CollectmeException;
use Collectme\Misc\Auth;
use Collectme\Misc\DB;
use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\SignatureEntry;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class SignatureController extends WP_REST_Controller
{
    use UnauthorizedResponseMaker;
    use NotFoundResponseMaker;
    use SuccessResponseMaker;
    use ValidationErrorResponseMaker;
    use InternalServerErrorResponseMaker;

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
            return $this->makeInternalServerErrorResponse($e);
        }

        $errors = [];
        if (!UuidValidator::check($entryProps['groupUuid'] ?? null)) {
            $errors[] = '/data/relationships/group/data/id';
        }

        if (!UuidValidator::check($entryProps['userUuid'] ?? null)) {
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
            $objective = Objective::findHighestOfGroup($group->uuid);
        } catch (CollectmeDBException) {
            $objective = null;
        }

        if (!empty($objective)
            && $group->type === EnumGroupType::PERSON
            && $group->signatures() < $objective[0]->objective
            && ($group->signatures() + $entryProps['count']) >= $objective[0]->objective)
        {
            $log = new ActivityLog(
                null,
                EnumActivityType::PERSONAL_GOAL_ACHIEVED,
                $objective[0]->objective,
                $group->causeUuid,
                $group->uuid,
            );
        } else {
            $log = new ActivityLog(
                null,
                $group->type === EnumGroupType::PERSON ? EnumActivityType::PERSONAL_SIGNATURE : EnumActivityType::ORGANIZATION_SIGNATURE,
                $entryProps['count'],
                $group->causeUuid,
                $group->uuid,
            );
        }

        try {
            $entry = DB::transactional(static function () use ($log, $entryProps) {
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
            return $this->makeInternalServerErrorResponse($e);
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
            return $this->makeInternalServerErrorResponse($e);
        }

        return new ResponseApiSuccess(204, null);
    }
}