<?php

declare(strict_types=1);

namespace Unit\Email;

use Collectme\Email\GroupPlaceholder;
use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\SignatureEntry;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class GroupPlaceholderTest extends TestCase
{
    use GroupPlaceholder;

    public function test_replaceGroupPlaceholder_all(): void
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

        $log1 = new ActivityLog(
            null,
            EnumActivityType::PLEDGE,
            10,
            $cause->uuid,
            $group->uuid,
        );
        $log1->save();
        $log2 = new ActivityLog(
            null,
            EnumActivityType::PLEDGE,
            20,
            $cause->uuid,
            $group->uuid,
        );
        $log2->save();

        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::DE,
            true,
            'test_replaceGroupPlaceholder_all'
        );
        $user->save();

        $entry1 = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            10,
            $log1->uuid,
        );
        $entry1->save();
        $entry2 = new SignatureEntry(
            null,
            $group->uuid,
            $user->uuid,
            20,
            $log2->uuid,
        );
        $entry2->save();

        $objective = new Objective(
            null,
            33,
            $group->uuid,
            'test_replaceGroupPlaceholder_all'
        );
        $objective->save();

        $template = <<<EOL
{{groupName}} collected {{groupSignatureCount}} out of {{groupSignatureObjective}} signatures.
EOL;

        $expected = <<<EOL
{$group->name} collected 30 out of 33 signatures.
EOL;

        $result = $this->replaceGroupPlaceholder($template, $group);
        self::assertSame($expected, $result);
    }
}
