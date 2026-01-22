<?php

namespace MartenaSoft\ConsoleProfileBundle\Command\Traits;

use Doctrine\DBAL\Types\Types;
use MartenaSoft\ConsoleProfileBundle\Service\ProfilerReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;

trait ProfileTrait
{
    private function printProfile(
        OutputInterface $output,
        ProfilerReader $reader,
        ?string $token = null,
        int $limit = 10,
        array $removeFields = ['virtual_type', 'parent']
    ): int {

        $table = new Table($output);
        $table->setHeaders(['Token', 'IP', 'Method', 'URL', 'Time (ms)', 'Status code', 'Memory (MB)']);

        if (!empty($token)) {
            $profile = $this->reader->load($token);

            if (!$profile) {
                $output->writeln('<error>Profile not found</error>');
                return Command::FAILURE;
            }

            $item['token'] = $token;
            $item['ip'] = $profile->getIp();
            $item['method'] = $profile->getMethod();
            $item['url'] = $profile->getUrl();
            $item['time'] = $profile->getTime();
            $item['status code'] = $profile->getStatusCode();
            $item = array_merge($item, $this->writeProfileItem($profile));
            $table->addRow($item);
            $table->render();


            return Command::SUCCESS;
        } else {
            $items = $reader->find(limit: $limit);
        }

        foreach ($items as $item) {
            $profile_ = $reader->load($item['token']);
            $item = array_merge($item, $this->writeProfileItem($profile_));

            if (!$profile_) {
                continue;
            }
            foreach ($removeFields as $k) {
                if (isset($item[$k])) {
                    unset($item[$k]);
                }
            }
            $table->addRow($item);
        }
        $table->render();
        return Command::SUCCESS;
    }

    private function writeProfileItem(Profile $profile): array
    {
        $timeDuration = '';
        $memoryMb = '';
        if ($profile->hasCollector('time')) {
            $timeDuration = sprintf('%.3f ms', $profile->getCollector('time')->getDuration() / 1000);
        }

        if ($profile->hasCollector('memory')) {
            $memoryMb = $profile->getCollector('memory')->getMemory() / 1024 / 1024;
        }

        $item['time'] = $timeDuration;
        $item['memory'] = $memoryMb;
        return $item;
    }

    private function writeProfileDb(string $token,  OutputInterface $output): void
    {
        $profile = $this->reader->load($token);
        if (!$profile && !$profile->hasCollector('db')) {
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