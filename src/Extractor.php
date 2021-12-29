<?php declare(strict_types=1);

namespace Sunlight\ExtendParser;

class Extractor
{
    /** @var Parser */
    private $parser;

    function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @return ExtendCall[]
     */
    function fromDirectory(string $directory): array
    {
        $extends = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::CURRENT_AS_FILEINFO)
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
     * @return ExtendCall[]
     */
    function fromFile(string $path): array
    {
        return $this->parser->parse(file_get_contents($path), $path);
    }

    private function isExcludedPath(string $path): bool
    {
        return (bool) preg_match('{[\\\\/](?:vendor|plugins|cache|tmp)[\\\\/]}', $path);
    }
}
