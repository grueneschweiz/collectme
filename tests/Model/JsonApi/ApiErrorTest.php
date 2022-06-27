<?php /** @noinspection JsonEncodingApiUsageInspection */

declare(strict_types=1);

namespace Model\JsonApi;

use Collectme\Model\JsonApi\ApiError;
use PHPUnit\Framework\TestCase;

class ApiErrorTest extends TestCase
{

    public function test_jsonSerialize__pointer(): void
    {
        $error = new ApiError(
            403,
            'Invalid Token',
            'Invalid token format',
            '/data/attributes/token',
        );

        $actualJson = json_encode($error);
        $expectedJson = json_encode([
            'status' => 403,
            'title' => 'Invalid Token',
            'detail' => 'Invalid token format',
            'source' => ['pointer' => '/data/attributes/token']
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson, $actualJson);
    }

    public function test_jsonSerialize__parameter(): void
    {
        $error = new ApiError(
            403,
            'Invalid Token',
            'Invalid token format',
            null,
            '/data/attributes/token',
        );

        $actualJson = json_encode($error);
        $expectedJson = json_encode([
            'status' => 403,
            'title' => 'Invalid Token',
            'detail' => 'Invalid token format',
            'source' => ['parameter' => '/data/attributes/token']
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson, $actualJson);
    }

    public function test_jsonSerialize__titleOnly(): void
    {
        $error = new ApiError(
            403,
            'Invalid Token',
        );

        $actualJson = json_encode($error);
        $expectedJson = json_encode([
            'status' => 403,
            'title' => 'Invalid Token',
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson, $actualJson);
    }
}
