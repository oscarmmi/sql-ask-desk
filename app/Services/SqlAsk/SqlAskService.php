<?php

namespace App\Services\SqlAsk;

use App\Models\AskQuery;
use App\Services\Llm\LlmClient;
use Illuminate\Support\Facades\DB;
use Throwable;

class SqlAskService
{
    private const SCHEMA = <<<'SCHEMA'
products(id, sku, name, category, unit_price, stock_quantity)
orders(id, order_number, ordered_at, customer_name, status)
order_items(id, order_id, product_id, quantity, unit_price)
SCHEMA;

    public function __construct(private readonly LlmClient $llm, private readonly SqlGuard $guard) {}

    public function ask(string $question): AskQuery
    {
        $query = AskQuery::create([
            'question' => $question,
            'status' => 'failed',
        ]);

        try {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                throw new \RuntimeException('The demo is configured to use SQLite.');
            }

            $sql = trim($this->llm->generateSql($question, self::SCHEMA));
            $query->sql_text = $sql;
            $query->save();

            $this->guard->assertAllowed($sql);

            $rows = DB::select($this->limitRows($sql));
            $preview = array_map(static fn (object $row): array => (array) $row, array_slice($rows, 0, 100));

            $query->forceFill([
                'row_count' => count($preview),
                'status' => 'ok',
                'preview_json' => $preview,
            ])->save();

            return $query->refresh();
        } catch (SqlRejectedException $exception) {
            $query->forceFill(['status' => 'rejected', 'row_count' => 0, 'preview_json' => null])->save();
            throw new SqlAskRejected($query->refresh());
        } catch (Throwable $exception) {
            report($exception);
            $query->forceFill(['status' => 'failed', 'row_count' => 0, 'preview_json' => null])->save();
            throw new SqlAskFailed($query->refresh());
        }
    }

    private function limitRows(string $sql): string
    {
        if (preg_match('/\bLIMIT\s+(\d+)\s*;?\s*$/i', $sql, $matches) === 1) {
            $limit = min((int) $matches[1], 100);

            return preg_replace('/\bLIMIT\s+\d+/i', 'LIMIT '.$limit, $sql) ?? $sql;
        }

        return rtrim($sql, "; \t\n\r").' LIMIT 100';
    }
}
