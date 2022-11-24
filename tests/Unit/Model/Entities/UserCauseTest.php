<?php

declare(strict_types=1);

namespace Unit\Model\Entities;

use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\User;
use Collectme\Model\Entities\UserCause;
use PHPUnit\Framework\TestCase;

class UserCauseTest extends TestCase
{
    public function test_findByUserAndCause(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'user cause test'
        );
        $user->save();

        $cause = new Cause(
            null,
            'user_cause_'.wp_generate_password(),
        );
        $cause->save();

        $userCause = new UserCause(
            null,
            $user->uuid,
            $cause->uuid
        );
        $userCause->save();

        $tests = UserCause::findByUserAndCause($user->uuid, $cause->uuid);

        $this->assertSame($user->uuid, $tests[0]->userUuid);
        $this->assertSame($cause->uuid, $tests[0]->causeUuid);
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
            $cause2->uuid
        );
        $userCause3->save();


        $test = UserCause::findByUser($user1->uuid);

        $this->assertIsArray($test);
        $this->assertCount(2, $test);
        $this->assertInstanceOf(UserCause::class, $test[0]);
        $this->assertEqualsCanonicalizing([$cause1->uuid, $cause2->uuid], [$test[0]->causeUuid, $test[1]->causeUuid]);
        $this->assertEqualsCanonicalizing([$user1->uuid, $user1->uuid], [$test[0]->userUuid, $test[1]->userUuid]);
    }

    public function test_findByCause(): void
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
            $user2->uuid,
            $cause1->uuid
        );
        $userCause2->save();

        $userCause3 = new UserCause(
            null,
            $user2->uuid,
            $cause2->uuid
        );
        $userCause3->save();


        $test = UserCause::findByCause($cause1->uuid);

        $this->assertIsArray($test);
        $this->assertCount(2, $test);
        $this->assertInstanceOf(UserCause::class, $test[0]);
        $this->assertEqualsCanonicalizing([$cause1->uuid, $cause1->uuid], [$test[0]->causeUuid, $test[1]->causeUuid]);
        $this->assertEqualsCanonicalizing([$user1->uuid, $user2->uuid], [$test[0]->userUuid, $test[1]->userUuid]);
    }
}
