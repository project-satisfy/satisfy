<?php

namespace Playbloom\Satisfy\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class AbstractProtectedController extends Controller
{
    /**
     * Check admin access.
     */
    protected function checkAccess()
    {
        if (!$this->getParameter('admin.auth')) {
            return;
        }

        parent::denyAccessUnlessGranted('ROLE_ADMIN');
    }
}
