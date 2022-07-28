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

    public function test_totalByCauseAndType(): void
    {
        $cause1 = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause1->save();

        $cause2 = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause2->save();

        $group1 = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause1->uuid,
            false,
        );
        $group1->save();

        $group2 = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause1->uuid,
            false,
        );
        $group2->save();

        $groupOrga = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::ORGANIZATION,
            $cause1->uuid,
            false,
        );
        $groupOrga->save();

        $groupCause2 = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause2->uuid,
            false,
        );
        $groupCause2->save();

        $objective11 = new Objective(
            null,
            1,
            $group1->uuid,
            'Newsletter 220401'
        );
        $objective11->save();

        $objective12 = new Objective(
            null,
            10,
            $group1->uuid,
            'Newsletter 220401'
        );
        $objective12->save();

        $objective21 = new Objective(
            null,
            100,
            $group2->uuid,
            'Newsletter 220401'
        );
        $objective21->save();

        $objectiveOrga = new Objective(
            null,
            1000,
            $groupOrga->uuid,
            'Newsletter 220401'
        );
        $objectiveOrga->save();

        $objectiveCause2 = new Objective(
            null,
            10000,
            $groupCause2->uuid,
            'Newsletter 220401'
        );
        $objectiveCause2->save();

        $totalCause1Person = Objective::totalByCauseAndType($cause1->uuid, EnumGroupType::PERSON);
        $this->assertSame(110, $totalCause1Person);

        $totalCause1Orga = Objective::totalByCauseAndType($cause1->uuid, EnumGroupType::ORGANIZATION);
        $this->assertSame(1000, $totalCause1Orga);

        $totalCause2Person = Objective::totalByCauseAndType($cause2->uuid, EnumGroupType::PERSON);
        $this->assertSame(10000, $totalCause2Person);
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

    public function test_findByGroups(): void {
        $cause = new Cause(
            null,
            'test_'.wp_generate_password(),
        );
        $cause->save();

        $group1 = new Group(
            null,
            'test_'.wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group1->save();

        $group2 = new Group(
            null,
            'test_'.wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group2->save();

        $objectiveGroup1 = new Objective(
            null,
            100,
            $group1->uuid,
            'Newsletter 220401'
        );
        $objectiveGroup1->save();

        $objectiveDeleted = new Objective(
            null,
            100,
            $group1->uuid,
            'Newsletter 220401'
        );
        $objectiveDeleted->save();
        $objectiveDeleted->delete();

        $objectiveGroup2 = new Objective(
            null,
            100,
            $group2->uuid,
            'Newsletter 220401'
        );
        $objectiveGroup2->save();

        $objectives = Objective::findByGroups([$group1->uuid]);

        $this->assertCount(1, $objectives);
        $this->assertSame($objectiveGroup1->uuid, $objectives[0]->uuid);
    }

    public function test_findHighestOfGroup(): void {
        $cause = new Cause(
            null,
            'test_'.wp_generate_password(),
        );
        $cause->save();

        $group1 = new Group(
            null,
            'test_'.wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group1->save();

        $group2 = new Group(
            null,
            'test_'.wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group2->save();

        $objective1 = new Objective(
            null,
            100,
            $group1->uuid,
            'Newsletter 220401'
        );
        $objective1->save();

        $objective2 = new Objective(
            null,
            200,
            $group1->uuid,
            'Newsletter 220401'
        );
        $objective2->save();

        $objectiveDeleted = new Objective(
            null,
            500,
            $group1->uuid,
            'Newsletter 220401'
        );
        $objectiveDeleted->save();
        $objectiveDeleted->delete();

        $objectiveOther = new Objective(
            null,
            500,
            $group2->uuid,
            'Newsletter 220401'
        );
        $objectiveOther->save();

        $objectives = Objective::findHighestOfGroup($group1->uuid);

        $this->assertCount(1, $objectives);
        $this->assertSame($objective2->uuid, $objectives[0]->uuid);
    }
}
