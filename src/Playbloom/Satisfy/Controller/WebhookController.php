<?php

namespace Playbloom\Satisfy\Controller;

use Playbloom\Satisfy\Webhook\AbstractWebhook;
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
        $webhook = $this->container->get('satisfy.webhook.bitbucket');

        return $this->handleRequest($request, $webhook);
    }

    public function githubAction(Request $request): Response
    {
        $webhook = $this->container->get('satisfy.webhook.github');

        return $this->handleRequest($request, $webhook);
    }

    public function gitlabAction(Request $request): Response
    {
        $webhook = $this->container->get('satisfy.webhook.gitlab');

        return $this->handleRequest($request, $webhook);
    }

    private function handleRequest(Request $request, AbstractWebhook $webhook): Response
    {
        try {
            $status = $webhook->handle($request);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException();
        } catch (\Throwable $exception) {
            throw new ServiceUnavailableHttpException();
        }

        return new Response($status);
    }
}
