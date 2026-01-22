<?php

namespace MartenaSoft\ConsoleProfileBundle\Service;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;

class ProfileRequestService
{
    public function __construct(
        private readonly ProfilerReader $reader,
    ) {
    }

    public function renderItems(
        OutputInterface $output,
        array $items,
        array $removeFields = ['virtual_type', 'parent']
    ): array {

        $table = new Table($output);
        $table->setHeaders(['#', 'Token', 'IP', 'Method', 'URL', 'Time (ms)', 'Status code', 'Memory (MB)', 'Date']);

        $i = 1;
        $tokens = [];
        foreach ($items as $item) {
            $profile_ = $this->reader->load($item['token']);
            array_unshift($item, $i);
            $item = array_merge($item, $this->writeProfileItem($profile_));

            if (!$profile_) {
                continue;
            }

            $tokens[$i] = $item['token'];

            foreach ($removeFields as $k) {
                if (isset($item[$k])) {
                    unset($item[$k]);
                }
            }
            $table->addRow($item);
            $i++;
        }
        $table->render();
        return $tokens;
    }

    public function profileDetail(Profile $profile, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setHeaders(['Token', $profile->getToken()]);
        $table->addRow(['IP', $profile->getIp()]);
        $table->addRow(['Method', $profile->getMethod()]);
        $table->addRow(['Url', $profile->getUrl()]);
        $table->addRow(['Status Code', $profile->getStatusCode()]);
        $table->render();
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