<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

enum ModuleKind: string
{
    case Standard = 'standard';
    case Pivot = 'pivot';
}
