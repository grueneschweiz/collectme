<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\InternalServerErrorResponseMaker;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\UnauthorizedResponseMaker;
use Collectme\Controller\Http\UuidValidator;
use Collectme\Controller\Http\ValidationErrorResponseMaker;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Exceptions\CollectmeException;
use Collectme\Misc\Auth;
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
        if (!array_key_exists('groupUuid', $entryProps) || !UuidValidator::check($entryProps['groupUuid'])) {
            $errors[] = '/data/relationships/group/data/id';
        }

        if (!array_key_exists('objective', $entryProps)
            || !is_int($entryProps['objective'])
            || $entryProps['objective'] <= 0
            || $entryProps['objective'] > PHP_INT_MAX
        ) {
            $errors[] = '/data/attributes/count';
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

        $objective = new Objective(
            null,
            $entryProps['objective'],
            $group->uuid,
            'app'
        );

        try {
            $objective = $objective->save();
        } catch (CollectmeDBException $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        return $this->makeSuccessResponse(201, $objective);
    }
}