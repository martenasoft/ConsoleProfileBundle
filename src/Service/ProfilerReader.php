<?php
// Profiler/ProfilerReader.php
namespace MartenaSoft\ConsoleProfileBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\HttpKernel\Profiler\Profile;

final class ProfilerReader
{
    public function __construct(
        #[Autowire(service: 'profiler')]
        private readonly Profiler $profiler,
    ) {}

    /**
     * @return Profile[]
     */
    public function find(
        ?string $ip = '',
        ?string $url = '',
        ?int $limit = 10,
        ?string $method = '',
        ?string $start = '',
        ?string $end = '',
        ?string $statusCode = null
    ): array {
        return $this->profiler->find($ip, $url, $limit, $method, $start, $end, $statusCode);
    }

    public function load(string $token): ?Profile
    {
        return $this->profiler->loadProfile($token);
    }
}
