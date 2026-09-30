<?php

use Amon\ModuleGenerator\Install\Installer;
use App\Models\Category;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Inertia\Testing\AssertableInertia;

/*
 * Le code généré est exécuté pour de vrai : projet bac à sable installé, modules générés,
 * classes chargées, migrations exécutées sur SQLite, routes appelées.
 */

function crudProject(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $base = sandboxPath();
    file_put_contents($base.'/composer.json', json_encode(['require' => ['kalnoy/nestedset' => '^7.0']]));
    $installer = new Installer($base, dirname(__DIR__, 2).'/scaffold');
    $installer->execute($installer->plan(), editShared: false);
    mkdir($base.'/app/Http/Controllers', 0777, true);
    file_put_contents($base.'/app/Http/Controllers/Controller.php', "<?php\n\nnamespace App\Http\Controllers;\n\nabstract class Controller {}\n");
    mkdir($base.'/resources/views', 0777, true);
    file_put_contents($base.'/resources/views/app.blade.php', '@inertia');

    $service = moduleService($base);

    foreach (['client', 'invoice', 'category'] as $profile) {
        $input = json_decode(file_get_contents(__DIR__."/../Fixtures/crud/{$profile}.json"), true);
        $service->generate($service->plan($service->definition($input)));
    }

    spl_autoload_register(function (string $class) use ($base) {
        foreach (['App' => '/app/', 'Database'.chr(92).'Factories' => '/database/factories/'] as $prefix => $directory) {
            $prefix .= chr(92);

            if (str_starts_with($class, $prefix)) {
                $file = $base.$directory.str_replace(chr(92), '/', substr($class, strlen($prefix))).'.php';

                if (is_file($file)) {
                    require $file;
                }
            }
        }
    });

    return $base;
}

beforeEach(function () {
    $base = crudProject();
    $this->loadMigrationsFrom($base.'/database/migrations');
    View::addLocation($base.'/resources/views');
    Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function () use ($base) {
        foreach (glob($base.'/routes/modules/*.php') as $file) {
            require $file;
        }
    });
    Storage::fake('public');
    $user = new User;
    $user->id = 1;
    $this->actingAs($user);
});

it('liste, crée, affiche, modifie et supprime des clients', function () {
    $this->post('/admin/clients', ['name' => '', 'email' => 'x'])->assertSessionHasErrors(['name', 'email']);
    $this->post('/admin/clients', ['name' => 'Alice', 'email' => 'alice@example.org', 'vip' => true, 'status' => 'active'])
        ->assertRedirect('/admin/clients')->assertSessionHas('success');
    $this->post('/admin/clients', ['name' => 'Bob', 'email' => 'bob@example.org', 'vip' => false, 'status' => 'prospect']);

    $this->get('/admin/clients?search=ali&sort=name&direction=desc')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Clients/Index')
        ->has('records.data', 1)
        ->where('filters.search', 'ali')
        ->where('options.status.1.label', 'Active'));

    $alice = Client::query()->firstWhere('email', 'alice@example.org');
    expect($alice->status->value)->toBe('active')->and($alice->vip)->toBeTrue();

    $this->get("/admin/clients/{$alice->id}")->assertInertia(fn (AssertableInertia $page) => $page->component('Clients/Show')->where('record.name', 'Alice'));
    $this->put("/admin/clients/{$alice->id}", ['name' => 'Alice B.', 'email' => 'alice@example.org', 'vip' => false, 'status' => 'active'])->assertSessionHasNoErrors();
    $this->put("/admin/clients/{$alice->id}", ['name' => 'Alice', 'email' => 'bob@example.org', 'vip' => false, 'status' => 'active'])->assertSessionHasErrors('email');
    $this->delete("/admin/clients/{$alice->id}")->assertRedirect('/admin/clients');

    expect(Client::query()->count())->toBe(1);
});

it('gère relation, enum, JSON et fichier (stockage, remplacement, suppression)', function () {
    $client = Client::query()->create(['name' => 'Acme', 'email' => 'acme@example.org', 'status' => 'active']);

    $this->post('/admin/invoices', [
        'number' => 'F-1', 'amount' => '12.50', 'status' => 'draft', 'client_id' => $client->id, 'meta' => '{"a":1}',
        'attachment' => UploadedFile::fake()->create('devis.pdf', 10, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $invoice = Invoice::query()->firstWhere('number', 'F-1');
    $old = $invoice->attachment;
    Storage::disk('public')->assertExists($old);
    expect($invoice->meta)->toBe(['a' => 1]);

    $this->post("/admin/invoices/{$invoice->id}", [
        '_method' => 'put', 'number' => 'F-1', 'amount' => '20', 'status' => 'paid', 'client_id' => $client->id,
        'attachment' => UploadedFile::fake()->create('facture.pdf', 10, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $invoice->refresh();
    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($invoice->attachment);
    expect($invoice->status->value)->toBe('paid');

    $this->get('/admin/invoices')->assertInertia(fn (AssertableInertia $page) => $page->where('records.data.0.client.name', 'Acme'));
    $this->delete("/admin/invoices/{$invoice->id}");
    Storage::disk('public')->assertMissing($invoice->attachment);
});

it('maintient l\'arbre et refuse un parent descendant', function () {
    $this->post('/admin/categories', ['name' => 'Racine'])->assertSessionHasNoErrors();
    $root = Category::query()->firstWhere('name', 'Racine');
    $this->post('/admin/categories', ['name' => 'Enfant', 'parent_id' => $root->id])->assertSessionHasNoErrors();
    $child = Category::query()->firstWhere('name', 'Enfant');

    $this->put("/admin/categories/{$root->id}", ['name' => 'Racine', 'parent_id' => $child->id])->assertSessionHasErrors('parent_id');
    $this->get('/admin/categories')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('filters.sort', '_lft')
        ->where('records.data.1.depth', 1)
        ->where('options.parent_id.1.label', '— Enfant'));
});
