<?php

namespace Playbloom\Controller;

use Playbloom\Webhook\AbstractWebhook;
use Playbloom\Webhook\BitbucketWebhook;
use Playbloom\Webhook\DevOpsWebhook;
use Playbloom\Webhook\GiteaWebhook;
use Playbloom\Webhook\GithubWebhook;
use Playbloom\Webhook\GitlabWebhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;

class WebhookController extends AbstractController
{
    /**
     * @throws BadRequestHttpException
     * @throws ServiceUnavailableHttpException
     */
    #[Route('/webhook/bitbucket', name: 'webhook_bitbucket', methods: ['GET', 'POST'])]
    public function bitbucketAction(Request $request): Response
    {
        $webhook = $this->container->get(BitbucketWebhook::class);

        return $this->handleRequest($request, $webhook);
    }

    #[Route('/webhook/github', name: 'webhook_github', methods: ['GET', 'POST'])]
    public function githubAction(Request $request): Response
    {
        $webhook = $this->container->get(GithubWebhook::class);

        return $this->handleRequest($request, $webhook);
    }

    #[Route('/webhook/gitea', name: 'webhook_gitea', methods: ['GET', 'POST'])]
    public function giteaAction(Request $request): Response
    {
        $webhook = $this->container->get(GiteaWebhook::class);

        return $this->handleRequest($request, $webhook);
    }

    #[Route('/webhook/gitlab', name: 'webhook_gitlab', methods: ['GET', 'POST'])]
    public function gitlabAction(Request $request): Response
    {
        $webhook = $this->container->get(GitlabWebhook::class);

        return $this->handleRequest($request, $webhook);
    }

    #[Route('/webhook/devops', name: 'webhook_devops', methods: ['GET', 'POST'])]
    public function devopsAction(Request $request): Response
    {
        $webhook = $this->container->get(DevOpsWebhook::class);

        return $this->handleRequest($request, $webhook);
    }

    private function handleRequest(Request $request, AbstractWebhook $webhook): Response
    {
        return $webhook->getResponse($request);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[] = BitbucketWebhook::class;
        $services[] = GithubWebhook::class;
        $services[] = GitlabWebhook::class;
        $services[] = GiteaWebhook::class;
        $services[] = DevOpsWebhook::class;

        return $services;
    }
}
