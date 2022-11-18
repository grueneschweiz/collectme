<?php

declare(strict_types=1);

namespace Email;

use Collectme\Email\UserPlaceholder;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class UserPlaceholderTest extends TestCase
{
    use UserPlaceholder;

    public function test_replaceUserPlaceholder(): void
    {
        $user = new User(
            wp_generate_uuid4(),
            'email@example.com',
            'Jane',
            'Doe',
            EnumLang::FR,
            true,
            'test_replaceUserPlaceholder'
        );

        $template = <<<EOL
hello {{firstName}} {{lastName}}
your email is {{userEmail}} and your uuid {{userUuid}}
{{invalidPlaceholder}}
EOL;

        $expected = <<<EOL
hello Jane Doe
your email is email@example.com and your uuid {$user->uuid}
{{invalidPlaceholder}}
EOL;

        $result = $this->replaceUserPlaceholder($template, $user);
        self::assertSame($expected, $result);
    }
}
