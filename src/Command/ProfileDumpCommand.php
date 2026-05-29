<?php

namespace MartenaSoft\ConsoleProfileBundle\Command;

use MartenaSoft\ConsoleProfileBundle\Service\ProfileDbService;
use MartenaSoft\ConsoleProfileBundle\Service\ProfileDumpService;
use MartenaSoft\ConsoleProfileBundle\Service\ProfileEventsService;
use MartenaSoft\ConsoleProfileBundle\Service\ProfileRequestService;
use MartenaSoft\ConsoleProfileBundle\Service\ProfilerReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\DataCollector\DumpDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\VarDumper\Dumper\CliDumper;

final class ProfileDumpCommand extends Command
{
    public function __construct(
        private readonly ProfilerReader $reader,
        private readonly ProfileRequestService $profileRequestService,
        private readonly ProfileDbService $profileDbService,
        private readonly ProfileEventsService $profileEventsService,
        private readonly ProfileDumpService $profileDumpService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('ms:profiler:dump')
            ->addOption('token', null, InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $token = $input->getOption('token') ?? null;
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);


        $i = 0;
        $limit = 20;
        $cursorBefore = null;
        $isRequestDetail = false;
        $isSql = false;

        $tokens = [];
        $items = [];
        $selectedToken = null;

        while (true) {

            $menu = ($cursorBefore? '[h] home ' : '') . "[n] next [1..$limit] select token [q] quit";

            if (!$isRequestDetail && !$isSql) {
                $selectedToken = '';
                $items = $this->reader->find(limit: $limit, end: $cursorBefore);
                $tokens = $this->profileRequestService->renderItems($output, $items);
            } elseif ($isRequestDetail) {
                $menu = "[r] Request list [s] Sql [si] Sql + import sql to file CSV [e] Events [d] Dumps [q] quit";
            } elseif ($isSql) {
                $menu = "[r] Request list [s] Sql [si] Sql + import sql to file CSV [e] Events [d] Dumps [q] quit";
            }

            $io->writeln($menu);
            $key = trim(fgets(STDIN));

            if ($key === 'q') break;

            if ($key === 'n' && $items) {
                $cursorBefore = end($items)['time'];
            }

            if (in_array($key, ['h', 'r'])) {
                $selectedToken = '';
                $isRequestDetail = false;
                $cursorBefore = null;
                $isSql = false;
            }

            if (($key === 's' || $key === 'si') && !empty($selectedToken)) {
                $isSql = true;
                $profile = $this->reader->load($selectedToken);

                if (!$profile) {
                    $output->writeln('<error>Profile not found</error>');
                    return Command::FAILURE;
                }

                $this->profileDbService->renderProfileDb($profile, $output, ($key === 'si'));
            }
            
            if ($key === 'd') {
                $profile = $this->reader->load($selectedToken);
                $this->profileDumpService->renderDumps($profile, $output);
            }

            if ($key === 'e') {
                $profile = $this->reader->load($selectedToken);
                $this->profileEventsService->renderEvents($profile, $output);
            }

            if (isset($tokens[$key])) {
                $selectedToken = $tokens[$key];
                $profile = $this->reader->load($tokens[$key]);

                if (!$profile) {
                    $output->writeln('<error>Profile not found</error>');
                    return Command::FAILURE;
                }


                $this->profileRequestService->profileDetail($profile, $output);
                $isRequestDetail = true;
            }

            if ($i >= 10000) {
                break;
            }
            $i++;
        }
        return 0;
    }
}
