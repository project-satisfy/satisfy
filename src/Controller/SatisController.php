<?php

namespace Playbloom\Controller;

use Playbloom\Http\ProcessResponse;
use Playbloom\Runner\SatisBuildRunner;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SatisController extends AbstractProtectedController
{
    #[Route('/admin/satis/build', name: 'satis_build', methods: ['GET'])]
    public function buildAction(): Response
    {
        $this->checkAccess();

        return $this->render('views/satis_build.html.twig');
    }

    #[Route('/admin/satis/buildRun', name: 'satis_build_run', methods: ['GET'])]
    public function buildRunAction(SatisBuildRunner $runner): Response
    {
        $this->checkAccess();
        $output = $runner->run();

        return ProcessResponse::createFromOutput($output);
    }
}
