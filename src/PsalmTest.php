<?php

declare(strict_types=1);

namespace PHPyh\PsalmTester;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\Constraint\StringMatchesFormatDescription;

/**
 * @api
 * @psalm-immutable
 * @psalm-type PhptSections = array<non-empty-string, array{string, positive-int}>
 */
final class PsalmTest
{
    private const FILE = 'FILE';
    private const ARGS = 'ARGS';
    private const EXPECT = 'EXPECT';
    private const EXPECTF = 'EXPECTF';
    private const EXPECT_EXTERNAL = 'EXPECT_EXTERNAL';
    private const EXPECTF_EXTERNAL = 'EXPECTF_EXTERNAL';

    /**
     * @param positive-int $codeFirstLine
     */
    public function __construct(
        public readonly string $code,
        public readonly Constraint $constraint,
        public readonly string $arguments = '',
        public readonly int $codeFirstLine = 1,
    ) {}

    /**
     * @see https://qa.php.net/phpt_details.php
     */
    public static function fromPhptFile(string $phptFile): self
    {
        $sections = self::parsePhpt($phptFile);

        if (!isset($sections[self::FILE])) {
            throw new \LogicException(sprintf('File %s must have a FILE section.', $phptFile));
        }

        return new self(
            code: $sections[self::FILE][0],
            constraint: self::resolvePhptConstraint($phptFile, $sections),
            arguments: $sections[self::ARGS][0] ?? '',
            codeFirstLine: $sections[self::FILE][1],
        );
    }

    /**
     * @param PhptSections $sections
     */
    private static function resolvePhptConstraint(string $file, array $sections): Constraint
    {
        if (isset($sections[self::EXPECT])) {
            return new IsIdentical($sections[self::EXPECT][0]);
        }

        if (isset($sections[self::EXPECTF])) {
            return new StringMatchesFormatDescription($sections[self::EXPECTF][0]);
        }

        if (isset($sections[self::EXPECT_EXTERNAL])) {
            return new IsIdentical(file_get_contents($sections[self::EXPECT_EXTERNAL][0]));
        }

        if (isset($sections[self::EXPECTF_EXTERNAL])) {
            return new StringMatchesFormatDescription(file_get_contents($sections[self::EXPECTF_EXTERNAL][0]));
        }

        throw new \LogicException(sprintf('File %s must have an EXPECT* section.', $file));
    }

    /**
     * @return PhptSections
     */
    private static function parsePhpt(string $phptFile): array
    {
        $name = null;
        $sections = [];
        $lineNumber = 0;

        foreach (file($phptFile, FILE_IGNORE_NEW_LINES) as $line) {
            ++$lineNumber;

            if (preg_match('/^--([_A-Z]+)--/', $line, $matches)) {
                /** @var non-empty-string */
                $name = $matches[1];

                if (!\defined(sprintf('%s::%s', self::class, $name))) {
                    throw new \InvalidArgumentException(sprintf('Section %s is not supported.', $name));
                }

                $sections[$name] = ['', $lineNumber + 1];

                continue;
            }

            if ($name === null) {
                throw new \LogicException('.phpt file must start with a section delimiter, f.e. --TEST--.');
            }

            $sections[$name][0] .= ($sections[$name][0] ? "\n" : '') . $line;
        }

        /** @var PhptSections */
        return $sections;
    }
}
