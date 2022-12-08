<?php

namespace Playbloom\Satisfy\Webhook;

use InvalidArgumentException;
use Playbloom\Satisfy\Model\RepositoryInterface;
use Playbloom\Satisfy\Service\Manager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class DevOpsWebhook extends AbstractWebhook
{
    private const HTTP_TOKEN = 'X-DEVOPS-TOKEN';

    public function __construct(
        Manager $manager,
        EventDispatcherInterface $dispatcher,
        ?string $secret = null
    ) {
        parent::__construct($manager, $dispatcher);
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
        $this->secret = $secret;
    }

    /**
     * {@inheritDoc}
     */
    protected function validate(Request $request): void
    {
        if ($request->headers->get(self::HTTP_TOKEN) !== $this->secret) {
            throw new \InvalidArgumentException('Invalid Token');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getRepository(Request $request): RepositoryInterface
    {
        $content = json_decode($request->getContent(), true);
        if (
            !is_array($content)
            || !array_key_exists('resource', $content)
            || !is_array($content['resource'])
            || !array_key_exists('repository', $content['resource'])
            || !is_array($content['resource']['repository'])
            || !array_key_exists('url', $content['resource']['repository'])
            || empty($content['resource']['repository']['url'])
        ) {
            throw new \InvalidArgumentException('Invalid Request');
        }
        $repositoryUrlHttp = $content['resource']['repository']['url'];
        $repositoryUrlPattern = preg_replace('/(https:\/\/)([^\/]+)(.+)/', '$3', $repositoryUrlHttp);
        $repository = $this->manager->findByUrl('#'.$repositoryUrlPattern.'$#');
        if (!$repository instanceof RepositoryInterface) {
            throw new \InvalidArgumentException('Invalid Repository');
        }

        return $repository;
    }
}
