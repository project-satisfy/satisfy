<?php

namespace Playbloom\Plugin\Hookgate;

use Symfony\Component\Process\Process;

/**
 * Runs HOOKGATE_CHECKER, if set, on a candidate satis.json before auto-require
 * writes it. The command gets SATIS_JSON (the candidate), SATIS_CHECK_SCOPE=file,
 * GITLAB_API_TOKEN, GITLAB_API_BASE and PACKAGE_TYPES_FILE in its environment,
 * which is what check-satis.php reads.
 *
 * Its exit status is read as rebuild scripts read it: 0 clean and 4 advisory
 * proceed; anything else refuses, because not knowing is not the same as clean.
 */
class Checker
{
    public function __construct(
        private readonly HookgateConfig $config,
        private readonly PackageTypes $types,
    ) {
    }

    /**
     * @return array{0: int, 1: string} exit status and report; [0, ''] when no checker is set
     */
    public function check(string $candidateJson, string $projectUrl): array
    {
        $command = $this->config->getChecker();
        if (null === $command) {
            return [0, ''];
        }

        $dir = sys_get_temp_dir() . '/hookgate-' . bin2hex(random_bytes(6));
        if (!@mkdir($dir, 0700)) {
            return [3, 'could not create a directory for the candidate satis.json'];
        }
        try {
            // Named satis.json so that the report says so.
            file_put_contents($dir . '/satis.json', $candidateJson);
            $this->types->writeFile($dir . '/package_types.php');
            $process = Process::fromShellCommandline($command, null, [
                'SATIS_JSON' => $dir . '/satis.json',
                'SATIS_CHECK_SCOPE' => 'file',
                'GITLAB_API_TOKEN' => (string) $this->config->getApiToken(),
                'GITLAB_API_BASE' => (string) $this->config->getApiBase($projectUrl),
                'PACKAGE_TYPES_FILE' => $dir . '/package_types.php',
            ], null, 300);
            $process->run();

            return [(int) $process->getExitCode(), $process->getOutput()];
        } finally {
            @unlink($dir . '/satis.json');
            @unlink($dir . '/package_types.php');
            @rmdir($dir);
        }
    }
}
