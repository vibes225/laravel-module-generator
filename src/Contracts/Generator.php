<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Contracts;

use Amon\ModuleGenerator\Generation\GenerationContext;
use Amon\ModuleGenerator\Generation\PlannedFile;

/** Un générateur = une responsabilité. Pur : aucun accès au système de fichiers. */
interface Generator
{
    public function applies(GenerationContext $context): bool;

    /** @return list<PlannedFile> */
    public function generate(GenerationContext $context): array;
}
