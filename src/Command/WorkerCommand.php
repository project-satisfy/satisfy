<?php

namespace Playbloom\Command;

use Playbloom\Model\BuildRequest;
use Playbloom\Model\BuildResult;
use Playbloom\Queue\BuildQueue;
use Playbloom\Runner\SatisBuildRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'satisfy:worker', description: 'Run the builds that webhooks and the admin UI have queued')]
class WorkerCommand extends Command implements SignalableCommandInterface
{
    private bool $stopping = false;

    public function __construct(
        private readonly BuildQueue $queue,
        private readonly SatisBuildRunner $runner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('once', null, InputOption::VALUE_NONE, 'Run whatever is queued, then exit')
            ->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Seconds between looks at an empty queue', '5');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $once = (bool) $input->getOption('once');
        $interval = max(1, (int) $input->getOption('interval'));

        $this->queue->recover();
        while (!$this->stopping) {
            $request = $this->queue->claim();
            if (null === $request) {
                if ($once) {
                    break;
                }
                sleep($interval);
                continue;
            }

            $onOutput = $output->isVerbose() ? static function (string $buffer) use ($output): void {
                $output->write($buffer);
            } : null;

            try {
                $result = $this->runner->build($request, $onOutput);
                $output->writeln($this->summarize($request, $result));
            } catch (\Throwable $exception) {
                $output->writeln($this->summarize($request, null, $exception->getMessage()));
            } finally {
                $this->queue->complete($request);
            }
        }

        return Command::SUCCESS;
    }

    public function getSubscribedSignals(): array
    {
        return \defined('SIGTERM') ? [\SIGTERM, \SIGINT] : [];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        // Let the build in progress finish, then leave the loop.
        $this->stopping = true;

        return false;
    }

    private function summarize(BuildRequest $request, ?BuildResult $result, ?string $error = null): string
    {
        return json_encode([
            'time' => date(DATE_ATOM),
            'scope' => $request->isFull() ? 'full' : 'repositories',
            'repositories' => $request->getRepositoryUrls(),
            'exit_code' => $result?->getExitCode(),
            'cancelled' => $result?->getCancelReason(),
            'error' => $error,
            'seconds' => null === $result ? null : round($result->getSeconds(), 1),
        ], JSON_UNESCAPED_SLASHES);
    }
}
