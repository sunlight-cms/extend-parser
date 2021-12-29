<?php declare(strict_types=1);

namespace Sunlight\ExtendParser;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser as PhpParser;
use PhpParser\ParserFactory;

class Parser
{
    /** @var PhpParser */
    private $phpParser;

    /** @var NodeTraverser */
    private $traverser;

    /** @var ExtendCallVisitor */
    private $extendCallVisitor;

    /** @var CurrentFunctionResolver */
    private $currentFunctionResolver;

    function __construct()
    {
        $parserFactory = new ParserFactory();
        $this->currentFunctionResolver = new CurrentFunctionResolver();

        $this->phpParser = $parserFactory->create(ParserFactory::PREFER_PHP7);
        $this->extendCallVisitor = new ExtendCallVisitor($this->currentFunctionResolver);
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor($this->currentFunctionResolver);
        $this->traverser->addVisitor($this->extendCallVisitor);
    }

    /**
     * @return ExtendCall[]
     */
    function parse(string $code, ?string $file = null): array
    {
        $this->extendCallVisitor->setFile($file);
        $this->currentFunctionResolver->reset();

        try {
            $this->traverser->traverse(
                $this->phpParser->parse($code)
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('Error while parsing %s', $file ?? '<no file>'), 0, $e);
        } finally {
            $extendCalls = $this->extendCallVisitor->finalize();
        }

        return $extendCalls;
    }
}
