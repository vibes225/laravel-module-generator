<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Definition;

/** Choix de pivot d'une relation belongsToMany. */
enum PivotMode: string
{
    /** Méthode seule, aucune table pivot générée. */
    case None = 'none';

    /** Migration de la table pivot appartenant au module. */
    case Generate = 'generate';

    /** Utilise un module pivot existant (`using()` + champs pivot). */
    case Module = 'module';
}
