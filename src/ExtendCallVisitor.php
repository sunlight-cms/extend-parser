<?php declare(strict_types=1);

namespace Sunlight\ExtendParser;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class ExtendCallVisitor extends NodeVisitorAbstract
{
    /** @var string */
    private static $extendFqcn = 'Sunlight\\Extend';

    /** @var array */
    private static $extendMethodMap = ['call' => 0, 'fetch' => 1, 'buffer' => 2];

    /** @var ExtendCall[] */
    private $extendCalls = [];

    /** @var string|null */
    private $file;

    /** @var CurrentFunctionResolver */
    private $currentFunctionResolver;

    function __construct(CurrentFunctionResolver $currentFunctionResolver)
    {
        $this->currentFunctionResolver = $currentFunctionResolver;
    }

    function leaveNode(Node $node)
    {
        if (
            $node instanceof StaticCall
            && $node->class instanceof FullyQualified
            && $node->class->toString() === static::$extendFqcn
            && is_string($node->name)
            && isset(static::$extendMethodMap[$node->name])
            && sizeof($node->args) >= 1
        ) {
            $extendCall = new ExtendCall();
            $extendCall->file = $this->file;
            $extendCall->line = $node->getLine();
            $extendCall->method = $node->name;
            $extendCall->context = $this->currentFunctionResolver->getCurrentFunction();

            // event name
            $extendCall->event = $this->parseStringLiteralOrConcatenation($node->args[0]->value);

            // arguments
            $extendCall->arguments = new ExtendArguments();
            $extendCall->arguments->arguments = $this->getImpliedExtendArguments($node->name);

            if (sizeof($node->args) >= 2) {
                $this->addExtendArgumentsFromArgumentNode($extendCall->arguments, $node->args[1]);
            }

            $this->extendCalls[] = $extendCall;
        }
    }

    function setFile(?string $file): void
    {
        $this->file = $file;
    }

    /**
     * @return ExtendCall[]
     */
    function finalize(): array
    {
        $extendCalls = $this->extendCalls;

        $this->file = null;
        $this->extendCalls = [];

        return $extendCalls;
    }

    /**
     * @return ExtendArgument[]
     */
    private function getImpliedExtendArguments(string $method): array
    {
        $arguments = [];

        if ($method === 'buffer') {
            $outputArgument = new ExtendArgument();
            $outputArgument->name = 'output';
            $outputArgument->isRef = true;

            $arguments[] = $outputArgument;
        } elseif ($method === 'fetch') {
            $valueArgument = new ExtendArgument();
            $valueArgument->name = 'value';
            $valueArgument->isRef = true;

            $arguments[] = $valueArgument;
        }

        return $arguments;
    }

    private function addExtendArgumentsFromArgumentNode(ExtendArguments $arguments, Arg $argumentsNode): void
    {
        if ($argumentsNode->value instanceof Array_) {
            // array literal
            foreach ($argumentsNode->value->items as $index => $arrayItem) {
                $argument = new ExtendArgument();

                if ($arrayItem->key !== null) {
                    $argument->name = $this->parseStringLiteralOrConcatenation($arrayItem->key);
                }

                if ($argument->name === null) {
                    $argument->name = "<unknown{$index}>";
                }

                $argument->isRef = $arrayItem->byRef;

                $arguments->arguments[] = $argument;
            }
        } else {
            // dynamic
            $arguments->isDynamic = true;

            if ($argumentsNode->value instanceof Variable && is_string($argumentsNode->value->name)) {
                // variable
                $arguments->variableName = $argumentsNode->value->name;
            }
        }
    }

    private function parseStringLiteralOrConcatenation(Node $node): ?string
    {
        if ($node instanceof String_) {
            // scalar name
            return $node->value;
        }

        if ($node instanceof Concat) {
            // concatenation
            return $this->parseStringConcatenation($node);
        }

        if ($node instanceof Encapsed) {
            // string interpolation
            $name = '';

            foreach ($node->parts as $part) {
                if ($part instanceof EncapsedStringPart) {
                    $name .= $part->value;
                } else {
                    $name .= '*';
                }
            }

            return $name;
        }

        // dynamic
        return null;
    }

    private function parseStringConcatenation(Concat $concat): string
    {
        $name = '';

        if ($concat->left instanceof String_) {
            $name .= $concat->left->value;
        } elseif ($concat->left instanceof Concat) {
            $name .= $this->parseStringConcatenation($concat->left);
        } else {
            $name .= '*';
        }

        if ($concat->right instanceof String_) {
            $name .= $concat->right->value;
        } elseif ($concat->right instanceof Concat) {
            $name .= $this->parseStringConcatenation($concat->right);
        } else {
            $name .= '*';
        }

        return $name;
    }
}
