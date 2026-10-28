<?php

namespace Tests\Feature;

use App\Models\AskQuery;
use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqlAskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => null]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_top_products_question_returns_rows_and_persists_the_generated_select(): void
    {
        $this->assertInstanceOf(FakeLlmClient::class, app(LlmClient::class));

        $response = $this->postJson(route('ask'), ['question' => 'Show me the top products']);

        $response->assertOk()
            ->assertJsonPath('query.status', 'ok')
            ->assertJsonPath('query.row_count', 8)
            ->assertJsonPath('query.rows.0.name', 'Summit 4K Monitor');

        $query = AskQuery::query()->sole();
        $this->assertStringStartsWith('SELECT', $query->sql_text);
        $this->assertSame('ok', $query->status);
        $this->assertCount(8, $query->preview_json);
    }

    public function test_mutating_sql_is_rejected_without_executing_it(): void
    {
        $this->app->instance(LlmClient::class, new class implements LlmClient
        {
            public function generateSql(string $question, string $schema): string
            {
                return 'DROP TABLE products';
            }
        });

        $productCount = DB::table('products')->count();
        $response = $this->postJson(route('ask'), ['question' => 'Drop the products table']);

        $response->assertUnprocessable()
            ->assertJsonPath('query.status', 'rejected')
            ->assertJsonPath('query.sql_text', 'DROP TABLE products');

        $this->assertSame($productCount, DB::table('products')->count());
        $this->assertSame('rejected', AskQuery::query()->sole()->status);
    }

    public function test_generated_select_cannot_read_tables_outside_the_seed_schema(): void
    {
        $this->app->instance(LlmClient::class, new class implements LlmClient
        {
            public function generateSql(string $question, string $schema): string
            {
                return 'SELECT name FROM products, sqlite_master';
            }
        });

        $this->postJson(route('ask'), ['question' => 'Show internal database tables'])
            ->assertUnprocessable()
            ->assertJsonPath('query.status', 'rejected');
    }

    public function test_llm_errors_are_saved_as_failed_instead_of_returning_a_server_error(): void
    {
        $this->app->instance(LlmClient::class, new class implements LlmClient
        {
            public function generateSql(string $question, string $schema): string
            {
                throw new \RuntimeException('Simulated provider timeout');
            }
        });

        $this->postJson(route('ask'), ['question' => 'Show products'])
            ->assertStatus(503)
            ->assertJsonPath('query.status', 'failed');

        $this->assertSame('failed', AskQuery::query()->sole()->status);
    }
}
