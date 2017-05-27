<?php

namespace Sunlight\ExtendParser;

class ExtendCall
{
    /** @var string|null */
    public $file;
    /** @var int */
    public $line;
    /** @var string */
    public $event;
    /** @var ExtendArguments */
    public $arguments;
    /** @var string */
    public $method;
}
