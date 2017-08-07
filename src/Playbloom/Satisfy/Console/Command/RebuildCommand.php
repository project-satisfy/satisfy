<?php

namespace Playbloom\Satisfy\Console\Command;

use Composer\Json\JsonFile;
use Composer\Satis\Console\Command\BuildCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Silex\Application;
use League\Flysystem\FilesystemInterface;

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
        /* @var Application $app */
        $app = require __DIR__ . '/../../../../../app/bootstrap.php';

        /* @var FilesystemInterface $filesystem */
        $filesystem = $app['filesystem'];

        $inputFile = $input->getArgument('file');
        $lifetime = (int)$input->getOption('lifetime');

        $configFile = tempnam('/tmp', 'satis');
        file_put_contents($configFile, $filesystem->read($inputFile));
        $input->setArgument('file', $configFile);

        parent::execute($input, $output);
    }
}
