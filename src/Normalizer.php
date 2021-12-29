<?php declare(strict_types=1);

namespace Sunlight\ExtendParser;

class Normalizer
{
    /** @var string|null */
    private $basePath;

    /** @var int|null */
    private $basePathLength;

    /** @var array|null */
    private $hints;

    /** @var array */
    private $unmatchedHints;

    function setBasePath(?string $basePath): void
    {
        if ($basePath === null) {
            $this->basePath = null;
            $this->basePathLength = null;
        } else {
            $this->basePath = realpath($basePath);
            $this->basePathLength = strlen($this->basePath);
        }
    }

    function setHints(?array $hints = null): void
    {
        $this->hints = $hints;
    }

    function getHintsKey(ExtendCall $extendCall): string
    {
        if (strpos($extendCall->context, '::') === false) {
            $context = basename($extendCall->file) . '/' . $extendCall->context;
        } else {
            $context = $extendCall->context;
        }

        return sprintf(
            '%s(%s)@%s',
            $extendCall->method,
            $extendCall->arguments->isDynamic ?
                '*' . $extendCall->arguments->variableName
                : count($extendCall->arguments->arguments),
            $context
        );
    }

    /**
     * Get unmatched hints since the last normalize call
     *
     * @return string[]
     */
    function getUnmatchedHints(): array
    {
        return array_keys($this->unmatchedHints);
    }

    /**
     * @param ExtendCall[] $extendCalls
     * @return ExtendCall[]
     */
    function normalize(array $extendCalls): array
    {
        $this->unmatchedHints = array();

        foreach ($extendCalls as $extendCall) {
            $this->normalizeFile($extendCall);
            $this->normalizeEvent($extendCall);
        }

        usort($extendCalls, [$this, 'sortExtends']);

        return $extendCalls;
    }

    private function normalizeFile(ExtendCall $extendCall): void
    {
        if ($extendCall->file === null) {
            return;
        }

        if ($this->basePath !== null) {
            $realFilePath = realpath($extendCall->file);

            if (strncmp($realFilePath, $this->basePath, $this->basePathLength) === 0) {
                $extendCall->file = substr($realFilePath, $this->basePathLength);
            }
        }

        $extendCall->file = str_replace(DIRECTORY_SEPARATOR, '/', $extendCall->file);
    }

    private function normalizeEvent(ExtendCall $extendCall): void
    {
        if ($extendCall->event !== null || !isset($extendCall->file, $extendCall->context)) {
            return;
        }

        $hintsKey = $this->getHintsKey($extendCall);

        if (isset($this->hints[$hintsKey])) {
            $extendCall->event = $this->hints[$hintsKey];
        } else {
            $this->unmatchedHints[$hintsKey] = true;
        }
    }

    private function sortExtends(ExtendCall $a, ExtendCall $b): int
    {
        return strnatcmp($a->event, $b->event);
    }
}
