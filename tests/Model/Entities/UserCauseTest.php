<?php

declare(strict_types=1);

namespace Model\Entities;

use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\User;
use Collectme\Model\Entities\UserCause;
use PHPUnit\Framework\TestCase;

class UserCauseTest extends TestCase
{
    public function test_getByUserAndCause(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::FR,
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

        $test = UserCause::getByUserAndCause($user->uuid, $cause->uuid);

        $this->assertSame($user->uuid, $test->userUuid);
        $this->assertSame($cause->uuid, $test->causeUuid);
    }
}
