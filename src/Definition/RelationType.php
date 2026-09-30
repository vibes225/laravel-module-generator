<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

enum RelationType: string
{
    case BelongsTo = 'belongsTo';
    case HasOne = 'hasOne';
    case HasMany = 'hasMany';
    case BelongsToMany = 'belongsToMany';
}
