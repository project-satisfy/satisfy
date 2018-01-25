<?php

use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;
use Composer\{Composer, Factory};
use Composer\IO\{ConsoleIO, IOInterface};
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends BaseApplication
{
    /** @var IOInterface */
    protected $io;

    /** @var Composer */
    protected $composer;

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->io = new ConsoleIO($input, $output, $this->getHelperSet());

        return parent::doRun($input, $output);
    }

    /**
     * @param bool $required
     * @param null $config
     * @return Composer
     */
    public function getComposer($required = true, $config = null)
    {
        if (null === $this->composer) {
            try {
                $this->composer = Factory::create($this->io, $config);
            } catch (\InvalidArgumentException $e) {
                echo $e;
                exit(1);
            }
        }

        return $this->composer;
    }
}
