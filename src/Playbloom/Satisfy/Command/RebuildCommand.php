<?php

namespace Playbloom\Satisfy\Command;

use Composer\Json\JsonFile;
use Composer\Satis\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class RebuildCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('satisfy:rebuild')
            ->setDescription('Rebuild composer packages when config is changed or definitions is outdated')
            ->setHelp('')
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
        $input->setArgument('command', 'build');
        $configFile = $this->container->getParameter('satis_filename');
        $input->setArgument('file', $configFile);
        $lifetime = (int)$input->getOption('lifetime');

        if (!empty($lifetime) && is_file($configFile)) {
            if (!$outputDir = $input->getArgument('output-dir')) {
                $file = new JsonFile($configFile);
                $config = $file->read();
                $outputDir = $config['output-dir'] ?? null;
            }

            $modifiedAt = filemtime($configFile);
            $lastUpdate = @filemtime($outputDir . '/packages.json');
            if ($modifiedAt < $lastUpdate && time() - $lastUpdate < $lifetime) {
                return 0;
            }
        }

        $application = new Application();

        return $application->doRun($input, $output);
    }
}
