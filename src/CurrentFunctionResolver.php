<?php

namespace Sunlight\ExtendParser;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;

class CurrentFunctionResolver extends NodeVisitorAbstract
{
    /** @var Node[] */
    private $context;

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Function_
            || $node instanceof ClassMethod
            || $node instanceof Class_
            || $node instanceof Trait_
        ) {
            $this->context[] = $node;
        }
    }

    public function leaveNode(Node $node)
    {
        if (empty($this->context)) {
            return;
        }

        if ($this->context[sizeof($this->context) - 1] === $node) {
            array_pop($this->context);
        }
    }

    /**
     * @return string|null
     */
    public function getCurrentFunction()
    {
        /** @var Function_|ClassMethod|null $function */
        $function = null;
        /** @var Class_|Trait_|null $class */
        $class = null;

        for ($i = sizeof($this->context) - 1; $i >= 0; --$i) {
            $node = $this->context[$i];

            if ($function === null) {
                if ($node instanceof FunctionLike) {
                    $function = $node;
                }

                continue;
            }

            if ($node instanceof Class_ || $node instanceof Trait_) {
                $class = $node;
                break;
            }
        }

        if ($function instanceof Function_) {
            return $function->name;
        }

        if ($function instanceof ClassMethod && $class instanceof ClassLike) {
            return $class->namespacedName . '::' . $function->name;
        }

        return null;
    }

    public function reset()
    {
        $this->context = [];
    }
}
