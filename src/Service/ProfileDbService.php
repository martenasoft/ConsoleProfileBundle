<?php

namespace MartenaSoft\ConsoleProfileBundle\Service;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;

class ProfileDbService
{

    public function __construct(private string $logDir)
    {
    }

    public function renderProfileDb(Profile $profile,  OutputInterface $output, bool $isImportToCsv = false): void
    {
        if (!$profile->hasCollector('db')) {
            return;
        }

        $db = $profile->getCollector('db');
        $queries = $db->getQueries();

        if (empty($queries)) {
            $output->writeln(sprintf(
                "\n<fg=green;options=bold>[Sql #%d]</> <fg=yellow>%s</>",
                0,
                "No sqls found"));
        }
        
        $table = new Table($output);
        $table->setColumnMaxWidth(0, 200);
        $table->setHeaders(['Sql', 'Times']);
        $index = 1;
        $totals = [];
        $queriesData = [];
        
        foreach ($queries as $queries) {
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
                
                if (preg_match('/^\s*([^\s,()]+)/u', $sql, $matches) && isset($matches[1])) {
                    if (!isset($totals[$matches[1]])) {
                        $totals[$matches[1]] = (float)$query['executionMS'];
                    } else {
                        $totals[$matches[1]] += (float)$query['executionMS'];
                    }
                }
                $queriesData[] = [
                    'sql' => $sql,
                    'time' => (float)$query['executionMS'],
                ];
                $table->addRow([$sql, $time]);
                $index++;
            }            
        }
        
        $table->render();

        if ($isImportToCsv) {
            $filePath = $this->logDir . '/' . $profile->getToken() . '_ms_profiler_queries.csv';
            $file = fopen($filePath, 'w');
            fputcsv($file, ['Sql', 'Times'], ';');

        }

        $table = new Table($output);
        $table->setHeaders(['Database:', ' --- ' ]);

        foreach ($totals as $com => $total) {
            $table->addRow([$com, sprintf('%.6f ms',  sprintf('%.6f ms', $total))]);
        }
        
        $table->addRow(['Total queries count: ', $db->getQueryCount()]);
        $table->addRow(['Total time: ', $db->getTime()]);
        $table->render();

        if ($isImportToCsv) {
            foreach ($queriesData as $item) {
                $row = [
                    $item['sql'],
                    $item['time'],
                ];
                fputcsv($file, $row, ';');
            }
            fclose($file);
        }
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

            $value = $this->formatSqlValue($value, $type);
            
            return $value;
        }, $sql);
    }

    private function formatSqlValue(mixed $value, ?int $type): string
    {
        $valueToLower = strtolower($value);
        if ($value === null) {
            return 'NULL';
        }

        // DateTime
        if ($value instanceof \DateTimeInterface) {
            return "'" . $value->format('Y-m-d H:i:s') . "'";
        }

        // Boolean
        if (is_bool($value) || $valueToLower=== 'false' || $valueToLower === 'false') {
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