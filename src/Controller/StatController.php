<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\InternalServerErrorResponseMaker;
use Collectme\Controller\Http\SuccessResponseMaker;
use Collectme\Controller\Http\ValidationErrorResponseMaker;
use Collectme\Controller\Validators\CauseUuidValidator;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\Stat;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class StatController extends WP_REST_Controller
{
    use SuccessResponseMaker;
    use ValidationErrorResponseMaker;
    use InternalServerErrorResponseMaker;

    public function __construct(
    ) {
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $causeUuid = $request->get_param('uuid');

        if (!CauseUuidValidator::check($causeUuid)) {
            return $this->makeValidationErrorResponse([], ['uuid']);
        }

        try {
            $stat = Stat::getByCause($causeUuid);
        } catch (CollectmeDBException $e) {
            return $this->makeInternalServerErrorResponse($e);
        }

        return $this->makeSuccessResponse(200, $stat);
    }
}