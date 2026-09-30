<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Filters\InvoiceFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = new InvoiceFilter($request);

        return Inertia::render('Invoices/Index', [
            'records' => $filter->paginate(Invoice::query()->with(['client'])),
            'filters' => $filter->state(),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Invoices/Create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('invoices', 'public');
        }

        $data['meta'] = isset($data['meta']) ? json_decode($data['meta'], true) : null;

        $invoice = Invoice::create(Arr::except($data, ['tags']));
        $invoice->tags()->sync($data['tags'] ?? []);

        return to_route('admin.invoices.index')->with('success', 'Enregistrement créé.');
    }

    public function show(Invoice $invoice): Response
    {
        return Inertia::render('Invoices/Show', [
            'record' => $invoice->load(['client', 'tags']),
            'options' => $this->options(),
        ]);
    }

    public function edit(Invoice $invoice): Response
    {
        return Inertia::render('Invoices/Edit', [
            'record' => [
                ...$invoice->toArray(),
                'tags' => $invoice->tags()->pluck('tags.id'),
            ],
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            if ($invoice->attachment) {
                Storage::disk('public')->delete($invoice->attachment);
            }

            $data['attachment'] = $request->file('attachment')->store('invoices', 'public');
        } else {
            unset($data['attachment']);
        }

        if (blank($data['secret'] ?? null)) {
            unset($data['secret']);
        }

        $data['meta'] = isset($data['meta']) ? json_decode($data['meta'], true) : null;

        $invoice->update(Arr::except($data, ['tags']));
        $invoice->tags()->sync($data['tags'] ?? []);

        return to_route('admin.invoices.index')->with('success', 'Enregistrement mis à jour.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        if ($invoice->attachment) {
            Storage::disk('public')->delete($invoice->attachment);
        }

        return to_route('admin.invoices.index')->with('success', 'Enregistrement supprimé.');
    }

    /**
     * Options des listes : formulaires, filtres et libellés.
     *
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'status' => InvoiceStatus::options(),
            'is_paid' => [['value' => '1', 'label' => 'Oui'], ['value' => '0', 'label' => 'Non']],
            'client_id' => Client::query()->orderBy('name')->get()
                ->map(fn (Client $record) => ['value' => $record->getKey(), 'label' => $record->name])
                ->all(),
            'tags' => Tag::query()->orderBy('name')->get()
                ->map(fn (Tag $record) => ['value' => $record->getKey(), 'label' => $record->name])
                ->all(),
        ];
    }
}
