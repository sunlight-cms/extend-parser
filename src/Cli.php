<?php

namespace Sunlight\ExtendParser;

class Cli
{
    /** @var ExtendParser */
    private $parser;

    public function __construct()
    {
        $this->parser = new ExtendParser();
    }

    /**
     * @return int
     */
    public function run()
    {
        global $argc, $argv;
    
        if ($argc !== 2) {
            $this->printUsage();

            return 1;
        }

        $path = $argv[1];

        if (!file_exists($path)) {
            return $this->fail("{$path} does not exist");
        }

        if (is_dir($path)) {
            $extends = $this->parseExtendsInDirectory($path);
            $baseDirectory = $path;
        } else {
            $extends = $this->parseExtendsInFile($path);
            $baseDirectory = dirname($path);
        }

        usort($extends, [$this, 'sortExtends']);
        $this->normalizeExtendPaths($extends, $baseDirectory);

        echo json_encode($extends, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return 0;
    }

    /**
     * @param string $directory
     * @return ExtendCall[]
     */
    private function parseExtendsInDirectory($directory)
    {
        $extends = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO)
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            if ($item->getExtension() === 'php' && !$this->isExcludedPath($path = $item->getPathname())) {
                foreach ($this->parseExtendsInFile($path) as $extend) {
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
    private function parseExtendsInFile($path)
    {
        return $this->parser->parse(file_get_contents($path), $path);
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

    /**
     * @param ExtendCall[] $extendCalls
     * @param string $baseDirectory
     */
    private function normalizeExtendPaths(array $extendCalls, $baseDirectory)
    {
        $baseDirectory = realpath($baseDirectory);
        $baseDirectoryLength = strlen($baseDirectory);

        foreach ($extendCalls as $extendCall) {
            if ($extendCall->file === null) {
                continue;
            }

            $realFilePath = realpath($extendCall->file);

            if (strncmp($realFilePath, $baseDirectory, $baseDirectoryLength) === 0) {
                $extendCall->file = substr($realFilePath, $baseDirectoryLength);
            }

            $extendCall->file = str_replace(DIRECTORY_SEPARATOR, '/', $extendCall->file);
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    private function isExcludedPath($path)
    {
        return (bool) preg_match('{[\\\\/](?:vendor|plugins|cache|tmp)[\\\\/]}', $path);
    }

    private function printUsage()
    {
        echo "Usage: parse <path-to-sunlight-root-directory|path-to-single-file>\n";
    }

    /**
     * @param string $message
     * @return int
     */
    private function fail($message)
    {
        fwrite(STDERR, "ERROR: {$message}\n");

        return 1;
    }
}
