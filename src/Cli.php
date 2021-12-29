<?php declare(strict_types=1);

namespace Sunlight\ExtendParser;

class Cli
{
    const DEFAULT_HINTS_FILE = __DIR__ . '/Resources/hints.json';

    /** @var Extractor */
    private $extractor;

    /** @var Normalizer */
    private $normalizer;

    function __construct()
    {
        $this->extractor = new Extractor(new Parser());
        $this->normalizer = new Normalizer();
    }

    function run(): void
    {
        global $argc, $argv;
    
        if ($argc < 2 || $argc > 3) {
            $this->printUsage();

            return;
        }

        $path = $argv[1];
        $hintsFile = $argv[2] ?? static::DEFAULT_HINTS_FILE;

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

    private function loadHints(string $hintsFile): void
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
    private function verify(array $extendCalls): void
    {
        foreach ($extendCalls as $index => $extendCall) {
            if ($extendCall->file === null) {
                $this->warn('Extend #%d has NULL file (this should not happen)', $index);
            }

            if ($extendCall->event === null) {
                $this->warn(
                    'Extend #%d at %s:%d has NULL event, missing hint?',
                    $index,
                    $extendCall->file,
                    $extendCall->line
                );

                $this->notice(
                    'Extend #%d hit key is %s',
                    $index,
                    $this->normalizer->getHintsKey($extendCall)
                );
            }
        }

        foreach ($this->normalizer->getUnmatchedHints() as $unmatchedHint) {
            $this->warn('Unmatched hint "%s"', $unmatchedHint);
        }
    }

    private function printUsage(): void
    {
        echo "Usage: parse <directory|file> [hints-file]\n";
    }

    private function notice(string $message, ...$args): void
    {
        $this->printMessage('NOTICE', $message, ...$args);
    }

    private function warn(string $message, ...$args): void
    {
        $this->printMessage('WARN', $message, ...$args);
    }

    private function printMessage(string $type, string $message, ...$args): void
    {
        $this->writeToStdErr(
            '[',
            $type,
            '] ',
            $args ? vsprintf($message, $args) : $message,
            "\n"
        );
    }

    private function writeToStdErr(string ...$strings): void
    {
        foreach ($strings as $string) {
            fwrite(STDERR, $string);
        }
    }

    /**
     * @throws CliException
     */
    private function fail(string $message, ...$args): void
    {
        throw new CliException($args ? vsprintf($message, $args) : $message);
    }
}
