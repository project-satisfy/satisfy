<?php

namespace Playbloom\Tests\Plugin\Mcp;

use Playbloom\Plugin\Mcp\Command\TokenCommand;
use Playbloom\Plugin\Mcp\Token\TokenStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tokens issued and revoked from the command line and from /admin/mcp-tokens,
 * which keep the same store.
 */
class TokenAdminTest extends McpTestCase
{
    public function testTheCommandIssuesListsAndRevokes(): void
    {
        $store = new TokenStore($this->dir . '/mcp');
        $tester = new CommandTester(new TokenCommand($store));

        $this->assertSame(Command::SUCCESS, $tester->execute(['action' => 'issue', 'name' => 'cli', '--write' => true]));
        $this->assertMatchesRegularExpression('/^satisfy_mcp_[0-9a-f]{48}$/m', $tester->getDisplay());
        preg_match('/^(satisfy_mcp_[0-9a-f]{48})$/m', $tester->getDisplay(), $match);
        $this->assertSame(['name' => 'cli', 'scope' => 'write'], $store->find($match[1]));

        $this->assertSame(Command::SUCCESS, $tester->execute(['action' => 'list']));
        $this->assertMatchesRegularExpression('/^cli +write +issued/m', $tester->getDisplay());

        $this->assertSame(Command::SUCCESS, $tester->execute(['action' => 'revoke', 'name' => 'cli']));
        $this->assertSame(Command::FAILURE, $tester->execute(['action' => 'revoke', 'name' => 'cli']));
        $this->assertNull($store->find($match[1]));
    }

    public function testThePageIssuesATokenShownOnceAndRevokesIt(): void
    {
        $client = $this->client();
        $crawler = $client->request('GET', '/admin/mcp-tokens');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('reader', $crawler->filter('table')->text());

        $client->submit($crawler->selectButton('Issue')->form(['name' => 'page', 'scope' => 'write']));
        $this->assertResponseRedirects('/admin/mcp-tokens');
        $crawler = $client->followRedirect();
        $token = trim($crawler->filter('.secret pre')->first()->text());
        $this->assertSame(['name' => 'page', 'scope' => 'write'], (new TokenStore($this->dir . '/mcp'))->find($token));

        $crawler = $client->request('GET', '/admin/mcp-tokens');
        $this->assertCount(0, $crawler->filter('.secret'), 'The token was shown a second time');

        $client->submit($crawler->filter('form.inline-form')->reduce(static fn ($form): bool => str_contains($form->html(), 'value="page"'))->selectButton('Revoke')->form());
        $this->assertStringContainsString('Revoked page', $client->followRedirect()->filter('.alert, .flash, [role=status]')->text(''));
        $this->assertNull((new TokenStore($this->dir . '/mcp'))->find($token));
    }

    public function testAForgedPostChangesNothing(): void
    {
        $client = $this->client();
        $client->request('POST', '/admin/mcp-tokens', ['action' => 'revoke', 'name' => 'writer', '_token' => 'forged']);

        $this->assertResponseRedirects('/admin/mcp-tokens');
        $this->assertNotNull((new TokenStore($this->dir . '/mcp'))->find($this->writeToken));
    }
}
