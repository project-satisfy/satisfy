<?php

namespace Playbloom\Satisfy\Controller;

use Playbloom\Satisfy\Form\Type\ConfigurationType;
use Playbloom\Satisfy\Service\Manager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationController extends AbstractProtectedController
{
    public function indexAction(Request $request): Response
    {
        $this->checkAccess();
        $this->checkEnvironment();

        $manager = $this->get(Manager::class);
        $config = $manager->getConfig();
        $form = $this->createForm(ConfigurationType::class, $config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();
        }

        return $this->render('@PlaybloomSatisfy/configuration.html.twig', ['form' => $form->createView()]);
    }
}
