<?php

namespace App\Http\Controllers\Admin;

use App\Filters\TaggableFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaggableRequest;
use App\Http\Requests\Admin\UpdateTaggableRequest;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Taggable;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaggableController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = new TaggableFilter($request);

        return Inertia::render('Taggables/Index', [
            'records' => $filter->paginate(Taggable::query()->with(['tag', 'taggable'])),
            'filters' => $filter->state(),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Taggables/Create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreTaggableRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $taggable = Taggable::create($data);

        return to_route('admin.taggables.index')->with('success', 'Enregistrement créé.');
    }

    public function show(Taggable $taggable): Response
    {
        return Inertia::render('Taggables/Show', [
            'record' => $taggable->load(['tag', 'taggable']),
            'options' => $this->options(),
        ]);
    }

    public function edit(Taggable $taggable): Response
    {
        return Inertia::render('Taggables/Edit', [
            'record' => $taggable,
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateTaggableRequest $request, Taggable $taggable): RedirectResponse
    {
        $data = $request->validated();
        $taggable->update($data);

        return to_route('admin.taggables.index')->with('success', 'Enregistrement mis à jour.');
    }

    public function destroy(Taggable $taggable): RedirectResponse
    {
        $taggable->delete();

        return to_route('admin.taggables.index')->with('success', 'Enregistrement supprimé.');
    }

    /**
     * Options des listes : formulaires, filtres et libellés.
     *
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'tag_id' => Tag::query()->orderBy('name')->get()
                ->map(fn (Tag $record) => ['value' => $record->getKey(), 'label' => $record->name])
                ->all(),
            'taggable_type' => collect(Taggable::TAGGABLE_TYPES)
                ->map(fn (string $label, string $class) => ['value' => $class, 'label' => $label])
                ->values()
                ->all(),
            'taggable_id' => [
                Post::class => Post::query()->orderBy('name')->get()
                    ->map(fn (Post $record) => ['value' => $record->getKey(), 'label' => $record->name])
                    ->all(),
                Video::class => Video::query()->orderBy('title')->get()
                    ->map(fn (Video $record) => ['value' => $record->getKey(), 'label' => $record->title])
                    ->all(),
            ],
            'taggable_display' => [
                Post::class => 'name',
                Video::class => 'title',
            ],
        ];
    }
}
