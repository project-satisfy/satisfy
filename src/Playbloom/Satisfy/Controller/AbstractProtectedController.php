<?php

namespace Playbloom\Satisfy\Controller;

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
}
