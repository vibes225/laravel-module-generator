<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Manifest;

enum ManifestStatus: string
{
    /** Génération en cours ou interrompue : fichiers possiblement partiels. */
    case Pending = 'pending';

    case Complete = 'complete';
}
