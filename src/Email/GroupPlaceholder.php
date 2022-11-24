<?php

declare(strict_types=1);

namespace Collectme\Email;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\Objective;

trait GroupPlaceholder
{
    use LazyReplacer;

    /**
     * @throws CollectmeDBException
     * @noinspection PhpDocRedundantThrowsInspection
     */
    private function replaceGroupPlaceholder(string $msg, Group $group): string
    {
        $getObjective = static function() use ($group) {
            $objectives = Objective::findHighestOfGroup($group->uuid);
            return empty($objectives) ? '0' : (string) $objectives[0]->objective;
        };

        $replacements = [
            '{{groupName}}' => $group->name,
            '{{groupSignatureCount}}' => static fn() => (string) $group->signatures(),
            '{{groupSignatureObjective}}' => $getObjective,
        ];

        return $this->lazyReplace($msg, $replacements);
    }
}