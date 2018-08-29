<?php

namespace Playbloom\Satisfy\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class WebhookController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @throws BadRequestHttpException
     * @throws ServiceUnavailableHttpException
     */
    public function bitbucketAction(Request $request): Response
    {
        $webhook = $this->container->get('satisfy.webhook.bitbucket');
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
