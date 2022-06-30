<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\InternalServerErrorResponseMaker;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\UnauthorizedResponseMaker;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Exceptions\CollectmeException;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\Role;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class GroupController extends WP_REST_Controller
{
    use SuccessResponseMaker;
    use UnauthorizedResponseMaker;
    use InternalServerErrorResponseMaker;

    public function __construct(
        private readonly Auth $auth
    ) {
    }

    public function findByCause(WP_REST_Request $request): WP_REST_Response
    {
        $causeUuid = $request->get_param('uuid');

        try {
            $userUuid = $this->auth->getUserUuid();
        } catch (CollectmeException) {
            return $this->makeUnauthorizedResponse();
        }

        try {
            $groups = Group::findByCauseAndReadableByUser($causeUuid, $userUuid);
        } catch (CollectmeDBException $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        $groupUuids = array_map(static fn($group) => $group->uuid, $groups);

        try {
            // convert to model and add writeable attribute
            $models = [];
            foreach ($groups as $group) {
                $model = $group->toApiModel();
                $model->attributes['_writeable'] = $group->userCanWrite($userUuid);
                $models[] = $model;
            }

            $included = [
                ...Objective::findByGroups($groupUuids),
                ...Role::findByGroups($groupUuids),
            ];
        } catch (CollectmeException|CollectmeDBException|\ReflectionException $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        $jsonApiData = [
            'data' => $models,
            'included' => $included,
        ];

        return $this->makeSuccessResponse(200, $jsonApiData);
    }
}