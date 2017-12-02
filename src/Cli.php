<?php

namespace Sunlight\ExtendParser;

class Cli
{
    const DEFAULT_HINTS_FILE = __DIR__ . '/Resources/hints.json';

    /** @var Extractor */
    private $extractor;
    /** @var Normalizer */
    private $normalizer;

    public function __construct()
    {
        $this->extractor = new Extractor(new Parser());
        $this->normalizer = new Normalizer();
    }

    public function run()
    {
        global $argc, $argv;
    
        if ($argc < 2 || $argc > 3) {
            $this->printUsage();

            return;
        }

        $path = $argv[1];
        $hintsFile = isset($argv[2]) ? $argv[2] : static::DEFAULT_HINTS_FILE;

        if (!file_exists($path)) {
            $this->fail('Path "%s" does not exist', $path);
        }

        $this->loadHints($hintsFile);

        if (is_dir($path)) {
            $extends = $this->extractor->fromDirectory($path);
            $this->normalizer->setBasePath($path);
        } else {
            $extends = $this->extractor->fromFile($path);
            $this->normalizer->setBasePath(dirname($path));
        }

        $extends = $this->normalizer->normalize($extends);
        
        $this->verify($extends);

        echo json_encode($extends, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param string $hintsFile
     */
    private function loadHints($hintsFile)
    {
        if (!file_exists($hintsFile)) {
            $this->fail('Hints file "%s" does not exist', $hintsFile);
        }

        $hints = json_decode(file_get_contents($hintsFile), true);

        if (!is_array($hints)) {
            $this->fail('Failed to load hints file "%s"', $hintsFile);
        }

        $this->normalizer->setHints($hints);
    }

    /**
     * @param ExtendCall[] $extendCalls
     */
    private function verify(array $extendCalls)
    {
        foreach ($extendCalls as $index => $extendCall) {
            if ($extendCall->file === null) {
                $this->warn('Extend #%d has NULL file', $index);
            }

            if ($extendCall->event === null) {
                $this->warn('Extend #%d has NULL event, missing hint?', $index);
            }
        }

        foreach ($this->normalizer->getUnmatchedHints() as $unmatchedHint) {
            $this->warn('Unmatched hint "%s"', $unmatchedHint);
        }
    }

    private function printUsage()
    {
        echo "Usage: parse <directory|file> [hints-file]\n";
    }

    /**
     * @param string $message
     * @param mixed $args,...
     */
    private function warn($message, ...$args)
    {
        fwrite(STDERR, 'Warning: ');
        fwrite(STDERR, $args ? vsprintf($message, $args) : $message);
        fwrite(STDERR, "\n");
    }

    /**
     * @param string $message
     * @param mixed $args,...
     * @throws CliException
     */
    private function fail($message, ...$args)
    {
        throw new CliException($args ? vsprintf($message, $args) : $message);
    }
}
