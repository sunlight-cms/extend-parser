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
    private $missingHints;

    /** @var array */
    private $matchedHints;

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
     * Get missing hints in the last normalize call
     *
     * @return string[]
     */
    function getMissingHints(): array
    {
        return array_keys($this->missingHints);
    }

    /**
     * Get hints that were not used in the last normalize call
     *
     * @return string[]
     */
    function getUnusedHints(): array
    {
        return array_keys(array_diff_key($this->hints, $this->matchedHints));
    }

    /**
     * @param ExtendCall[] $extendCalls
     * @return ExtendCall[]
     */
    function normalize(array $extendCalls): array
    {
        $this->missingHints = [];
        $this->matchedHints = [];

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
        if (!isset($extendCall->file, $extendCall->context)) {
            return;
        }

        $hintsKey = $this->getHintsKey($extendCall);

        if (isset($this->hints[$hintsKey])) {
            $extendCall->event = $this->hints[$hintsKey];
            $this->matchedHints[$hintsKey] = true;
        } elseif ($extendCall->event === null) {
            $this->missingHints[$hintsKey] = true;
        }
    }

    private function sortExtends(ExtendCall $a, ExtendCall $b): int
    {
        return strnatcmp($a->event ?? '', $b->event ?? '');
    }
}
