<?php

namespace Playbloom\Satisfy\Controller;

use Playbloom\Satisfy\Http\ProcessResponse;
use Playbloom\Satisfy\Runner\SatisBuildRunner;
use Symfony\Component\HttpFoundation\Response;

class SatisController extends AbstractProtectedController
{
    public function buildAction(): Response
    {
        $this->checkAccess();

        return $this->render('@PlaybloomSatisfy/satis_build.html.twig');
    }

    public function buildRunAction(SatisBuildRunner $runner): Response
    {
        $this->checkAccess();
        $output = $runner->run();

        return ProcessResponse::createFromOutput($output);
    }
}
