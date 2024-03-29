<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\InternalServerErrorResponseMaker;
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
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class ObjectiveController extends WP_REST_Controller
{
    use UnauthorizedResponseMaker;
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
            $entryProps = Objective::fromApiModelToPropsArray($apiData['data']);
        } catch (\Exception $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        $errors = [];
        if (!UuidValidator::check($entryProps['groupUuid'] ?? null)) {
            $errors[] = '/data/relationships/group/data/id';
        }

        if (!array_key_exists('objective', $entryProps)
            || !is_int($entryProps['objective'])
            || $entryProps['objective'] <= 0
            || $entryProps['objective'] > PHP_INT_MAX
        ) {
            $errors[] = '/data/attributes/objective';
        }

        if ($errors) {
            return $this->makeValidationErrorResponse($errors);
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
            $entryProps['source'],
        );

        try {
            $objectives = Objective::findByGroups([$group->uuid]);
        } catch (CollectmeDBException $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        $equalObjectives = array_values(
            array_filter(
                $objectives,
                static fn(Objective $objective) => $objective->objective === $entryProps['objective']
            )
        );

        if (!empty($equalObjectives)) {
            return $this->makeSuccessResponse(200, $equalObjectives[0]);
        }

        $greaterObjectives = array_filter(
            $objectives,
            static fn(Objective $objective) => $objective->objective > $entryProps['objective']
        );

        if (!empty($greaterObjectives)) {
            return $this->makeValidationErrorResponse(['/data/attributes/objective']);
        }

        $objective = new Objective(
            null,
            $entryProps['objective'],
            $group->uuid,
            'app'
        );

        $log = null;
        if ($group->type === EnumGroupType::PERSON) {
            $type = empty($objectives) ? EnumActivityType::PLEDGE : EnumActivityType::PERSONAL_GOAL_RAISED;

            $log = new ActivityLog(
                null,
                $type,
                $objective->objective,
                $group->causeUuid,
                $group->uuid,
            );
        }

        try {
            $objective = DB::transactional(static function () use ($objective, $log) {
                $log?->save();
                return $objective->save();
            });
        } catch (\Exception $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        return $this->makeSuccessResponse(201, $objective);
    }
}