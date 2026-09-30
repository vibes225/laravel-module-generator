<?php

namespace App\Http\Controllers\Admin;

use App\Filters\CategoryFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = new CategoryFilter($request);

        return Inertia::render('Categories/Index', [
            'records' => $filter->paginate(Category::query()->with(['owner'])->withDepth()),
            'filters' => $filter->state(),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Categories/Create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')->store('categories', 'public');
        }

        $category = Category::create($data);

        return to_route('admin.categories.index')->with('success', 'Enregistrement créé.');
    }

    public function show(Category $category): Response
    {
        return Inertia::render('Categories/Show', [
            'record' => $category->load(['owner', 'parent']),
            'options' => $this->options(),
        ]);
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Categories/Edit', [
            'record' => $category,
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('cover')) {
            if ($category->cover) {
                Storage::disk('public')->delete($category->cover);
            }

            $data['cover'] = $request->file('cover')->store('categories', 'public');
        } else {
            unset($data['cover']);
        }

        $category->update($data);

        return to_route('admin.categories.index')->with('success', 'Enregistrement mis à jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        if ($category->cover) {
            Storage::disk('public')->delete($category->cover);
        }

        return to_route('admin.categories.index')->with('success', 'Enregistrement supprimé.');
    }

    /**
     * Options des listes : formulaires, filtres et libellés.
     *
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'owner_type' => collect(Category::OWNER_TYPES)
                ->map(fn (string $label, string $class) => ['value' => $class, 'label' => $label])
                ->values()
                ->all(),
            'owner_id' => [
                User::class => User::query()->orderBy('name')->get()
                    ->map(fn (User $record) => ['value' => $record->getKey(), 'label' => $record->name])
                    ->all(),
                Client::class => Client::query()->orderBy('company')->get()
                    ->map(fn (Client $record) => ['value' => $record->getKey(), 'label' => $record->company])
                    ->all(),
            ],
            'owner_display' => [
                User::class => 'name',
                Client::class => 'company',
            ],
            'parent_id' => Category::query()->withDepth()->defaultOrder()->get()
                ->map(fn (Category $record) => ['value' => $record->id, 'label' => str_repeat('— ', $record->depth).$record->name])
                ->all(),
        ];
    }
}
