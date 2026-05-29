<?php

namespace MartenaSoft\ConsoleProfileBundle\Service;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\DataCollector\DumpDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class ProfileDumpService
{
    public function renderDumps(Profile $profile, OutputInterface $output): void
    {
        /** @var DumpDataCollector $dumpData */
        $dumpData = $profile->getCollector('dump');

        $reflection = new \ReflectionClass($dumpData);
        $property = $reflection->getProperty('data');
        $property->setAccessible(true);
        $rawDumps = $property->getValue($dumpData);

        if (empty($rawDumps)) {
            $output->writeln(sprintf(
                "\n<fg=green;options=bold>[Dump #%d]</> <fg=yellow>%s</>",
                0,
                "No dumps found"));
            
            return;
        }

        $dumper = new CliDumper();

        $stream = fopen('php://memory', 'r+');
        $dumper = new CliDumper($stream);
        $dumper->setColors($output->isDecorated());
        
        foreach ($rawDumps as $index => $dump) {
            ftruncate($stream, 0);
            rewind($stream);

            $output->writeln(sprintf(
                "\n<fg=green;options=bold>[Dump #%d]</> <fg=yellow>%s</>:<fg=cyan>%d</>",
                $index + 1,
                $dump['file'],
                $dump['line']
            ));
            $output->writeln('<fg=gray>' . str_repeat('─', 60) . '</>');
            $dumper->dump($dump['data']);
            rewind($stream);
            $dumpBody = stream_get_contents($stream);
            $output->write($dumpBody, false, OutputInterface::OUTPUT_RAW);
            $output->writeln('');
        }

        fclose($stream);
    }
}