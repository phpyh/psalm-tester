<?php

declare(strict_types=1);

namespace PHPyh\PsalmTester;

use Composer\InstalledVersions;
use PHPUnit\Framework\Assert;

/**
 * @api
 */
final class PsalmTester
{
    private function __construct(
        private readonly string $psalmPath,
        private readonly string $defaultArguments,
        private readonly string $temporaryDirectory,
    ) {}

    public static function create(
        ?string $psalmPath = null,
        string $defaultArguments = '--no-progress --no-diff --config=' . __DIR__ . '/psalm.xml',
        ?string $temporaryDirectory = null,
    ): self {
        return new self(
            psalmPath: $psalmPath ?? self::findPsalm(),
            defaultArguments: $defaultArguments,
            temporaryDirectory: self::resolveTemporaryDirectory($temporaryDirectory),
        );
    }

    private static function findPsalm(): string
    {
        if (!method_exists(InstalledVersions::class, 'getInstallPath')) {
            throw new \RuntimeException('Cannot find Psalm installation path. Please, explicitly specify path to Psalm binary.');
        }

        $installPath = InstalledVersions::getInstallPath('vimeo/psalm');

        if ($installPath === null) {
            throw new \RuntimeException('Cannot find Psalm installation path. Please, explicitly specify path to Psalm binary.');
        }

        return $installPath . '/psalm';
    }

    private static function resolveTemporaryDirectory(?string $temporaryDirectory): string
    {
        $temporaryDirectory ??= sys_get_temp_dir() . '/psalm_test';

        if (!is_dir($temporaryDirectory) && !mkdir($temporaryDirectory, recursive: true)) {
            throw new \RuntimeException(sprintf('Failed to create temporary directory %s.', $temporaryDirectory));
        }

        return $temporaryDirectory;
    }

    public function test(PsalmTest $test): void
    {
        $codeFile = $this->createTemporaryCodeFile($test->code);

        try {
            $command = sprintf(
                '%s --output-format=json %s %s',
                $this->psalmPath,
                $test->arguments ?: $this->defaultArguments,
                $codeFile,
            );

            /** @psalm-suppress ForbiddenCode */
            $output = shell_exec($command);

            if (!\is_string($output)) {
                throw new \RuntimeException(sprintf('Failed to run command %s.', $command));
            }

            $formattedOutput = $this->formatOutput($output, $test->codeFirstLine);

            Assert::assertThat($formattedOutput, $test->constraint);
        } finally {
            @unlink($codeFile);
        }
    }

    private function createTemporaryCodeFile(string $contents): string
    {
        $file = tempnam($this->temporaryDirectory, 'code_');

        if ($file === false) {
            throw new \LogicException(sprintf('Failed to create temporary code file in %s.', $this->temporaryDirectory));
        }

        file_put_contents($file, $contents);

        return $file;
    }

    private function formatOutput(string $output, int $codeFirstLine): string
    {
        /** @var list<array{type: string, column_from: int, line_from: int, message: string, ...}> */
        $decoded = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        return implode("\n", array_map(
            static fn(array $error): string => sprintf(
                '%s on line %d: %s',
                $error['type'],
                $error['line_from'] + $codeFirstLine - 1,
                $error['message'],
            ),
            $decoded,
        ));
    }
}
