<?php

declare(strict_types=1);

namespace Collectme\Email;

trait LazyReplacer
{
    /**
     * @param string $message
     * @param Array<string, string|Closure> $replacements
     * @return string
     */
    private function lazyReplace(string $message, array $replacements): string
    {
        foreach($replacements as $key => $value) {
            if (!str_contains($message, $key)) {
                continue;
            }

            if ($value instanceof \Closure) {
                $value = $value();
            }

            $message = str_replace($key, $value, $message);
        }

        return $message;
    }
}