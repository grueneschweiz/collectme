<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\SignatureEntry;
use Collectme\Model\Entities\Stat;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class StatTest extends TestCase
{
    public function test_updateCache(): void
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
            true,
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

        $entry1 = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            1,
            $log->uuid
        );
        $entry1->save();

        $entry2 = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            10,
            $log->uuid
        );
        $entry2->save();

        $objective1 = new Objective(
            null,
            1,
            $group->uuid,
            'Newsletter 220401'
        );
        $objective1->save();

        $objective2 = new Objective(
            null,
            10,
            $group->uuid,
            'Newsletter 220401'
        );
        $objective2->save();

        $stat = Stat::updateCache($cause->uuid);

        $this->assertSame(11/100000, $stat->registered);
        $this->assertSame(10/100000, $stat->pledged);
        $this->assertInstanceOf(\DateTime::class, $stat->updated);
    }

    public function test_updateCache__zero(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $stat = Stat::updateCache($cause->uuid);

        $this->assertSame(0.0, $stat->registered);
        $this->assertSame(0.0, $stat->pledged);
        $this->assertInstanceOf(\DateTime::class, $stat->updated);
    }

    public function test_getByCause__uncached(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        add_filter("transient_collectme_overview-{$cause->uuid}", function ($value) {
            $this->assertFalse($value);
            return $value;
        });

        Stat::getByCause($cause->uuid);
    }

    public function test_getByCause__cached(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        Stat::getByCause($cause->uuid);

        add_filter("transient_collectme_overview-{$cause->uuid}", function ($value) {
            $this->assertNotNull($value);
            return $value;
        });

        Stat::getByCause($cause->uuid);
    }

    public function test_cacheCleared__signatureEntry__save(): void
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
            true,
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

        add_action("delete_transient_collectme_overview-{$cause->uuid}", function ($name) use ($cause) {
            $this->assertSame("collectme_overview-{$cause->uuid}", $name);
        });

        $entry1 = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            1,
            $log->uuid
        );
        $entry1->save();
    }

    public function test_cacheCleared__signatureEntry__delete(): void
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
            true,
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

        $entry1 = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            1,
            $log->uuid
        );
        $entry1->save();

        add_action("delete_transient_collectme_overview-{$cause->uuid}", function ($name) use ($cause) {
            $this->assertSame("collectme_overview-{$cause->uuid}", $name);
        });

        $entry1->delete();
    }

    public function test_cacheCleared__objective__save(): void
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

        add_action("delete_transient_collectme_overview-{$cause->uuid}", function ($name) use ($cause) {
            $this->assertSame("collectme_overview-{$cause->uuid}", $name);
        });

        $objective = new Objective(
            null,
            1,
            $group->uuid,
            'Newsletter 220401'
        );
        $objective->save();
    }

    public function test_cacheCleared__objective__delete(): void
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

        $objective = new Objective(
            null,
            1,
            $group->uuid,
            'Newsletter 220401'
        );
        $objective->save();

        add_action("delete_transient_collectme_overview-{$cause->uuid}", function ($name) use ($cause) {
            $this->assertSame("collectme_overview-{$cause->uuid}", $name);
        });

        $objective->delete();
    }
}
