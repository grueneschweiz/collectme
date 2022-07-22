<?php
declare(strict_types=1);

return [
    // always mock php session class
    \Collectme\Model\PhpSession::class => static function() {
        return (new (new class extends \PHPUnit\Framework\TestCase {
            public function mock(string $class): \PHPUnit\Framework\MockObject\MockObject {
                return $this->createMock($class);
            }
        }))->mock(\Collectme\Model\PhpSession::class);
    },
    // always mock cookie class
    \Collectme\Misc\Cookie::class => static function() {
        return (new (new class extends \PHPUnit\Framework\TestCase {
            public function mock(string $class): \PHPUnit\Framework\MockObject\MockObject {
                return $this->createMock($class);
            }
        }))->mock(\Collectme\Misc\Cookie::class);
    },
];