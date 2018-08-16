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

        $runner = $this->container->get('satisfy.runner.satis_build');
        $output = $runner->run();

        $processRead = function () use ($output) {
            foreach ($output as $line) {
                $this->outputStream($line);
            }
            $this->outputStream('__done__');
        };

        return new StreamedResponse($processRead, Response::HTTP_OK, ['Content-Type' => 'text/event-stream']);
    }

    protected function outputStream(string $line)
    {
        echo 'data: ', $line, PHP_EOL, PHP_EOL;
    }
}
