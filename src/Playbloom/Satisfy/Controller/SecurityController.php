<?php

namespace Playbloom\Satisfy\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to handle user login
 */
class SecurityController extends Controller
{
    public function loginAction(): Response
    {
        $authUtils = $this->get('security.authentication_utils');
        // last username entered by the user
        $username = $authUtils->getLastUsername();
        // get the login error if there is one
        $error = $authUtils->getLastAuthenticationError();

        return $this->render('@PlaybloomSatisfy/login.html.twig', compact('username', 'error'));
    }
}
