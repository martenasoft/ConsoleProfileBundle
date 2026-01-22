<?php

namespace MartenaSoft\ConsoleProfileBundle\Service;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\DataCollector\EventDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;

class ProfileEventsService
{
    public function renderEvents(Profile $profile, OutputInterface $output): void
    {
        if (!$profile->hasCollector('events')) {
            return;
        }

        /** @var EventDataCollector $items */
        $items = $profile->getCollector('events');
dd($items->getNotCalledListeners());
        foreach ($items as $item) {
            dd($item);
        }
        dd(33);
    }
}