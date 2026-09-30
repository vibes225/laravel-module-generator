<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientStatus;
use App\Filters\ClientFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = new ClientFilter($request);

        return Inertia::render('Clients/Index', [
            'records' => $filter->paginate(Client::query()),
            'filters' => $filter->state(),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clients/Create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $client = Client::create($data);

        return to_route('admin.clients.index')->with('success', 'Enregistrement créé.');
    }

    public function show(Client $client): Response
    {
        return Inertia::render('Clients/Show', [
            'record' => $client,
            'options' => $this->options(),
        ]);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('Clients/Edit', [
            'record' => $client,
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $data = $request->validated();
        $client->update($data);

        return to_route('admin.clients.index')->with('success', 'Enregistrement mis à jour.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return to_route('admin.clients.index')->with('success', 'Enregistrement supprimé.');
    }

    /**
     * Options des listes : formulaires, filtres et libellés.
     *
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'vip' => [['value' => '1', 'label' => 'Oui'], ['value' => '0', 'label' => 'Non']],
            'status' => ClientStatus::options(),
        ];
    }
}
