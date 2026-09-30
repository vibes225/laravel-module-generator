<?php

namespace App\Http\Controllers\Admin;

use App\Filters\ClientTagFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientTagRequest;
use App\Http\Requests\Admin\UpdateClientTagRequest;
use App\Models\Client;
use App\Models\ClientTag;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientTagController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = new ClientTagFilter($request);

        return Inertia::render('ClientTag/Index', [
            'records' => $filter->paginate(ClientTag::query()->with(['client', 'tag'])),
            'filters' => $filter->state(),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ClientTag/Create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreClientTagRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $clientTag = ClientTag::create($data);

        return to_route('admin.client-tag.index')->with('success', 'Enregistrement créé.');
    }

    public function show(ClientTag $clientTag): Response
    {
        return Inertia::render('ClientTag/Show', [
            'record' => $clientTag->load(['client', 'tag']),
            'options' => $this->options(),
        ]);
    }

    public function edit(ClientTag $clientTag): Response
    {
        return Inertia::render('ClientTag/Edit', [
            'record' => $clientTag,
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateClientTagRequest $request, ClientTag $clientTag): RedirectResponse
    {
        $data = $request->validated();
        $clientTag->update($data);

        return to_route('admin.client-tag.index')->with('success', 'Enregistrement mis à jour.');
    }

    public function destroy(ClientTag $clientTag): RedirectResponse
    {
        $clientTag->delete();

        return to_route('admin.client-tag.index')->with('success', 'Enregistrement supprimé.');
    }

    /**
     * Options des listes : formulaires, filtres et libellés.
     *
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'client_id' => Client::query()->orderBy('name')->get()
                ->map(fn (Client $record) => ['value' => $record->getKey(), 'label' => $record->name])
                ->all(),
            'tag_id' => Tag::query()->orderBy('name')->get()
                ->map(fn (Tag $record) => ['value' => $record->getKey(), 'label' => $record->name])
                ->all(),
        ];
    }
}
