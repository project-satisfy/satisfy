<?php

namespace Playbloom\Satisfy\Console\Command;

use Composer\Json\JsonFile;
use Composer\Satis\Console\Command\BuildCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RebuildCommand extends BuildCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('satisfy:rebuild')
            ->setDescription('Rebuild composer packages when config is changed or definitions is outdated')
            ->setHelp(null)
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configFile = $input->getArgument('file');
        $lifetime = (int)$input->getOption('lifetime');

        if (is_file($configFile) && !empty($lifetime)) {
            if (!$outputDir = $input->getArgument('output-dir')) {
                $file = new JsonFile($configFile);
                $config = $file->read();
                $outputDir = isset($config['output-dir']) ? $config['output-dir'] : null;
            }

            $modifiedAt = filemtime($configFile);
            $lastUpdate = @filemtime($outputDir . '/packages.json');
            if ($modifiedAt < $lastUpdate && time() - $lastUpdate < $lifetime) {
                return;
            }
        }

        parent::execute($input, $output);
    }
}
