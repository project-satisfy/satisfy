<?php

namespace Playbloom\Controller;

use Playbloom\Exception\ConfigurationChangedException;
use Playbloom\Form\Type\ConfigurationType;
use Playbloom\Service\Manager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
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
        $form = $this->createConfigurationForm($manager);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // The form replaces almost the whole file, so it is saved only over
            // the version it was shown with: anything a webhook or another
            // writer changed meanwhile would otherwise be lost. A submission
            // without a version is saved as in 3.x.
            $version = $form->get('version')->getData();
            try {
                $manager->flush(is_string($version) && '' !== $version ? $version : null);
                $this->addFlash('success', 'Configuration updated successfully');
                $form = $this->createConfigurationForm($manager);
            } catch (ConfigurationChangedException $exception) {
                $form->addError(new FormError(
                    'satis.json was changed by someone else while you were editing it. Reload the page and make your changes again.'
                ));
            }
        }

        return $this->render('views/configuration.html.twig', ['form' => $form->createView()]);
    }

    private function createConfigurationForm(Manager $manager): FormInterface
    {
        $form = $this->createForm(ConfigurationType::class, $manager->getConfig());
        $form->add('version', HiddenType::class, ['mapped' => false, 'data' => $manager->getVersion()]);

        return $form;
    }
}
