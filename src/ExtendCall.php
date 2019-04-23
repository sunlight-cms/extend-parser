<?php declare(strict_types=1);

namespace Sunlight\ExtendParser;

class ExtendCall
{
    /** @var string|null */
    public $file;

    /** @var int */
    public $line;

    /** @var string|null */
    public $event;

    /** @var ExtendArguments */
    public $arguments;

    /** @var string */
    public $method;

    /** @var string|null */
    public $context;
}
