<?php /** @noinspection SqlResolve */

declare(strict_types=1);

namespace Model;

use Collectme\Model\Filter;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{

    public function test_addToQuery(): void
    {
        $filter = new Filter(
            'field',
            'value',
            '='
        );

        $query = 'SELECT * FROM tbl';
        $args = [];

        $this->assertSame(
            "SELECT * FROM tbl WHERE field = '%s'",
            $filter->addToQuery($query, $args)
        );
        $this->assertSame(
            ['value'],
            $args
        );
    }

    public function test_addToQuery_where(): void
    {
        $filter = new Filter(
            'field',
            2,
            '<'
        );

        $query = 'SELECT * FROM tbl WHERE a = 1';
        $args = [];

        $this->assertSame(
            "SELECT * FROM tbl WHERE a = 1 AND field < %d",
            $filter->addToQuery($query, $args)
        );
        $this->assertSame(
            [2],
            $args
        );
    }
}
