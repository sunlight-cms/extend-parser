<?php

namespace Sunlight\ExtendParser;

class Extractor
{
    /** @var Parser */
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param string $directory
     * @return ExtendCall[]
     */
    public function fromDirectory($directory)
    {
        $extends = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO)
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            if ($item->getExtension() === 'php' && !$this->isExcludedPath($path = $item->getPathname())) {
                foreach ($this->fromFile($path) as $extend) {
                    $extends[] = $extend;
                }
            }
        }

        return $extends;
    }

    /**
     * @param string $path $file
     * @return ExtendCall[]
     */
    public function fromFile($path)
    {
        return $this->parser->parse(file_get_contents($path), $path);
    }

    /**
     * @param string $path
     * @return bool
     */
    private function isExcludedPath($path)
    {
        return (bool) preg_match('{[\\\\/](?:vendor|plugins|cache|tmp)[\\\\/]}', $path);
    }
}
