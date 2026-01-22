<?php

namespace MartenaSoft\ConsoleProfileBundle\Service;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;

class ProfileDbService
{
    public function renderProfileDb(Profile $profile,  OutputInterface $output): void
    {
        if (!$profile->hasCollector('db')) {
            return;
        }

        $db = $profile->getCollector('db');

        $table = new Table($output);
        $table->setHeaders(['Database:', ' --- ' ]);
        $table->addRow(['Query count: ', $db->getQueryCount()]);
        $table->addRow(['Time: ', $db->getTime()]);
        $table->render();

        $table = new Table($output);
        $table->setColumnMaxWidth(1, 200);
        $table->setHeaders(['Times', 'SQL']);
        foreach ($db->getQueries() as $queries) {
            foreach ($queries as $query) {
                if (!isset($query['sql'])) {
                    continue;
                }

                $table->addRow(new TableSeparator());
                $sql = $this->interpolateQuery(
                    $query['sql'],
                    $query['params']->getValue() ?? [],
                    $query['types'] ?? []
                );
                $time = sprintf('%.6f ms', $query['executionMS']);
                $table->addRow([$time, $sql]);
            }
        }
        $table->render();
    }

    private function interpolateQuery(string $sql, array $params, array $types = []): string
    {
        $index = 0;

        return preg_replace_callback('/\?/', function () use (&$index, $params, $types) {
            if (!array_key_exists($index, $params)) {
                return '?';
            }

            $value = $params[$index];
            $type  = $types[$index] ?? null;

            $index++;

            return $this->formatSqlValue($value, $type);
        }, $sql);
    }

    private function formatSqlValue(mixed $value, ?int $type): string
    {
        if ($value === null) {
            return 'NULL';
        }

        // DateTime
        if ($value instanceof \DateTimeInterface) {
            return "'" . $value->format('Y-m-d H:i:s') . "'";
        }

        // Boolean
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        // Numeric
        if (in_array($type, [
            Types::INTEGER,
            Types::BIGINT,
            Types::SMALLINT,
            Types::FLOAT,
            Types::DECIMAL,
        ], true)) {
            return (string) $value;
        }

        // Doctrine DBAL Array types
        if (is_array($value)) {
            return "ARRAY[" . implode(', ', array_map(
                    fn ($v) => $this->formatSqlValue($v, null),
                    $value
                )) . "]";
        }

        if (preg_match('/\d+/', $value)) {
            return (string) $value;
        }

        // Fallback: string
        return "'" . addslashes((string) $value) . "'";
    }
}