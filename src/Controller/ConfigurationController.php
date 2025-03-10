<?php

namespace Playbloom\Controller;

use Playbloom\Form\Type\ConfigurationType;
use Playbloom\Service\Manager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConfigurationController extends AbstractProtectedController
{
    #[Route('/admin/configuration', name: 'configuration', methods: ['GET', 'POST'])]
    public function indexAction(Request $request): Response
    {
        $this->checkAccess();
        $this->checkEnvironment();

        $manager = $this->container->get(Manager::class);
        $config = $manager->getConfig();
        $form = $this->createForm(ConfigurationType::class, $config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();
            $this->addFlash('success', 'Configuration updated successfully');
        }

        return $this->render('views/configuration.html.twig', ['form' => $form->createView()]);
    }
}
