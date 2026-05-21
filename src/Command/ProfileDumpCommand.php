<?php

namespace MartenaSoft\ConsoleProfileBundle\Command;

use MartenaSoft\ConsoleProfileBundle\Service\ProfileDbService;
use MartenaSoft\ConsoleProfileBundle\Service\ProfileEventsService;
use MartenaSoft\ConsoleProfileBundle\Service\ProfileRequestService;
use MartenaSoft\ConsoleProfileBundle\Service\ProfilerReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Profiler\Profile;

final class ProfileDumpCommand extends Command
{
    public function __construct(
        private readonly ProfilerReader        $reader,
        private readonly ProfileRequestService $profileRequestService,
        private readonly ProfileDbService      $profileDbService,
        private readonly ProfileEventsService $profileEventsService,
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

            $menu = ($cursorBefore ? '[h] home ' : '') . "[n] next [1..$limit] select token [q] quit";

            if (!$isRequestDetail && !$isSql) {
                $selectedToken = '';
                $items = $this->reader->find(limit: $limit, end: $cursorBefore);
                $tokens = $this->profileRequestService->renderItems($output, $items);
            } elseif ($isRequestDetail) {
                $menu = "[r] Request list [s] Sql [e] Events [q] quit";
            } elseif ($isSql) {
                $menu = "[r] Request list [s] Sql [so] Sql Options... [q] quit";
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

            if ($key === 's' && !empty($selectedToken)) {
                $isSql = true;
                $profile = $this->reader->load($selectedToken);

                if (!$profile) {
                    $output->writeln('<error>Profile not found</error>');
                    return Command::FAILURE;
                }

                $this->profileDbService->renderProfileDb($profile, $output);
            }

            if (isset($tokens[$key])) {
                $selectedToken = $tokens[$key];
                $profile = $this->reader->load($tokens[$key]);

//                $this->profileEventsService->renderEvents($profile, $output);
////                foreach ($profile->getCollectors() as $name => $collector) {
////                    $output->writeln($name . ' => ' . get_class($collector));
////                }
//                dd(12);

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


//        $menu = $this->profileRequestService->itemsMenu($input, $output, $helper);
//
//        switch ($menu) {
//            case ProfileRequestService::ACTION_NEXT:
//                $io = new SymfonyStyle($input, $output);
//                $io->clear();
//                $cursorNext = end($items)['time'];
//                $items = $this->profileRequestService->printItems($output, limit: $limit, end: $cursorNext);
//                $menu = $this->profileRequestService->itemsMenu($input, $output, $helper);
//                break;
//            case ProfileRequestService::ACTION_PREV;
//        }


        // $this->mainMenu($input, $output);

//        $this->printProfile($output, $this->reader, $token);
//        $profile = $this->reader->load($token);
//        foreach ($profile->getCollectors() as $name => $collector) {
//            $output->writeln($name.' => '.get_class($collector));
//        }
//
//        if (!empty($token)) {
//           // $this->writeProfileDb($token, $output);
//        }
        return 0;
    }
}
