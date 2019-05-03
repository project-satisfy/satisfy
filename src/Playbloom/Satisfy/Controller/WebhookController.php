<?php

namespace Playbloom\Satisfy\Controller;

use Playbloom\Satisfy\Webhook\AbstractWebhook;
use Playbloom\Satisfy\Webhook\BitbucketWebhook;
use Playbloom\Satisfy\Webhook\GithubWebhook;
use Playbloom\Satisfy\Webhook\GitlabWebhook;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class WebhookController extends Controller
{
    /**
     * @throws BadRequestHttpException
     * @throws ServiceUnavailableHttpException
     */
    public function bitbucketAction(Request $request): Response
    {
        $webhook = $this->container->get(BitbucketWebhook::class);

        return $this->handleRequest($request, $webhook);
    }

    public function githubAction(Request $request): Response
    {
        $webhook = $this->container->get(GithubWebhook::class);

        return $this->handleRequest($request, $webhook);
    }

    public function gitlabAction(Request $request): Response
    {
        $webhook = $this->container->get(GitlabWebhook::class);

        return $this->handleRequest($request, $webhook);
    }

    private function handleRequest(Request $request, AbstractWebhook $webhook): Response
    {
        return $webhook->getResponse($request);
    }
}
