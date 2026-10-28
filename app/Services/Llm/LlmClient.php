<?php

namespace App\Services\Llm;

interface LlmClient
{
    /** Return a SQL statement for the supplied question and schema description. */
    public function generateSql(string $question, string $schema): string;
}
