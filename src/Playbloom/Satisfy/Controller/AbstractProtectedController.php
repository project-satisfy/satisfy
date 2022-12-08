<?php

namespace Playbloom\Satisfy\Controller;

use Playbloom\Satisfy\Service\Manager;
use Playbloom\Satisfy\Validator\EnvValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractProtectedController extends AbstractController
{
    /**
     * Check admin access.
     */
    protected function checkAccess(): void
    {
        if (!$this->getParameter('admin.auth')) {
            return;
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    }

    protected function checkEnvironment(): void
    {
        $validator = $this->container->get(EnvValidator::class);
        try {
            $validator->validate();
        } catch (\RuntimeException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }
    }

    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();
        $services[] = EnvValidator::class;
        $services[] = Manager::class;

        return $services;
    }
}
