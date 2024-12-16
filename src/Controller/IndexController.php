<?php

namespace Playbloom\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
            return new Response($this->render('unavailable.html.twig'));
        }

        return new Response(file_get_contents($indexFile));
    }
}
