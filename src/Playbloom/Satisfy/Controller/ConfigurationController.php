<?php

namespace Playbloom\Satisfy\Controller;

use Playbloom\Satisfy\Form\Type\ConfigurationType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ConfigurationController extends Controller
{
    public function indexAction(Request $request)
    {
        $manager = $this->get('satisfy.manager');
        $config = $manager->getConfig();
        $form = $this->createForm(ConfigurationType::class, $config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();
        }

        return $this->render('@PlaybloomSatisfy/configuration.html.twig', array('form' => $form->createView()));
    }
}
