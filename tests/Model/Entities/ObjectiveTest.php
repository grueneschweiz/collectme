<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Objective;
use PHPUnit\Framework\TestCase;

class ObjectiveTest extends TestCase
{
    public function test_get(): void
    {
        $cause = new Cause(
            null,
            'test_'.wp_generate_password(),
        );
        $cause->save();

        $group = new Group(
            null,
            'test_'.wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $objective = new Objective(
            null,
            100,
            $group->uuid,
            'Newsletter 220401'
        );
        $objective->save();

        $objective = Objective::get($objective->uuid);

        $this->assertSame(100, $objective->objective);
        $this->assertSame($group->uuid, $objective->groupUuid);
        $this->assertSame('Newsletter 220401', $objective->source);
    }

    public function test_save(): void
    {
        $cause = new Cause(
            null,
            'test_'.wp_generate_password(),
        );
        $cause->save();

        $group = new Group(
            null,
            'test_'.wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $objective = new Objective(
            null,
            100,
            $group->uuid,
            'Newsletter 220401'
        );
        $objective->save();

        $this->assertNotEmpty($objective->uuid);
    }

    public function test_toApiModel(): void
    {
        $objective = new Objective(
            wp_generate_uuid4(),
            100,
            wp_generate_uuid4(),
            'Newsletter 220401',
            date_create('2022-06-28T17:10:15+00:00'),
            date_create('2022-06-28T17:10:16+00:00'),
            date_create('2022-06-28T17:10:17+00:00'),
        );

        $apiModel = $objective->toApiModel();

        $this->assertEqualsCanonicalizing(
            [
                'id' => $objective->uuid,
                'type' => 'objective',
                'attributes' => [
                    'objective' => 100,
                    'source' => 'Newsletter 220401',
                    'created' => date_create('2022-06-28T17:10:15+00:00')->format(DATE_RFC3339_EXTENDED),
                    'updated' => date_create('2022-06-28T17:10:16+00:00')->format(DATE_RFC3339_EXTENDED),
                ],
                'relationships' => [
                    'group' => [
                        'data' => [
                            'id' => $objective->groupUuid,
                            'type' => 'group'
                        ]
                    ]
                ]
            ],
            $apiModel->toArray()
        );
    }

    public function test_fromApiModelToPropsArray(): void
    {
        $apiData = [
            'id' => wp_generate_uuid4(),
            'type' => 'objective',
            'attributes' => [
                'objective' => 100,
                'source' => 'Newsletter 220401',
            ],
            'relationships' => [
                'group' => [
                    'data' => [
                        'id' => wp_generate_uuid4(),
                        'type' => 'group'
                    ]
                ]
            ]
        ];

        $props = Objective::fromApiModelToPropsArray($apiData);

        $objective = new Objective(...$props);

        $this->assertSame($apiData['id'], $objective->uuid);
        $this->assertSame(100, $objective->objective);
        $this->assertSame('Newsletter 220401', $objective->source);
        $this->assertSame($apiData['relationships']['group']['data']['id'], $objective->groupUuid);
    }

}
