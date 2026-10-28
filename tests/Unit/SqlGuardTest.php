<?php

namespace Tests\Unit;

use App\Services\SqlAsk\SqlGuard;
use App\Services\SqlAsk\SqlRejectedException;
use PHPUnit\Framework\TestCase;

class SqlGuardTest extends TestCase
{
    public function test_it_allows_a_select_from_the_sample_schema(): void
    {
        $this->assertNull((new SqlGuard)->assertAllowed('SELECT p.name, SUM(i.quantity) AS units FROM products p JOIN order_items i ON i.product_id = p.id GROUP BY p.id'));
    }

    public function test_it_rejects_multiple_statements_and_database_admin_commands(): void
    {
        foreach ([
            'SELECT name FROM products; DROP TABLE products',
            'SELECT * FROM products; PRAGMA table_info(products)',
            "SELECT pragma_table_info('products') FROM products",
            'SELECT name FROM products -- comment',
            'SELECT name FROM users',
            'SELECT name FROM products, sqlite_master',
        ] as $sql) {
            try {
                (new SqlGuard)->assertAllowed($sql);
                $this->fail("Expected SQL to be rejected: {$sql}");
            } catch (SqlRejectedException) {
                $this->assertTrue(true);
            }
        }
    }
}
