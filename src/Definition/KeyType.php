<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

/** Type de clé de la relation polymorphe. */
enum KeyType: string
{
    case Integer = 'int';
    case Uuid = 'uuid';
    case Ulid = 'ulid';
}
