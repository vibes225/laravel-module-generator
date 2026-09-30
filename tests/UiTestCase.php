<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Tests;

/** Interface web activée (elle ne l'est par défaut qu'en environnement local). */
abstract class UiTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('module-generator.ui.enabled', true);
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
    }
}
