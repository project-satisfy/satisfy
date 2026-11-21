<?php

namespace Playbloom\Plugin\Diagnosis\Command;

use Playbloom\Plugin\Diagnosis\Diagnoser;
use Playbloom\Plugin\Diagnosis\DiagnosisConfig;
use Playbloom\Plugin\Diagnosis\DiagnosisStore;
use Playbloom\Service\LogRedactor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Diagnoses one kept build. The plugin runs it after a build fails, in a
 * process of its own; it refuses to run where COMPOSER_AUTH is set.
 */
#[AsCommand(name: 'satisfy:diagnose', description: 'Asks a model why a failed build failed, from the redacted copy of its log, and keeps the answer')]
final class DiagnoseCommand extends Command
{
    public function __construct(
        private readonly DiagnosisConfig $config,
        private readonly DiagnosisStore $store,
        private readonly Diagnoser $diagnoser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'The id the failed build is kept under');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (false !== getenv('COMPOSER_AUTH') || isset($_SERVER['COMPOSER_AUTH']) || isset($_ENV['COMPOSER_AUTH'])) {
            $output->writeln('<error>COMPOSER_AUTH is set. A diagnosis runs without it; nothing was sent.</error>');

            return Command::FAILURE;
        }
        if (!$this->config->isConfigured()) {
            $output->writeln('<error>SATISFY_DIAGNOSIS_URL and SATISFY_DIAGNOSIS_MODEL are needed.</error>');

            return Command::FAILURE;
        }
        $id = (string) $input->getArgument('id');
        if (!$this->store->exists($id)) {
            $output->writeln(sprintf('<error>No failed build is kept as "%s".</error>', $id));

            return Command::FAILURE;
        }

        $started = microtime(true);
        try {
            $diagnosis = $this->diagnoser->diagnose($id);
        } catch (\Throwable $exception) {
            $this->store->save($id, $this->outcome('failed', null, LogRedactor::redact($exception->getMessage()), $started));
            $output->writeln(sprintf('<error>%s</error>', LogRedactor::redact($exception->getMessage())));

            return Command::FAILURE;
        }
        $this->store->save($id, $this->outcome('done', $diagnosis, null, $started));
        $output->writeln($diagnosis);

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function outcome(string $status, ?string $diagnosis, ?string $error, float $started): array
    {
        return [
            'status' => $status,
            'diagnosis' => $diagnosis,
            'error' => $error,
            'model' => $this->config->getModel(),
            'seconds' => round(microtime(true) - $started, 1),
            'diagnosed_at' => date(\DATE_ATOM),
        ];
    }
}
