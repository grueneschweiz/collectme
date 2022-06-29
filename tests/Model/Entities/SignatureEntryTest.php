<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\SignatureEntry;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class SignatureEntryTest extends TestCase
{
    public function test_get(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $user = new User(
            null,
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            'user cause test'
        );
        $user->save();

        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            123,
            $cause->uuid,
            $group->uuid
        );
        $log->save();

        $entry = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            123,
            $log->uuid
        );
        $entry->save();

        $entry = SignatureEntry::get($entry->uuid);

        $this->assertSame($group->uuid, $entry->groupUuid);
        $this->assertSame($user->uuid, $entry->userUuid);
        $this->assertSame(123, $entry->count);
        $this->assertSame($log->uuid, $entry->activityLogUuid);
    }

    public function test_save(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

        $user = new User(
            null,
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            'user cause test'
        );
        $user->save();

        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            123,
            $cause->uuid,
            $group->uuid
        );
        $log->save();

        $entry = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            123,
            $log->uuid
        );
        $entry->save();

        $this->assertNotEmpty($entry->uuid);
    }

    public function test_toApiModel(): void
    {
        $entry = new SignatureEntry(
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            wp_generate_uuid4(),
            44,
            wp_generate_uuid4(),
            date_create('2022-06-28T17:10:15+00:00'),
            date_create('2022-06-28T17:10:16+00:00'),
            date_create('2022-06-28T17:10:17+00:00'),
        );

        $data = $entry->toApiModel()->toArray();

        $this->assertEqualsCanonicalizing(
            [
                'data' => [
                    'id' => $entry->userUuid,
                    'type' => 'user'
                ]
            ],
            $data['relationships']['user']
        );
        $this->assertEqualsCanonicalizing(
            [
                'data' => [
                    'id' => $entry->groupUuid,
                    'type' => 'group'
                ]
            ],
            $data['relationships']['group']
        );
        $this->assertEqualsCanonicalizing(
            [
                'data' => [
                    'id' => $entry->activityLogUuid,
                    'type' => 'activity'
                ]
            ],
            $data['relationships']['activity']
        );

        unset($data['relationships']);

        $this->assertEqualsCanonicalizing(
            [
                'id' => $entry->uuid,
                'type' => 'signature',
                'attributes' => [
                    'count' => 44,
                    'created' => date_create('2022-06-28T17:10:15+00:00')->format(DATE_RFC3339_EXTENDED),
                    'updated' => date_create('2022-06-28T17:10:16+00:00')->format(DATE_RFC3339_EXTENDED),
                ],
            ],
            $data
        );
    }

    public function test_fromApiModelToPropsArray(): void
    {
        $apiData = [
            'id' => wp_generate_uuid4(),
            'type' => 'signature',
            'attributes' => [
                'count' => 13,
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'id' => wp_generate_uuid4(),
                        'type' => 'user'
                    ]
                ],
                'group' => [
                    'data' => [
                        'id' => wp_generate_uuid4(),
                        'type' => 'group'
                    ]
                ],
                'activity' => [
                    'data' => [
                        'id' => wp_generate_uuid4(),
                        'type' => 'activity'
                    ]
                ],
            ]
        ];

        $props = SignatureEntry::fromApiModelToPropsArray($apiData);

        $entry = new SignatureEntry(...$props);

        $this->assertSame($apiData['id'], $entry->uuid);
        $this->assertSame($apiData['attributes']['count'], $entry->count);
        $this->assertSame($apiData['relationships']['user']['data']['id'], $entry->userUuid);
        $this->assertSame($apiData['relationships']['group']['data']['id'], $entry->groupUuid);
        $this->assertSame($apiData['relationships']['activity']['data']['id'], $entry->activityLogUuid);
    }
}
