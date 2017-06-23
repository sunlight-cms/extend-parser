<?php

namespace Sunlight\ExtendParser;

class Cli
{
    /** @var string[] */
    private $argv;
    /** @var int */
    private $argc;
    /** @var ExtendParser */
    private $parser;

    /**
     * @param string[] $argv
     * @param int       $argc
     */
    public function __construct(array $argv, $argc)
    {
        $this->argv = $argv;
        $this->argc = $argc;
        $this->parser = new ExtendParser();
    }

    /**
     * @return int
     */
    public function run()
    {
        if ($this->argc !== 2) {
            $this->printUsage();

            return 1;
        }

        $path = $this->argv[1];

        if (!file_exists($path)) {
            return $this->fail("{$path} does not exist");
        }

        if (is_dir($path)) {
            $extends = $this->parseExtendsInDirectory($path);
        } else {
            $extends = $this->parseExtendsInFile(new \SplFileInfo($path));
        }

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
