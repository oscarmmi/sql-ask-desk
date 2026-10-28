<?php

namespace App\Services\SqlAsk;

class SqlGuard
{
    private const ALLOWED_TABLES = ['products', 'orders', 'order_items'];

    private const BLOCKED_WORDS = [
        'ALTER', 'ANALYZE', 'ATTACH', 'BEGIN', 'COMMIT', 'CREATE', 'DELETE', 'DETACH',
        'DROP', 'END', 'INSERT', 'LOAD_EXTENSION', 'PRAGMA', 'REINDEX', 'RELEASE',
        'REPLACE', 'ROLLBACK', 'SAVEPOINT', 'UPDATE', 'VACUUM', 'WRITEFILE', 'READFILE',
    ];

    public function assertAllowed(string $sql): void
    {
        if (strlen($sql) > 4000 || trim($sql) === '') {
            throw new SqlRejectedException('The SQL statement is empty or too long.');
        }

        $tokens = $this->tokenize($sql);

        if ($tokens === [] || strtoupper($tokens[0]) !== 'SELECT') {
            throw new SqlRejectedException('Only a single SELECT statement is allowed.');
        }

        if (in_array(';', $tokens, true)) {
            $semicolonIndex = array_search(';', $tokens, true);
            if ($semicolonIndex !== count($tokens) - 1) {
                throw new SqlRejectedException('Only one SQL statement is allowed.');
            }
            array_pop($tokens);
        }

        $words = array_map('strtoupper', array_filter($tokens, fn (string $token): bool => preg_match('/^[a-z_][a-z0-9_]*$/i', $token) === 1));

        foreach ($words as $word) {
            if (in_array($word, self::BLOCKED_WORDS, true) || str_starts_with($word, 'PRAGMA_')) {
                throw new SqlRejectedException('Mutating SQL and database administration commands are not allowed.');
            }
        }

        if (in_array('SELECT', array_slice($words, 1), true)) {
            throw new SqlRejectedException('Nested queries are not supported.');
        }

        $this->assertTablesAreAllowed($tokens);
    }

    /** @return list<string> */
    private function tokenize(string $sql): array
    {
        $tokens = [];
        $length = strlen($sql);

        for ($index = 0; $index < $length;) {
            $character = $sql[$index];

            if (ctype_space($character)) {
                $index++;

                continue;
            }

            if ($character === '-' && ($sql[$index + 1] ?? '') === '-' || $character === '/' && ($sql[$index + 1] ?? '') === '*') {
                throw new SqlRejectedException('SQL comments are not accepted.');
            }

            if ($character === "'" || $character === '"' || $character === '`' || $character === '[') {
                $closing = $character === '[' ? ']' : $character;
                $value = '';
                $index++;
                $closed = false;

                while ($index < $length) {
                    if ($sql[$index] === $closing) {
                        if (($sql[$index + 1] ?? '') === $closing && $closing !== ']') {
                            $value .= $closing;
                            $index += 2;

                            continue;
                        }
                        $index++;
                        $closed = true;
                        break;
                    }

                    $value .= $sql[$index++];
                }

                if (! $closed) {
                    throw new SqlRejectedException('The SQL contains an unterminated quoted value.');
                }

                $tokens[] = $character === "'" ? '<literal>' : $value;

                continue;
            }

            if (preg_match('/^[a-z_][a-z0-9_]*/i', substr($sql, $index), $match) === 1) {
                $tokens[] = $match[0];
                $index += strlen($match[0]);

                continue;
            }

            if (str_contains(';,().', $character)) {
                $tokens[] = $character;
            }
            $index++;
        }

        return $tokens;
    }

    /** @param list<string> $tokens */
    private function assertTablesAreAllowed(array $tokens): void
    {
        $count = count($tokens);
        $inFromClause = false;
        $sawTable = false;
        $expectTable = false;
        $clauseEnd = ['WHERE', 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'OFFSET', 'UNION', 'EXCEPT', 'INTERSECT'];

        foreach ($tokens as $index => $token) {
            $word = strtoupper($token);

            if (in_array($word, ['FROM', 'JOIN'], true)) {
                $inFromClause = true;
                $expectTable = true;

                continue;
            }

            if ($inFromClause && in_array($word, $clauseEnd, true)) {
                $inFromClause = false;
                $expectTable = false;

                continue;
            }

            if ($expectTable) {
                if (! in_array(strtolower($token), self::ALLOWED_TABLES, true)) {
                    throw new SqlRejectedException('Queries may read only products, orders, and order_items.');
                }
                $sawTable = true;
                $expectTable = false;

                continue;
            }

            if ($inFromClause && $token === ',') {
                $expectTable = true;
            }

            if ($index === $count - 1 && $expectTable) {
                throw new SqlRejectedException('A source table is required.');
            }
        }

        if (! $sawTable) {
            throw new SqlRejectedException('Queries must read from an approved seed table.');
        }
    }
}
