<?php

namespace Playbloom\Tests\Webhook;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playbloom\Event\BuildEvent;
use Playbloom\Event\WebhookReceivedEvent;
use Playbloom\Model\Repository;
use Playbloom\Service\Manager;
use Playbloom\Webhook\AbstractWebhook;
use Playbloom\Webhook\BitbucketWebhook;
use Playbloom\Webhook\DevOpsWebhook;
use Playbloom\Webhook\GiteaWebhook;
use Playbloom\Webhook\GithubWebhook;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * GitHub, Gitea, Bitbucket Cloud and Azure DevOps deliveries, shaped as each
 * provider documents them (tests/fixtures/*), against a satis.json naming the
 * repository in each of the ways Composer accepts.
 */
class WebhookProvidersTest extends TestCase
{
    use ProphecyTrait;

    private const SECRET = 'webhook-secret';

    /** @var string[] */
    private array $built = [];

    /** @var WebhookReceivedEvent[] */
    private array $received = [];

    public static function githubUrlProvider(): \Generator
    {
        yield 'clone_url' => ['https://github.com/example-org/widget.git'];
        yield 'ssh_url' => ['git@github.com:example-org/widget.git'];
        yield 'html_url' => ['https://github.com/example-org/widget'];
    }

    #[DataProvider('githubUrlProvider')]
    public function testGithubSignedTagPushIsQueued(string $configured): void
    {
        $body = $this->fixture('github-push-tag.json');
        $request = $this->request($body, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', $body, self::SECRET),
        ]);

        $response = $this->github([$configured])->getResponse($request);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('queued', $response->getContent());
        $this->assertSame([$configured], $this->built);
        $this->assertSame('github', $this->received[0]->getProvider());
        $this->assertSame('v1.2.0', $this->received[0]->getCreatedTag());
    }

    public static function githubLiveDeliveryProvider(): \Generator
    {
        yield 'ping' => ['ping', 200, 'skipped: ping', null];
        yield 'push to a branch' => ['push-branch', 202, 'queued', null];
        yield 'tag pushed' => ['push-tag', 202, 'queued', 'webhook-fixture'];
        yield 'tag pushed, form-encoded' => ['push-tag-form', 202, 'queued', 'webhook-fixture'];
        yield 'tag deleted, form-encoded' => ['delete-tag-form', 202, 'queued', null];
    }

    /**
     * Deliveries GitHub made from repository webhooks on FivePaths/satisfy on
     * 22 October 2026, each checked against GitHub's X-Hub-Signature-256 as
     * sent. The pusher's email address was then redacted and the deliveries
     * signed again with the fixture secret, "github-live-fixture".
     */
    #[DataProvider('githubLiveDeliveryProvider')]
    public function testGithubLiveDelivery(string $name, int $status, string $content, ?string $tag): void
    {
        $webhook = new GithubWebhook($this->manager(['https://github.com/FivePaths/satisfy.git'])->reveal(), $this->dispatcher());
        $webhook->setSecret('github-live-fixture');

        $this->assertLiveDelivery($webhook, "github-live/$name", 'https://github.com/FivePaths/satisfy.git', $status, $content, $tag);
    }

    public static function giteaLiveDeliveryProvider(): \Generator
    {
        yield 'push to a branch' => ['push-branch', null];
        yield 'tag pushed' => ['push-tag', 'v1.0.0'];
        yield 'tag pushed, form-encoded' => ['push-tag-form', 'v1.0.0'];
        yield 'tag created' => ['create-tag', null];
        yield 'tag deleted' => ['delete-tag', null];
    }

    /**
     * Deliveries Gitea 1.27.3 made on 22 October 2026, from a local instance
     * whose ROOT_URL is http://gitea.example.test:3000/, to webhooks with the
     * secret "gitea-live-fixture". Unchanged: body and headers as sent.
     */
    #[DataProvider('giteaLiveDeliveryProvider')]
    public function testGiteaLiveDelivery(string $name, ?string $tag): void
    {
        $configured = 'http://gitea.example.test:3000/fixture/widget.git';
        $webhook = new GiteaWebhook($this->manager([$configured])->reveal(), $this->dispatcher());
        $webhook->setSecret('gitea-live-fixture');

        $this->assertLiveDelivery($webhook, "gitea-live/$name", $configured, 202, 'queued', $tag);
    }

    public function testGithubFormEncodedDeliveryIsQueued(): void
    {
        $body = http_build_query(['payload' => $this->fixture('github-push-tag.json')]);
        $request = Request::create('/webhook/github', 'POST', ['payload' => $this->fixture('github-push-tag.json')], [], [], [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_X_GITHUB_EVENT' => 'push',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=' . hash_hmac('sha256', $body, self::SECRET),
        ], $body);

        $response = $this->github(['https://github.com/example-org/widget.git'])->getResponse($request);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('v1.2.0', $this->received[0]->getCreatedTag());
    }

    public static function badGithubSignatureProvider(): \Generator
    {
        yield 'missing' => [[]];
        yield 'wrong secret' => [['X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', '{}', 'other')]];
        yield 'SHA-1 only' => [['X-Hub-Signature' => 'sha1=0000']];
    }

    #[DataProvider('badGithubSignatureProvider')]
    public function testGithubBadSignatureIsRefused(array $headers): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->github(['https://github.com/example-org/widget.git'])
            ->getResponse($this->request($this->fixture('github-push-tag.json'), $headers + ['X-GitHub-Event' => 'push']));
    }

    public function testGithubPingIsAnsweredWithoutABuild(): void
    {
        $body = $this->fixture('github-ping.json');
        $request = $this->request($body, [
            'X-GitHub-Event' => 'ping',
            'X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', $body, self::SECRET),
        ]);

        $response = $this->github([])->getResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('skipped: ping', $response->getContent());
        $this->assertSame([], $this->built);
    }

    public function testGithubAutoAddUsesTheHttpsCloneUrl(): void
    {
        $manager = $this->manager([]);
        $manager->add(Argument::that(fn (Repository $r) => 'https://github.com/example-org/widget.git' === $r->getUrl()))
            ->shouldBeCalledOnce();
        $webhook = new GithubWebhook($manager->reveal(), $this->dispatcher());
        $webhook->setSecret(self::SECRET);
        $webhook->setAutoAdd(true);
        $webhook->setAutoAddType('vcs');

        $body = $this->fixture('github-push-tag.json');
        $response = $webhook->getResponse($this->request($body, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', $body, self::SECRET),
        ]));

        $this->assertSame(202, $response->getStatusCode());
    }

    public function testGithubUnknownRepositoryIsRefused(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Cannot find specified repository');

        $webhook = new GithubWebhook($this->manager(['https://github.com/example-org/other.git'])->reveal(), $this->dispatcher());
        $webhook->getResponse($this->request($this->fixture('github-push-tag.json'), ['X-GitHub-Event' => 'push']));
    }

    public static function giteaUrlProvider(): \Generator
    {
        yield 'clone_url' => ['https://gitea.example.com/example-org/widget.git'];
        yield 'ssh_url' => ['git@gitea.example.com:example-org/widget.git'];
        yield 'html_url' => ['https://gitea.example.com/example-org/widget'];
    }

    #[DataProvider('giteaUrlProvider')]
    public function testGiteaSignedPushIsQueued(string $configured): void
    {
        $body = $this->fixture('gitea-push.json');
        $request = $this->request($body, [
            'X-Gitea-Event' => 'push',
            'X-GitHub-Event' => 'push',
            'X-Gitea-Signature' => hash_hmac('sha256', $body, self::SECRET),
            'X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', $body, self::SECRET),
        ]);

        $response = $this->gitea([$configured])->getResponse($request);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame([$configured], $this->built);
        $this->assertSame('gitea', $this->received[0]->getProvider());
        $this->assertNull($this->received[0]->getCreatedTag());
    }

    public function testGiteaBadSignatureIsRefused(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $body = $this->fixture('gitea-push.json');
        $this->gitea(['https://gitea.example.com/example-org/widget.git'])
            ->getResponse($this->request($body, ['X-Gitea-Signature' => hash_hmac('sha256', $body, 'other')]));
    }

    public function testGiteaSecretInThePayloadIsStillAccepted(): void
    {
        $payload = json_decode($this->fixture('gitea-push.json'), true);
        $payload['secret'] = self::SECRET;

        $response = $this->gitea(['https://gitea.example.com/example-org/widget.git'])
            ->getResponse($this->request((string) json_encode($payload), []));

        $this->assertSame(202, $response->getStatusCode());
    }

    public static function bitbucketUrlProvider(): \Generator
    {
        yield 'https' => ['https://bitbucket.org/example-ws/widget.git'];
        yield 'https with a user name' => ['https://example-dev@bitbucket.org/example-ws/widget.git'];
        yield 'https without .git' => ['https://bitbucket.org/example-ws/widget'];
        yield 'scp-like ssh' => ['git@bitbucket.org:example-ws/widget.git'];
        yield 'ssh URL' => ['ssh://git@bitbucket.org/example-ws/widget.git'];
        yield 'other case' => ['https://bitbucket.org/Example-WS/Widget.git'];
    }

    #[DataProvider('bitbucketUrlProvider')]
    public function testBitbucketSignedTagPushIsQueued(string $configured): void
    {
        $body = $this->fixture('bitbucket-push.json');
        $request = $this->request($body, [
            'X-Event-Key' => 'repo:push',
            'X-Hub-Signature' => 'sha256=' . hash_hmac('sha256', $body, self::SECRET),
        ], '203.0.113.9');

        $response = $this->bitbucket([$configured], self::SECRET)->getResponse($request);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame([$configured], $this->built);
        $this->assertSame('bitbucket', $this->received[0]->getProvider());
        $this->assertSame('v1.2.0', $this->received[0]->getCreatedTag());
    }

    public static function bitbucketAddressProvider(): \Generator
    {
        yield 'Atlassian IPv4' => ['104.192.137.10', 202];
        yield 'Atlassian IPv4, added since 3.x' => ['13.200.41.130', 202];
        yield 'Atlassian IPv6' => ['2401:1d80:3000::10', 202];
        yield 'elsewhere' => ['203.0.113.9', 400];
    }

    #[DataProvider('bitbucketAddressProvider')]
    public function testBitbucketWithoutASecretChecksTheAddress(string $address, int $expected): void
    {
        if (400 === $expected) {
            $this->expectException(BadRequestHttpException::class);
        }

        $response = $this->bitbucket(['git@bitbucket.org:example-ws/widget.git'], '')
            ->getResponse($this->request($this->fixture('bitbucket-push.json'), ['X-Event-Key' => 'repo:push'], $address));

        $this->assertSame($expected, $response->getStatusCode());
    }

    public function testBitbucketWithASecretRefusesAnUnsignedDeliveryFromAtlassian(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->bitbucket(['git@bitbucket.org:example-ws/widget.git'], self::SECRET)
            ->getResponse($this->request($this->fixture('bitbucket-push.json'), ['X-Event-Key' => 'repo:push'], '104.192.137.10'));
    }

    public function testBitbucketDeletedBranchIsNotACreatedTag(): void
    {
        $payload = json_decode($this->fixture('bitbucket-push.json'), true);
        $payload['push']['changes'][0]['new'] = null;
        $payload['push']['changes'][0]['old'] = ['type' => 'branch', 'name' => 'feature'];

        $event = new WebhookReceivedEvent('bitbucket', Request::create('/'), $payload);

        $this->assertNull($event->getCreatedTag());
    }

    public static function devopsUrlProvider(): \Generator
    {
        yield 'https' => ['https://dev.azure.com/example-org/Example/_git/widget'];
        yield 'https with a user name' => ['https://example-org@dev.azure.com/example-org/Example/_git/widget'];
        yield 'ssh' => ['git@ssh.dev.azure.com:v3/example-org/Example/widget'];
    }

    #[DataProvider('devopsUrlProvider')]
    public function testDevopsPushIsQueued(string $configured): void
    {
        $request = $this->request($this->fixture('devops-push.json'), ['X-DEVOPS-TOKEN' => self::SECRET]);

        $response = $this->devops([$configured])->getResponse($request);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame([$configured], $this->built);
        $this->assertSame('devops', $this->received[0]->getProvider());
        $this->assertNull($this->received[0]->getCreatedTag());
    }

    public function testDevopsApiUrlAloneMatchesNothing(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $payload = json_decode($this->fixture('devops-push.json'), true);
        unset($payload['resource']['repository']['remoteUrl']);

        $this->devops(['https://dev.azure.com/example-org/Example/_git/widget'])
            ->getResponse($this->request((string) json_encode($payload), ['X-DEVOPS-TOKEN' => self::SECRET]));
    }

    public function testDevopsTagPushIsACreatedTag(): void
    {
        $payload = json_decode($this->fixture('devops-push.json'), true);
        $payload['resource']['refUpdates'] = [
            ['name' => 'refs/tags/v1.1.0', 'oldObjectId' => 'c0d2d6e3c0a1b2f8e5d4a7b9c3e1f0a2b4d6e8f0', 'newObjectId' => str_repeat('0', 40)],
            ['name' => 'refs/tags/v1.2.0', 'oldObjectId' => str_repeat('0', 40), 'newObjectId' => 'c0d2d6e3c0a1b2f8e5d4a7b9c3e1f0a2b4d6e8f0'],
        ];

        $event = new WebhookReceivedEvent('devops', Request::create('/'), $payload);

        $this->assertSame('v1.2.0', $event->getCreatedTag());
    }

    private function assertLiveDelivery(AbstractWebhook $webhook, string $fixture, string $configured, int $status, string $content, ?string $tag): void
    {
        $body = $this->fixture("$fixture.body");
        $headers = json_decode($this->fixture("$fixture.headers.json"), true);

        $response = $webhook->getResponse($this->request($body, $headers));

        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame($content, $response->getContent());
        $this->assertSame(202 === $status ? [$configured] : [], $this->built);
        $this->assertSame($webhook->getProvider(), $this->received[0]->getProvider());
        $this->assertSame($tag, $this->received[0]->getCreatedTag());
    }

    private function github(array $configured): GithubWebhook
    {
        $webhook = new GithubWebhook($this->manager($configured)->reveal(), $this->dispatcher());
        $webhook->setSecret(self::SECRET);

        return $webhook;
    }

    private function gitea(array $configured): GiteaWebhook
    {
        $webhook = new GiteaWebhook($this->manager($configured)->reveal(), $this->dispatcher());
        $webhook->setSecret(self::SECRET);

        return $webhook;
    }

    private function bitbucket(array $configured, string $secret): BitbucketWebhook
    {
        $webhook = new BitbucketWebhook($this->manager($configured)->reveal(), $this->dispatcher());
        $webhook->setSecret($secret);

        return $webhook;
    }

    private function devops(array $configured): AbstractWebhook
    {
        return new DevOpsWebhook($this->manager($configured)->reveal(), $this->dispatcher(), self::SECRET);
    }

    /**
     * A Manager whose satis.json lists $configured, looked up as Manager::findByUrl() does.
     *
     * @param string[] $configured
     */
    private function manager(array $configured): ObjectProphecy
    {
        $manager = $this->prophesize(Manager::class);
        $manager->findByUrl(Argument::type('string'))->will(function (array $args) use ($configured) {
            foreach ($configured as $url) {
                if (preg_match($args[0], $url)) {
                    return new Repository($url);
                }
            }

            return null;
        });

        return $manager;
    }

    private function dispatcher(): EventDispatcher
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(WebhookReceivedEvent::class, function (WebhookReceivedEvent $event): void {
            $this->received[] = $event;
        });
        $dispatcher->addListener(BuildEvent::class, function (BuildEvent $event): void {
            $this->built[] = $event->getRepository()->getUrl();
            $event->markQueued();
        });

        return $dispatcher;
    }

    private function request(string $body, array $headers, string $address = '127.0.0.1'): Request
    {
        $server = ['REMOTE_ADDR' => $address, 'CONTENT_TYPE' => 'application/json'];
        foreach ($headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));
            $server['CONTENT_TYPE' === $key ? $key : 'HTTP_' . $key] = $value;
        }

        $request = Request::create('/webhook', 'POST', [], [], [], $server, $body);
        if (str_starts_with((string) $request->headers->get('Content-Type'), 'application/x-www-form-urlencoded')) {
            parse_str($body, $fields);
            $request->request->replace($fields);
        }

        return $request;
    }

    private function fixture(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/../fixtures/' . $name);
    }
}
