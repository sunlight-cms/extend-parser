<?php

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

    public function __construct()
    {
        $parserFactory = new ParserFactory();
        $this->currentFunctionResolver = new CurrentFunctionResolver();

        $this->phpParser = $parserFactory->create(ParserFactory::ONLY_PHP5);
        $this->extendCallVisitor = new ExtendCallVisitor($this->currentFunctionResolver);
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor($this->currentFunctionResolver);
        $this->traverser->addVisitor($this->extendCallVisitor);
    }

    /**
     * @param string      $code
     * @param string|null $file
     * @return ExtendCall[]
     */
    public function parse($code, $file = null)
    {
        $this->extendCallVisitor->setFile($file);
        $this->currentFunctionResolver->reset();

        $e = null;
        try {
            $this->traverser->traverse(
                $this->phpParser->parse($code)
            );
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        $extendCalls = $this->extendCallVisitor->finalize();

        if ($e !== null) {
            throw $e;
        }

        return $extendCalls;
    }
}