<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Misc\Settings;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\User;
use Collectme\Model\Entities\UserCause;
use PHPUnit\Framework\TestCase;

class CauseTest extends TestCase
{

    public function test_get(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password()
        );
        $cause = $cause->save();

        $dbCause = Cause::get($cause->uuid);

        $this->assertSame($cause->uuid, $dbCause->uuid);
        $this->assertSame($cause->name, $dbCause->name);
    }

    public function test_save(): void
    {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password()
        );
        $cause = $cause->save();

        $this->assertNotEmpty($cause->uuid);
        $this->assertNotEmpty($cause->created);
    }

    public function test_findByUser(): void
    {
        $user1 = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user1->save();

        $user2 = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user2->save();


        $cause1 = new Cause(
            null,
            'user_cause_'.wp_generate_password(),
        );
        $cause1->save();

        $cause2 = new Cause(
            null,
            'user_cause_'.wp_generate_password(),
        );
        $cause2->save();

        $cause3 = new Cause(
            null,
            'user_cause_'.wp_generate_password(),
        );
        $cause3->save();

        $userCause1 = new UserCause(
            null,
            $user1->uuid,
            $cause1->uuid
        );
        $userCause1->save();

        $userCause2 = new UserCause(
            null,
            $user1->uuid,
            $cause2->uuid
        );
        $userCause2->save();

        $userCause3 = new UserCause(
            null,
            $user2->uuid,
            $cause3->uuid
        );
        $userCause3->save();

        $causes = Cause::findByUser($user1->uuid);

        $this->assertEqualsCanonicalizing(
            [$cause1->uuid, $cause2->uuid],
            [$causes[0]->uuid, $causes[1]->uuid]
        );
        $this->assertCount(2, $causes);
    }

    private function createCause(?\DateTime $start, ?\DateTime $stop) {
        $cause = new Cause(
            null,
            'test_' . wp_generate_password()
        );
        $cause->save();
        Settings::getInstance()->setTimings([
            'start' => $start,
            'stop' => $stop,
        ], $cause->uuid);
        return $cause;
    }

    public function test_findActive(): void
    {
        $activeToday = $this->createCause(
            date_create()->setTime(0,0),
            date_create()->setTime(23,59, 59, 999999)
        );
        $activeForever = $this->createCause(
            null,
            null,
        );
        $passed = $this->createCause(
            null,
            date_create('-1 day'),
        );
        $upcoming = $this->createCause(
            date_create('+1 day'),
            null,
        );

        $causes = Cause::findActive();
        $causesUuids = array_map(static fn(Cause $cause) => $cause->uuid, $causes);

        $this->assertContains($activeToday->uuid, $causesUuids);
        $this->assertContains($activeForever->uuid, $causesUuids);
        $this->assertNotContains($passed->uuid, $causesUuids);
        $this->assertNotContains($upcoming->uuid, $causesUuids);
    }

    /**
     * @dataProvider getActiveCauses
     */
    public function test_isActive(Cause $cause, bool $active): void
    {
        $this->assertEquals($active, $cause->isActive());
    }

    public function getActiveCauses(): array
    {
        $activeToday = $this->createCause(
            date_create()->setTime(0,0),
            date_create()->setTime(23,59, 59, 999999)
        );
        $activeUntilTomorrow = $this->createCause(
            null,
            date_create('+1 day')
        );
        $activeSinceYesterday = $this->createCause(
            date_create('-1 day'),
            null,
        );
        $activeForever = $this->createCause(
            null,
            null,
        );
        $passed = $this->createCause(
            null,
            date_create('-1 day'),
        );
        $upcoming = $this->createCause(
            date_create('+1 day'),
            null,
        );

        return [
            'activeToday' => [$activeToday, true],
            'activeUntilTomorrow' => [$activeUntilTomorrow, true],
            'activeSinceYesterday' => [$activeSinceYesterday, true],
            'activeForever' => [$activeForever, true],
            'passed' => [$passed, false],
            'upcoming' => [$upcoming, false],
        ];
    }
}
