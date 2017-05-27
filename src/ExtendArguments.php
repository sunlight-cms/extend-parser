<?php

namespace Sunlight\ExtendParser;

class ExtendArguments
{
    /** @var ExtendArgument[] */
    public $arguments;
    /** @var bool */
    public $isDynamic = false;
    /** @var string|null */
    public $variableName;
}
