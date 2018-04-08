<?php

namespace Playbloom\Satisfy\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;

class SatisController extends AbstractProtectedController
{
    /**
     * @return Response
     */
    public function buildAction()
    {
        $this->checkAccess();

        return $this->render('@PlaybloomSatisfy/satis_build.html.twig');
    }

    /**
     * @return StreamedResponse
     */
    public function buildRunAction()
    {
        $this->checkAccess();

        ini_set('implicit_flush', 1);
        ob_implicit_flush(true);

        $path = $this->container->getParameter('kernel.project_dir');
        $env = $this->getDefaultEnv();
        $env['HOME'] = $this->container->getParameter('composer.home');

        $arguments = $this->container->getParameter('satis_filename');
        $arguments .= ' --skip-errors --no-ansi --no-interaction --verbose';

        $process = new Process($path . '/bin/satis build', $path, $env, $arguments, 600);
        $process->start();

        $processRead = function () use ($process) {
            $print = function ($data) {
                $data = trim($data);
                if (empty($data)) {
                    return;
                }
                echo 'data: ', $data, PHP_EOL, PHP_EOL;
            };
            $print('$ ' . $process->getCommandLine() . ' ' . $process->getInput());
            foreach ($process as $content) {
                $print($content);
            }
            $print($process->getExitCodeText());
            $print('__done__');
        };

        return new StreamedResponse($processRead, Response::HTTP_OK, ['Content-Type' => 'text/event-stream']);
    }

    /**
     * @return array
     */
    private function getDefaultEnv(): array
    {
        $env = [];

        foreach ($_SERVER as $k => $v) {
            if (is_string($v) && false !== $v = getenv($k)) {
                $env[$k] = $v;
            }
        }

        foreach ($_ENV as $k => $v) {
            if (is_string($v)) {
                $env[$k] = $v;
            }
        }

        return $env;
    }
}
