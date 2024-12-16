<?php

namespace Playbloom\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Controller to handle user login
 */
class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function loginAction(AuthenticationUtils $authenticationUtils): Response
    {
        // last username entered by the user
        $username = $authenticationUtils->getLastUsername();
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('views/login.html.twig', compact('username', 'error'));
    }
}
