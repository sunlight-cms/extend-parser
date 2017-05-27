<?php

namespace Sunlight\ExtendParser;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class ExtendParser
{
    /** @var Parser */
    private $phpParser;
    /** @var NodeTraverser */
    private $traverser;
    /** @var ExtendCallVisitor */
    private $extendCallVisitor;

    public function __construct()
    {
        $parserFactory = new ParserFactory();
        $this->phpParser = $parserFactory->create(ParserFactory::ONLY_PHP5);
        $this->extendCallVisitor = new ExtendCallVisitor();
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new NameResolver());
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

        $e = null;
        try {
            $this->traverser->traverse(
                $this->phpParser->parse($code)
            );
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        $extendCalls = $this->extendCallVisitor->finalize();

        if (null !== $e) {
            throw $e;
        }

        return $extendCalls;
    }
}
