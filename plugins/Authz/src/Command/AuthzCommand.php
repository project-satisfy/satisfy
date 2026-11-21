<?php

namespace Playbloom\Plugin\Authz\Command;

use Playbloom\Plugin\Authz\Audit;
use Playbloom\Plugin\Authz\AuthzConfig;
use Playbloom\Plugin\Authz\Cache;
use Playbloom\Plugin\Authz\Database;
use Playbloom\Plugin\Authz\Deps;
use Playbloom\Plugin\Authz\Entitlement;
use Playbloom\Plugin\Authz\Sites;
use Playbloom\Plugin\Authz\Token;
use Playbloom\Service\Manager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * authz's administration, as one command with authzctl's subcommands, so that
 * anything written against authzctl changes only its entry point.
 */
#[AsCommand(name: 'authz', description: 'Manage site tokens and package grants for the catalogue')]
class AuthzCommand extends Command
{
    private const USAGE = <<<'TXT'
        Sites
          init                      Create or upgrade the database (idempotent).
          issue <site> [note]       Issue a token. Prints it once.
          reissue <site>            Replace a site's token.
          revoke <site>             Deactivate a site.
          restore <site>            Reactivate a revoked site.
          forget <site>             Delete a site, its grants and its requests.
          list                      Show every site.
          note <site> <text>        Set a site's note.

        Credential enforcement (every site)
          enforce status|on|off     Refuse requests without a valid token.

        Package grants (per site)
          grant <site> <pkg>...     Allow packages. 'vendor/*' allows a vendor.
          ungrant <site> <pkg>...   Remove grants.
          entitlements <site>       Show the grants and everything they reach.
          pkg-enforce <site> on|off Refuse (on) or only record (off) other packages.

        Approval queue
          requests [site]           Packages sites asked for and were not granted.
          approve <site> <pkg>...   Grant and resolve the request.
          dismiss <site> <pkg>...   Resolve without granting.

        Diagnostics
          check <site> <token>      Verify a credential.
          packages                  The packages the catalogue hosts.
          deps <pkg>                A package's hosted dependencies.
          audit [n]                 The end of the audit log.
          unauth [n]                Requests without a valid token.
          stats                     Summary.
          selftest                  Check hashing, the database, APCu and the catalogue.
        TXT;

    public function __construct(
        private readonly AuthzConfig $config,
        private readonly Database $database,
        private readonly Sites $sites,
        private readonly Token $token,
        private readonly Entitlement $entitlement,
        private readonly Deps $deps,
        private readonly Audit $audit,
        private readonly Manager $manager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::OPTIONAL, 'What to do; run without one for the list')
            ->addArgument('arguments', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The action\'s arguments')
            ->setHelp(self::USAGE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = (string) $input->getArgument('action');
        $arguments = array_values((array) $input->getArgument('arguments'));

        try {
            return match ($action) {
                'init' => $this->init($output),
                'issue', 'reissue' => $this->issue($action, $arguments, $output),
                'revoke', 'restore' => $this->setActive($action, $arguments, $output),
                'forget' => $this->forget($arguments, $output),
                'note' => $this->note($arguments, $output),
                'list' => $this->listSites($output),
                'enforce' => $this->enforce($arguments, $output),
                'grant', 'approve' => $this->grant($action, $arguments, $output),
                'ungrant' => $this->ungrant($arguments, $output),
                'entitlements' => $this->entitlements($arguments, $output),
                'pkg-enforce' => $this->packageEnforce($arguments, $output),
                'requests' => $this->requests($arguments, $output),
                'dismiss' => $this->dismiss($arguments, $output),
                'check' => $this->check($arguments, $output),
                'packages' => $this->packages($output),
                'deps' => $this->dependencies($arguments, $output),
                'audit' => $this->auditTail($arguments, $output),
                'unauth' => $this->unauthenticated($arguments, $output),
                'stats' => $this->stats($output),
                'selftest' => $this->selftest($output),
                default => $this->usage($output),
            };
        } catch (\InvalidArgumentException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }

    private function usage(OutputInterface $output): int
    {
        $output->writeln(self::USAGE);

        return Command::INVALID;
    }

    private function init(OutputInterface $output): int
    {
        $this->config->ensureDataDir();
        $db = $this->database->open(true);
        $ran = $this->database->migrate($db);
        $db->close();
        $output->writeln([] === $ran ? 'The database is current.' : 'Applied: ' . implode(', ', $ran));

        return Command::SUCCESS;
    }

    private function issue(string $action, array $arguments, OutputInterface $output): int
    {
        $site = $this->required($arguments, 0, 'site');
        $exists = $this->sites->exists($site);
        if ('issue' === $action && $exists) {
            throw new \InvalidArgumentException(sprintf('Site "%s" already exists. Use reissue to replace its token.', $site));
        }
        if ('reissue' === $action && !$exists) {
            throw new \InvalidArgumentException(sprintf('Site "%s" does not exist. Use issue.', $site));
        }

        $token = $this->sites->issue($site, $arguments[1] ?? null);
        $this->audit->write(['event' => $action, 'site' => $site]);

        $auth = json_encode(['http-basic' => [$this->packageHost() => ['username' => $site, 'password' => $token]]], JSON_UNESCAPED_SLASHES);
        $output->writeln([
            '',
            '  site   ' . $site,
            '  token  ' . $token,
            '',
            '  Shown once. Only its hash is kept, so it cannot be shown again.',
            '',
            '  COMPOSER_AUTH, which is also the whole of an auth.json:',
            '',
            '    ' . $auth,
            '',
        ]);
        if ('reissue' === $action) {
            $output->writeln(['  The previous token stopped working at once.', '']);
        } else {
            $output->writeln(['  The site reaches no package until it is granted some:', '    bin/console authz grant ' . $site . ' <vendor/package>...', '']);
        }

        return Command::SUCCESS;
    }

    private function setActive(string $action, array $arguments, OutputInterface $output): int
    {
        $site = $this->required($arguments, 0, 'site');
        if (!$this->sites->setActive($site, 'restore' === $action)) {
            throw new \InvalidArgumentException('No such site: ' . $site);
        }
        $this->audit->write(['event' => $action, 'site' => $site]);
        $output->writeln(sprintf('%s %s. Effective within %ds, or at once after a purge.', 'restore' === $action ? 'Restored' : 'Revoked', $site, AuthzConfig::CACHE_TTL));

        return Command::SUCCESS;
    }

    private function forget(array $arguments, OutputInterface $output): int
    {
        $site = $this->required($arguments, 0, 'site');
        $this->sites->forget($site);
        $this->audit->write(['event' => 'forget', 'site' => $site]);
        $output->writeln(sprintf('Deleted %s, its grants and its requests.', $site));

        return Command::SUCCESS;
    }

    private function note(array $arguments, OutputInterface $output): int
    {
        $site = $this->required($arguments, 0, 'site');
        if (!$this->sites->setNote($site, implode(' ', array_slice($arguments, 1)))) {
            throw new \InvalidArgumentException('No such site: ' . $site);
        }
        $output->writeln('Note set for ' . $site . '.');

        return Command::SUCCESS;
    }

    private function listSites(OutputInterface $output): int
    {
        $sites = $this->sites->all();
        $output->writeln(sprintf('%-34s %-8s %-9s %-7s %-8s %s', 'SITE', 'STATE', 'PKG-MODE', 'GRANTS', 'PENDING', 'NOTE'));
        foreach ($sites as $row) {
            $output->writeln(sprintf(
                '%-34s %-8s %-9s %-7s %-8s %s',
                $row['name'],
                1 === (int) $row['active'] ? 'active' : 'REVOKED',
                1 === (int) $row['package_enforce'] ? 'enforce' : 'observe',
                (string) $row['grants'],
                (int) $row['pending'] > 0 ? $row['pending'] . ' !' : '-',
                (string) $row['note']
            ));
        }
        $output->writeln(['', count($sites) . ' site(s).']);

        return Command::SUCCESS;
    }

    private function enforce(array $arguments, OutputInterface $output): int
    {
        $value = $arguments[0] ?? 'status';
        if ('status' === $value) {
            $output->writeln('Enforcement: ' . ($this->config->isEnforcing()
                ? 'ON. Requests without a valid token are refused.'
                : 'OFF. Permissive: requests are recorded and allowed.'));

            return Command::SUCCESS;
        }
        if (!in_array($value, ['on', 'off'], true)) {
            return $this->usage($output);
        }
        $this->config->setEnforcing('on' === $value);
        $this->audit->write(['event' => 'enforce', 'value' => $value]);
        $output->writeln('Enforcement set to ' . $value . '.');

        return Command::SUCCESS;
    }

    private function grant(string $action, array $arguments, OutputInterface $output): int
    {
        $site = $this->required($arguments, 0, 'site');
        $packages = $this->packagesFrom($arguments);
        $db = $this->database->writable();
        foreach ($packages as $package) {
            $this->entitlement->grant($db, $site, $package, 'cli');
            $output->writeln('  granted ' . $package);
        }
        $reach = count($this->entitlement->effective($db, $site));
        $db->close();
        $this->audit->write(['event' => $action, 'site' => $site, 'packages' => $packages]);
        $output->writeln(['', sprintf('%s can now reach %d package(s), dependencies included.', $site, $reach)]);
        $output->writeln(sprintf('Effective within %ds, or at once after a purge.', AuthzConfig::CACHE_TTL));

        return Command::SUCCESS;
    }

    private function ungrant(array $arguments, OutputInterface $output): int
    {
        $site = $this->required($arguments, 0, 'site');
        $packages = $this->packagesFrom($arguments);
        $db = $this->database->writable();
        foreach ($packages as $package) {
            $removed = $this->entitlement->revoke($db, $site, $package);
            $output->writeln(($removed > 0 ? '  removed ' : '  not granted: ') . $package);
        }
        $db->close();
        $this->audit->write(['event' => 'ungrant', 'site' => $site, 'packages' => $packages]);

        return Command::SUCCESS;
    }

    private function entitlements(array $arguments, OutputInterface $output): int
    {
        $site = $this->required($arguments, 0, 'site');
        $db = $this->database->writable();
        $direct = $this->entitlement->granted($db, $site);
        $closure = $this->entitlement->effective($db, $site);
        $mode = $this->entitlement->enforcedFor($db, $site) ? 'enforce' : 'observe';
        $db->close();

        $implied = array_diff(array_keys($closure), array_map('strtolower', $direct));
        sort($implied);
        $output->writeln(['site        : ' . $site, 'package mode: ' . $mode, 'granted     : ' . count($direct)]);
        foreach ($direct as $package) {
            $output->writeln('  ' . $package);
        }
        $output->writeln('implied by dependencies: ' . count($implied));
        foreach ($implied as $package) {
            $output->writeln('  ' . $package);
        }
        $output->writeln(['', 'total reachable: ' . count($closure)]);

        return Command::SUCCESS;
    }

    private function packageEnforce(array $arguments, OutputInterface $output): int
    {
        $site = $this->required($arguments, 0, 'site');
        $value = $this->required($arguments, 1, 'on|off');
        if (!in_array($value, ['on', 'off'], true)) {
            return $this->usage($output);
        }
        $db = $this->database->writable();
        $changed = $this->entitlement->setEnforce($db, $site, 'on' === $value);
        $reach = count($this->entitlement->effective($db, $site));
        $db->close();
        if (0 === $changed) {
            throw new \InvalidArgumentException('No such site: ' . $site);
        }
        $this->audit->write(['event' => 'pkg-enforce', 'site' => $site, 'value' => $value]);
        $output->writeln(sprintf('Package enforcement for %s: %s.', $site, $value));
        if ('on' === $value) {
            $output->writeln(sprintf('It can reach %d package(s). Any other is now refused with 403.', $reach));
            if (0 === $reach) {
                $output->writeln('<comment>It has no grants, so every package request will fail.</comment>');
            }
        }

        return Command::SUCCESS;
    }

    private function requests(array $arguments, OutputInterface $output): int
    {
        $db = $this->database->writable();
        $requests = $this->entitlement->openRequests($db, $arguments[0] ?? null);
        $db->close();
        if ([] === $requests) {
            $output->writeln('No open package requests.');

            return Command::SUCCESS;
        }
        $output->writeln(sprintf('%-34s %-38s %s', 'SITE', 'PACKAGE', 'FIRST SEEN'));
        foreach ($requests as $request) {
            $output->writeln(sprintf('%-34s %-38s %s', $request['site'], $request['package'], date('Y-m-d H:i', (int) $request['first_seen'])));
        }
        $output->writeln(['', count($requests) . ' open request(s). Approve with:', '  bin/console authz approve <site> <package>']);

        return Command::SUCCESS;
    }

    private function dismiss(array $arguments, OutputInterface $output): int
    {
        $site = $this->required($arguments, 0, 'site');
        $db = $this->database->writable();
        foreach ($this->packagesFrom($arguments) as $package) {
            $this->entitlement->dismiss($db, $site, $package);
            $output->writeln('  dismissed ' . $package);
        }
        $db->close();

        return Command::SUCCESS;
    }

    private function check(array $arguments, OutputInterface $output): int
    {
        $site = $this->required($arguments, 0, 'site');
        $result = $this->sites->check($site, $this->required($arguments, 1, 'token'));
        if (!$result['exists']) {
            $output->writeln('FAIL: no such site: ' . $site);

            return Command::FAILURE;
        }
        $ok = $result['matches'] && $result['active'];
        $output->writeln([
            'token  : ' . ($result['matches'] ? 'MATCHES' : 'does NOT match'),
            'state  : ' . ($result['active'] ? 'active' : 'REVOKED'),
            'result : ' . ($ok ? 'WOULD AUTHENTICATE' : 'WOULD REFUSE'),
        ]);

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }

    private function packages(OutputInterface $output): int
    {
        $hosted = $this->deps->hosted();
        foreach ($hosted as $package) {
            $output->writeln('  ' . $package);
        }
        $output->writeln(['', count($hosted) . ' package(s) in the catalogue.']);

        return Command::SUCCESS;
    }

    private function dependencies(array $arguments, OutputInterface $output): int
    {
        $package = strtolower($this->required($arguments, 0, 'package'));
        $closure = $this->deps->closure([$package]);
        unset($closure[$package]);
        $names = array_keys($closure);
        sort($names);
        $output->writeln(sprintf('%s pulls in %d hosted package(s):', $package, count($names)));
        foreach ($names as $name) {
            $output->writeln('  ' . $name);
        }
        if ([] === $names) {
            $output->writeln('  (none, or the package is not in the catalogue)');
        }

        return Command::SUCCESS;
    }

    private function auditTail(array $arguments, OutputInterface $output): int
    {
        $output->writeln($this->audit->tail((int) ($arguments[0] ?? 40)));

        return Command::SUCCESS;
    }

    private function unauthenticated(array $arguments, OutputInterface $output): int
    {
        $hits = [];
        foreach ($this->audit->tail(100000) as $line) {
            $entry = json_decode($line, true);
            if (is_array($entry) && array_key_exists('ok', $entry) && false === $entry['ok']) {
                $hits[] = $line;
            }
        }
        $output->writeln(array_slice($hits, -(int) ($arguments[0] ?? 40)));
        $output->writeln(['', count($hits) . ' request(s) without a valid token.', 'This must reach zero before `enforce on`.']);

        return Command::SUCCESS;
    }

    private function stats(OutputInterface $output): int
    {
        $db = $this->database->writable();
        $count = static fn (string $sql): int => (int) $db->querySingle($sql);
        $lines = [
            'sites total      : ' . $count('SELECT COUNT(*) FROM sites'),
            'sites active     : ' . $count('SELECT COUNT(*) FROM sites WHERE active = 1'),
            'package-enforced : ' . $count('SELECT COUNT(*) FROM sites WHERE package_enforce = 1'),
            'grants           : ' . $count('SELECT COUNT(*) FROM entitlements'),
            'open requests    : ' . $count('SELECT COUNT(*) FROM package_requests WHERE resolved = 0'),
        ];
        $db->close();
        $summary = $this->audit->summary();
        $output->writeln(array_merge($lines, [
            'credential enforcement: ' . ($this->config->isEnforcing() ? 'ON' : 'OFF (permissive)'),
            'hosted packages  : ' . count($this->deps->hosted()),
            'unauthorized reqs: ' . $summary['unauth'],
            'sites observed   : ' . count($summary['observed']),
        ]));

        return Command::SUCCESS;
    }

    private function selftest(OutputInterface $output): int
    {
        $output->writeln('extensions');
        foreach (['sqlite3', 'apcu'] as $extension) {
            $output->writeln(sprintf('  %-10s %s', $extension, extension_loaded($extension) ? 'ok' : 'MISSING'));
        }
        $output->writeln('pepper');
        try {
            $this->config->getPepper();
            $output->writeln('  configured ok');
        } catch (\RuntimeException $exception) {
            $output->writeln('  MISSING: ' . $exception->getMessage());

            return Command::FAILURE;
        }
        $output->writeln('apcu round-trip');
        Cache::store('authz:selftest', 'v', 5);
        $output->writeln('  ' . ('v' === Cache::fetch('authz:selftest') ? 'ok' : 'not available to the command line (the web server has its own)'));
        $output->writeln('database');
        $db = $this->database->writable();
        $output->writeln('  sites: ' . (int) $db->querySingle('SELECT COUNT(*) FROM sites'));
        $db->close();
        $output->writeln('hash determinism');
        $token = Token::generate();
        $output->writeln('  ' . (hash_equals($this->token->hash($token), $this->token->hash($token)) ? 'ok' : 'FAILED'));
        $output->writeln('catalogue');
        $hosted = $this->deps->hosted();
        $output->writeln(sprintf('  %d hosted package(s)%s', count($hosted), [] === $hosted ? ', none: is ' . $this->config->getCatalogueDir() . ' the catalogue?' : ''));
        $output->writeln('audit log');
        $this->audit->write(['event' => 'selftest']);
        $output->writeln('  ' . (is_readable($this->config->getAuditPath()) ? 'writable ok' : 'NOT WRITABLE'));
        $output->writeln(['', 'All checks completed.']);

        return Command::SUCCESS;
    }

    private function required(array $arguments, int $index, string $name): string
    {
        $value = (string) ($arguments[$index] ?? '');
        if ('' === $value) {
            throw new \InvalidArgumentException('Missing required argument: ' . $name);
        }

        return $value;
    }

    /**
     * @return string[]
     */
    private function packagesFrom(array $arguments): array
    {
        $packages = array_slice($arguments, 1);
        if ([] === $packages) {
            throw new \InvalidArgumentException('Expected at least one package name.');
        }

        return $packages;
    }

    /**
     * The host Composer authenticates against: the catalogue's homepage.
     */
    private function packageHost(): string
    {
        $host = parse_url((string) $this->manager->getConfig()->getHomepage(), PHP_URL_HOST);

        return is_string($host) && '' !== $host ? $host : 'localhost';
    }
}
