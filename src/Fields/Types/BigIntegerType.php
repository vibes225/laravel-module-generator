<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Fields\Types;

use Amon\ModuleGenerator\Fields\IntegerLike;

final class BigIntegerType extends IntegerLike
{
    protected string $name = 'bigInteger';

    protected string $label = 'Grand entier';
}
