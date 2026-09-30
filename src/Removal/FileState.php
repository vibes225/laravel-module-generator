<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Removal;

enum FileState: string
{
    case Unchanged = 'unchanged';

    /** Modifié depuis la génération : suppression seulement sur confirmation explicite. */
    case Modified = 'modified';

    /** Absent : ignoré et signalé. */
    case Missing = 'missing';

    /** Manifest pending (génération interrompue) : pas d'empreinte, fichier issu de la génération. */
    case Unknown = 'unknown';
}
