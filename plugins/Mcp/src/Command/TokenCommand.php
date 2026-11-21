<?php

namespace Playbloom\Plugin\Mcp\Command;

use Playbloom\Plugin\Mcp\McpServerBundle;
use Playbloom\Plugin\Mcp\Token\TokenStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Issues, lists and revokes the MCP server's tokens, the same ones as
 * /admin/mcp-tokens. Run it as the web server's user, so that the files it
 * writes stay writable by the web server.
 */
#[AsCommand(name: 'satisfy:mcp-token', description: 'Issues, lists and revokes the tokens an MCP client sends to /admin/mcp')]
final class TokenCommand extends Command
{
    public function __construct(private readonly TokenStore $tokens)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'issue, list or revoke')
            ->addArgument('name', InputArgument::OPTIONAL, 'The token\'s name, such as the client it is for')
            ->addOption('write', null, InputOption::VALUE_NONE, 'With issue: the token may change Satisfy, not only read it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        switch ($input->getArgument('action')) {
            case 'issue':
                $scope = $input->getOption('write') ? TokenStore::WRITE : TokenStore::READ;
                $token = $this->tokens->issue($name, $scope);
                $output->writeln(sprintf('Issued "%s", %s scope. It is shown once; only its hash is kept.', $name, $scope));
                $output->writeln('');
                $output->writeln($token);
                $output->writeln('');
                $output->writeln(sprintf('An MCP client sends it to %s as "Authorization: Bearer <token>".', McpServerBundle::PATH));

                return Command::SUCCESS;

            case 'revoke':
                if (!$this->tokens->revoke($name)) {
                    $output->writeln(sprintf('<error>No token is named "%s".</error>', $name));

                    return Command::FAILURE;
                }
                $output->writeln(sprintf('Revoked "%s".', $name));

                return Command::SUCCESS;

            case 'list':
                foreach ($this->tokens->all() as $tokenName => $token) {
                    $output->writeln(sprintf(
                        '%-24s %-5s issued %s, last used %s',
                        $tokenName,
                        $token['scope'],
                        date('Y-m-d H:i', $token['created']),
                        null === $token['last_used'] ? 'never' : date('Y-m-d H:i', $token['last_used']),
                    ));
                }

                return Command::SUCCESS;
        }

        $output->writeln('<error>The action is issue, list or revoke.</error>');

        return Command::INVALID;
    }
}
