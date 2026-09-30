<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation\Formatting;

final class NullFormatter implements Formatter
{
    public function format(string $basePath, array $paths): array
    {
        return [];
    }
}
