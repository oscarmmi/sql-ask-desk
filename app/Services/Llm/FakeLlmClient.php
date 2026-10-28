<?php

namespace App\Services\Llm;

class FakeLlmClient implements LlmClient
{
    public function generateSql(string $question, string $schema): string
    {
        $question = mb_strtolower($question);

        if (str_contains($question, 'top product') || str_contains($question, 'best-selling') || str_contains($question, 'best selling')) {
            return 'SELECT name, category, unit_price, stock_quantity FROM products ORDER BY unit_price DESC LIMIT 10';
        }

        if (str_contains($question, 'order')) {
            return 'SELECT order_number, ordered_at, customer_name, status FROM orders ORDER BY ordered_at DESC LIMIT 10';
        }

        if (str_contains($question, 'low stock') || str_contains($question, 'running low')) {
            return 'SELECT name, stock_quantity FROM products WHERE stock_quantity < 20 ORDER BY stock_quantity ASC LIMIT 10';
        }

        if (str_contains($question, 'stock') || str_contains($question, 'inventory')) {
            return 'SELECT name, stock_quantity FROM products ORDER BY stock_quantity ASC LIMIT 10';
        }

        foreach (['audio', 'accessories', 'displays', 'workspace', 'video'] as $category) {
            if (str_contains($question, $category)) {
                $label = ucfirst($category);

                return "SELECT sku, name, category, unit_price, stock_quantity FROM products WHERE category = '{$label}' ORDER BY name LIMIT 10";
            }
        }

        return 'SELECT sku, name, category, unit_price, stock_quantity FROM products ORDER BY name LIMIT 10';
    }
}
