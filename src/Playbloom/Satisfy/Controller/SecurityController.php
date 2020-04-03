<?php

namespace Playbloom\Satisfy\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Controller to handle user login
 */
class SecurityController extends AbstractController
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

    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();
        $services['security.authentication_utils'] = AuthenticationUtils::class;

        return $services;
    }
}
