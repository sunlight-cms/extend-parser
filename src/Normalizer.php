<?php

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

    /**
     * @param string|null $basePath
     */
    public function setBasePath($basePath)
    {
        if ($basePath === null) {
            $this->basePath = null;
            $this->basePathLength = null;
        } else {
            $this->basePath = realpath($basePath);
            $this->basePathLength = strlen($this->basePath);
        }
    }

    /**
     * @param array|null $hints
     */
    public function setHints(array $hints = null)
    {
        $this->hints = $hints;
    }

    /**
     * Get unmatched hints since the last normalize call
     *
     * @return string[]
     */
    public function getUnmatchedHints()
    {
        return array_keys($this->unmatchedHints);
    }

    /**
     * @param ExtendCall[] $extendCalls
     * @return ExtendCall[]
     */
    public function normalize(array $extendCalls)
    {
        $this->unmatchedHints = array();

        foreach ($extendCalls as $extendCall) {
            $this->normalizeFile($extendCall);
            $this->normalizeEvent($extendCall);
        }

        usort($extendCalls, [$this, 'sortExtends']);

        return $extendCalls;
    }

    private function normalizeFile(ExtendCall $extendCall)
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

    private function normalizeEvent(ExtendCall $extendCall)
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

    /**
     * @param ExtendCall $extendCall
     * @return string
     */
    private function getHintsKey(ExtendCall $extendCall)
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
                : sizeof($extendCall->arguments->arguments),
            $context
        );
    }

    /**
     * @param ExtendCall $a
     * @param ExtendCall $b
     * @return int
     */
    private function sortExtends(ExtendCall $a, ExtendCall $b)
    {
        return strnatcmp($a->event, $b->event);
    }
}
