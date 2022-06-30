<?php
/** @noinspection SqlDerivedTableAlias */

/** @noinspection SqlRedundantOrderingDirection */

/** @noinspection SqlResolve */

declare(strict_types=1);

namespace Model;

use Collectme\Model\EnumPaginationCursorPointsTo;
use Collectme\Model\EnumPaginationOrder;
use Collectme\Model\Paginator;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    public function test_addToQuery__args()
    {
        $cursor = wp_generate_uuid4();
        $paginator = new Paginator(
            11,
            $cursor,
            EnumPaginationCursorPointsTo::LAST,
            EnumPaginationOrder::ASC
        );

        $query = 'SELECT * FROM tbl WHERE a = %d';
        $args = [8];

        $paginator->addToQuery($query, $args, 'insert_id', 'tbl');

        $this->assertSame(
            [8, $cursor],
            $args
        );
    }

    public function test_addToQuery__last__asc()
    {
        $cursor = wp_generate_uuid4();
        $paginator = new Paginator(
            11,
            $cursor,
            EnumPaginationCursorPointsTo::LAST,
            EnumPaginationOrder::ASC
        );

        $query = 'SELECT * FROM tbl WHERE a = %d';
        $args = [8];

        $this->assertSame(
            "SELECT * FROM (SELECT * FROM tbl WHERE a = %d AND insert_id > (SELECT insert_id FROM tbl WHERE uuid = '%s') ORDER BY insert_id ASC LIMIT 11) ORDER BY insert_id ASC",
            $paginator->addToQuery($query, $args, 'insert_id', 'tbl')
        );
        $this->assertSame(
            [8, $cursor],
            $args
        );
    }

    public function test_addToQuery__last__desc()
    {
        $cursor = wp_generate_uuid4();
        $paginator = new Paginator(
            11,
            $cursor,
            EnumPaginationCursorPointsTo::LAST,
            EnumPaginationOrder::DESC
        );

        $query = 'SELECT * FROM tbl';
        $args = [8];

        $this->assertSame(
            "SELECT * FROM (SELECT * FROM tbl WHERE insert_id < (SELECT insert_id FROM tbl WHERE uuid = '%s') ORDER BY insert_id DESC LIMIT 11) ORDER BY insert_id DESC",
            $paginator->addToQuery($query, $args, 'insert_id', 'tbl')
        );
    }

    public function test_addToQuery__first__asc()
    {
        $cursor = wp_generate_uuid4();
        $paginator = new Paginator(
            11,
            $cursor,
            EnumPaginationCursorPointsTo::FIRST,
            EnumPaginationOrder::ASC
        );

        $query = 'SELECT * FROM tbl';
        $args = [8];

        $this->assertSame(
            "SELECT * FROM (SELECT * FROM tbl WHERE insert_id < (SELECT insert_id FROM tbl WHERE uuid = '%s') ORDER BY insert_id DESC LIMIT 11) ORDER BY insert_id ASC",
            $paginator->addToQuery($query, $args, 'insert_id', 'tbl')
        );
    }

    public function test_addToQuery__first__desc()
    {
        $cursor = wp_generate_uuid4();
        $paginator = new Paginator(
            11,
            $cursor,
            EnumPaginationCursorPointsTo::FIRST,
            EnumPaginationOrder::DESC
        );

        $query = 'SELECT * FROM tbl';
        $args = [8];

        $this->assertSame(
            "SELECT * FROM (SELECT * FROM tbl WHERE insert_id > (SELECT insert_id FROM tbl WHERE uuid = '%s') ORDER BY insert_id ASC LIMIT 11) ORDER BY insert_id DESC",
            $paginator->addToQuery($query, $args, 'insert_id', 'tbl')
        );
    }

    public function test_addToQuery_noCursor()
    {
        $paginator = new Paginator(
            11,
            null,
            EnumPaginationCursorPointsTo::LAST,
            EnumPaginationOrder::ASC
        );

        $query = 'SELECT * FROM tbl WHERE a = %d';
        $args = [8];

        $this->assertSame(
            "SELECT * FROM (SELECT * FROM tbl WHERE a = %d ORDER BY insert_id ASC LIMIT 11) ORDER BY insert_id ASC",
            $paginator->addToQuery($query, $args, 'insert_id', 'tbl')
        );
        $this->assertSame(
            [8],
            $args
        );
    }
}
