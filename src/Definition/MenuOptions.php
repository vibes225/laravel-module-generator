<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

final readonly class MenuOptions
{
    public function __construct(
        public bool $enabled,
        public string $group,
        public int $order,
        public ?string $permission,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self($data['enabled'], $data['group'], $data['order'], $data['permission']);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['enabled' => $this->enabled, 'group' => $this->group, 'order' => $this->order, 'permission' => $this->permission];
    }
}
