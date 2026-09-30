<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Generation;

final readonly class PlannedFile
{
    /** @param  string  $path  relatif à la racine du projet, séparateurs `/` */
    public function __construct(
        public string $path,
        public string $content,
        public string $kind,
        public string $label,
    ) {}

    /** @return array{path: string, kind: string, label: string, content: string} */
    public function toArray(): array
    {
        return ['path' => $this->path, 'kind' => $this->kind, 'label' => $this->label, 'content' => $this->content];
    }
}
