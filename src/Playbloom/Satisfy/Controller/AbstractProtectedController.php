<?php

namespace Playbloom\Satisfy\Controller;

use Playbloom\Satisfy\Validator\EnvValidator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class AbstractProtectedController extends Controller
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
        $validator = $this->get(EnvValidator::class);
        try {
            $validator->validate();
        } catch (\RuntimeException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }
    }
}
