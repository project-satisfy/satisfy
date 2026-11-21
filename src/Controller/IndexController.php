<?php

namespace Playbloom\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('/', name: 'index')]
    public function indexAction(): Response
    {
        $indexFile = $this->projectDir . '/public/index.html';
        if (!file_exists($indexFile)) {
            return $this->render('unavailable.html.twig');
        }

        // Streamed rather than read into memory: a large catalogue's page runs to megabytes.
        $response = new BinaryFileResponse($indexFile);
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        return $response;
    }
}
