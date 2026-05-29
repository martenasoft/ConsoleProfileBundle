<?php

namespace MartenaSoft\ConsoleProfileBundle\Command;

use MartenaSoft\ConsoleProfileBundle\Service\ProfileDbService;
use MartenaSoft\ConsoleProfileBundle\Service\ProfileDumpService;
use MartenaSoft\ConsoleProfileBundle\Service\ProfileEventsService;
use MartenaSoft\ConsoleProfileBundle\Service\ProfileRequestService;
use MartenaSoft\ConsoleProfileBundle\Service\ProfilerReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\HttpKernel\Profiler\Profile;

#[AsCommand(name: 'ms:profiler', description: 'Console profiler')]
final class MsProfilerCommand extends Command
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cursorBefore = null;
        $isRequestDetail = false;
        $limit = 20;
        $removeFields = ['virtual_type', 'parent'];

        $items = $this->reader->find(limit: $limit, end: $cursorBefore);

        $tokens = [];
        $i = 1;
        foreach ($items as $item) {
            $profile_ = $this->reader->load($item['token']);
            array_unshift($item, $i);
            $item = array_merge($item, $this->writeProfileItem($profile_));

            if (!$profile_) {
                continue;
            }
            $tokens[$item['token']] = $item['url'] . '( date: '.$item['date'].' )';
            

            foreach ($removeFields as $k) {
                if (isset($item[$k])) {
                    unset($item[$k]);
                }
            }
            $i++;
        }
        
        $tokens['q'] = 'Quit';
        $selectedToken = null;

        while (true) {
            
            if (!$selectedToken) {
                $tokenChoice = new ChoiceQuestion(
                    'Select token: ',
                    $tokens
                );

                $selectedToken = $this->getHelper('question')->ask($input, $output, $tokenChoice);
            }

            if ($selectedToken === 'q') {
                break;
            }
            
            $selectedProfile = $this->reader->load($selectedToken);
            
            if (!$selectedProfile) {
                $output->writeln('<error>Profile not found</error>');
                return Command::FAILURE;
            }

            $actionsChoice = new ChoiceQuestion(
                'Select profed section: ',
                [
                    's' => 'Sql',
                    'si' => 'Sql + import to CSV file',
                    'e' => 'Events',
                    'd' => 'Dumps',
                    't' => 'Back to tokens',
                    'q' => 'Quit',
                ]
            );
            $output->writeln("\n<fg=green;options=bold>[Token# $selectedToken] -------------------------------------</>");
            $selectedAction = $this->getHelper('question')->ask($input, $output, $actionsChoice);

            switch ($selectedAction) {
                case 't':
                    $selectedToken = null;
                    break;
                case 'q': 
                    break(2);
                case 's':
                    $this->profileDbService->renderProfileDb($selectedProfile, $output, false);
                    break;
                case 'si':
                    $this->profileDbService->renderProfileDb($selectedProfile, $output, true);
                    break;    
                case 'e':
                    $this->profileEventsService->renderEvents($selectedProfile, $output);
                    break;
                case 'd':
                    $this->profileDumpService->renderDumps($selectedProfile, $output);   
                    break;    
            }
        }

        return Command::SUCCESS;
    }

    public function writeProfileItem(Profile $profile): array
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
        $item['date'] = (new \DateTime($item['time']))->format('Y-m-d H:i:s');
        return $item;
    }
}
