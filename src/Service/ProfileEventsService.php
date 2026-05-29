<?php

namespace MartenaSoft\ConsoleProfileBundle\Service;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\DataCollector\EventDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\VarDumper\Cloner\Data;

class ProfileEventsService
{
    public function renderEvents(Profile $profile, OutputInterface $output): void
    {
        if (!$profile->hasCollector('events')) {
            return;
        }

        /** @var EventDataCollector $items */
        $items = $profile->getCollector('events');

        $data = $items->getData();
        if ($data instanceof Data) {
            $data = $data->getValue(true);
        }

        if (!is_array($data) || empty($data)) {
            $output->writeln(sprintf(
                "\n<fg=green;options=bold>[Events #%d]</> <fg=yellow>%s</>",
                0,
                "No events found"));
            return;
        }
        
        foreach ($data as $dispatcherName => $dispatcherData) {

            foreach ($dispatcherData as $type => $listenerItem) {
                $output->writeln('<comment>' . $type . '</comment>');
                $table = new Table($output);
                $table->setHeaders(['Event', 'Priority', 'Stub']);
                foreach ($listenerItem as $eventData) {
                    $table->addRow([
                        $eventData['event'],
                        $eventData['priority'],
                        $eventData['stub'],

                    ]);
                }
                $table->render();
            }
        }
    }
}
