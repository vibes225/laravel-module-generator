<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

/** Options d'un module `kind: pivot` : vrai module (modèle, migration, CRUD) reliant deux côtés. */
final readonly class PivotModule
{
    public function __construct(
        public PivotSide $left,
        public PivotSide $right,
        public bool $uniquePair,
        public bool $primaryId,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            PivotSide::fromArray($data['left']),
            PivotSide::fromArray($data['right']),
            $data['unique_pair'],
            $data['primary_id'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'left' => $this->left->toArray(),
            'right' => $this->right->toArray(),
            'unique_pair' => $this->uniquePair,
            'primary_id' => $this->primaryId,
        ];
    }
}
