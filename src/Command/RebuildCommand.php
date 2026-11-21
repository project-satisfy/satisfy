<?php

namespace Playbloom\Command;

use Composer\Satis\Console\Application;
use Playbloom\Service\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;

class RebuildCommand extends Command
{
    public function __construct(
        #[Autowire('%satis_filename%')]
        private readonly string $satisFilename,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly LockFactory $buildLockFactory,
        private readonly Manager $manager,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('satisfy:rebuild')
            ->setDescription('Rebuild composer packages when config is changed or definitions is outdated')
            ->setHelp('Builds in this process, under the same lock as the worker, so the two never overlap.')
            ->addArgument('file', InputArgument::OPTIONAL, 'Json file to use', './satis.json')
            ->addArgument('output-dir', InputArgument::OPTIONAL, 'Location where to output built files', null)
            ->addArgument(
                'packages',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Packages that should be built. If not provided, all packages are built.',
                null
            )
            ->addOption(
                'lifetime',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Maximum lifetime of composer definitions in seconds'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $this->satisFilename;
        $lifetime = (int)$input->getOption('lifetime');
        $outputDir = $input->getArgument('output-dir');
        if (!$outputDir) {
            // The configured directory or Satisfy's default, as the worker uses,
            // relative to the project directory, where the worker runs Satis.
            $outputDir = $this->manager->getConfig()->getOutputDir();
            if (!(new Filesystem())->isAbsolutePath($outputDir)) {
                $outputDir = $this->projectDir . '/' . $outputDir;
            }
        }

        if (!empty($lifetime) && is_file($configFile)) {
            $modifiedAt = filemtime($configFile);
            $lastUpdate = @filemtime($outputDir . '/packages.json');
            if ($modifiedAt < $lastUpdate && time() - $lastUpdate < $lifetime) {
                return 0;
            }
        }
        $satisInput = new ArrayInput([
            'command' => 'build',
            'file' => $configFile,
            'output-dir' => $outputDir,
            'packages' => $input->getArgument('packages'),
            '--skip-errors' => true,
        ]);
        $application = new Application();

        $lock = $this->buildLockFactory->createLock('build');
        $lock->acquire(true);
        try {
            return $application->doRun($satisInput, $output);
        } finally {
            $lock->release();
        }
    }
}
