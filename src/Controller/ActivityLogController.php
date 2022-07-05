<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\InternalServerErrorResponseMaker;
use Collectme\Controller\Http\NotFoundResponseMaker;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\UnauthorizedResponseMaker;
use Collectme\Controller\Http\UuidValidator;
use Collectme\Controller\Http\ValidationErrorResponseMaker;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\Auth;
use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Group;
use Collectme\Model\EnumPaginationCursorPointsTo;
use Collectme\Model\EnumPaginationOrder;
use Collectme\Model\Filter;
use Collectme\Model\Paginator;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

use const Collectme\REST_V1_NAMESPACE;

class ActivityLogController extends WP_REST_Controller
{
    use SuccessResponseMaker;
    use UnauthorizedResponseMaker;
    use InternalServerErrorResponseMaker;
    use ValidationErrorResponseMaker;
    use NotFoundResponseMaker;

    public function __construct(
        private readonly Auth $auth
    ) {
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        if (!$this->auth->isAuthenticated()) {
            return $this->makeUnauthorizedResponse();
        }

        $causeUuid = $request->get_param('uuid');

        $paginatior = new Paginator();
        $paginatior->order = EnumPaginationOrder::DESC;
        if ($request->has_param('page') && isset($request->get_param('page')['cursor'])) {
            $paginatior->cursor = $request->get_param('page')['cursor'];
            if (!UuidValidator::check($paginatior->cursor)) {
                return $this->makeNotFoundResponse();
            }
            $paginatior->cursorPointsTo = EnumPaginationCursorPointsTo::tryFrom(
                $request->get_param('page')['points'] ?? 'last'
            );
            if ($paginatior->cursorPointsTo === null) {
                return $this->makeValidationErrorResponse([], ['page[points]']);
            }
        }

        $filter = new Filter('count', null);
        if ($request->has_param('filter') && isset($request->get_param('filter')['count'])) {
            $filterArg = $request->get_param('filter')['count'];
            $filterArg = preg_replace('/\s/', '', $filterArg);
            if (false === preg_match('/gt\((\d+)\)/', $filterArg, $matches)) {
                return $this->makeValidationErrorResponse([], ['filter[count]']);
            }
            $filter->value = (int)$matches[1];
            $filter->operator = '>';
        }

        try {
            $logs = ActivityLog::findByCause(
                $causeUuid,
                $paginatior,
                $filter
            );

            $groupsUuids = array_map(static fn(ActivityLog $log) => $log->groupUuid, $logs);
            $groups = Group::getMany($groupsUuids);
        } catch (CollectmeDBException $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        $jsonApiData = [
            'data' => $logs,
            'included' => $groups,
            'links' => $this->getLinks($causeUuid, $filter, $logs),
        ];

        return $this->makeSuccessResponse(200, $jsonApiData);
    }

    private function getLinks(string $causeUuid, Filter $filter, array $results): array
    {
        $path = rest_url(REST_V1_NAMESPACE . "/causes/$causeUuid/activity");
        $filterArgs = [];

        if (null !== $filter->value) {
            $filterArgs['filter[count]'] = "gt($filter->value)";
        }

        $firstResult = empty($results) ? null : reset($results);
        $lastResult = empty($results) ? null : end($results);

        $argsPrev = [
            'page[cursor]' => $firstResult?->uuid,
            'page[points]' => 'first',
            ...$filterArgs
        ];

        $argsNext = [
            'page[cursor]' => $lastResult?->uuid,
            'page[points]' => 'last',
            ...$filterArgs
        ];

        return [
            'first' => add_query_arg($filterArgs, $path),
            'last' => null,
            'prev' => $firstResult ? add_query_arg($argsPrev, $path) : null,
            'next' => $lastResult ? add_query_arg($argsNext, $path) : null,
        ];
    }
}