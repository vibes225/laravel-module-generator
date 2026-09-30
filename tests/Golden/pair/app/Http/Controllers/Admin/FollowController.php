<?php

namespace App\Http\Controllers\Admin;

use App\Filters\FollowFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFollowRequest;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Module pivot sans colonne id : un enregistrement est identifié par son couple de clés.
 * Pas de fiche ni de modification : supprimer puis recréer le lien.
 */
class FollowController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = new FollowFilter($request);

        return Inertia::render('UserUser/Index', [
            'records' => $filter->paginate(Follow::query()->with(['follower', 'followed'])),
            'filters' => $filter->state(),
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('UserUser/Create', [
            'options' => $this->options(),
        ]);
    }

    public function store(StoreFollowRequest $request): RedirectResponse
    {
        Follow::create($request->validated());

        return to_route('admin.user-user.index')->with('success', 'Enregistrement créé.');
    }

    public function destroy(int $follower_id, int $followed_id): RedirectResponse
    {
        Follow::query()
            ->where('follower_id', $follower_id)
            ->where('followed_id', $followed_id)
            ->delete();

        return to_route('admin.user-user.index')->with('success', 'Enregistrement supprimé.');
    }

    /**
     * Options des listes : formulaires, filtres et libellés.
     *
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'follower_id' => User::query()->orderBy('name')->get()
                ->map(fn (User $record) => ['value' => $record->getKey(), 'label' => $record->name])
                ->all(),
            'followed_id' => User::query()->orderBy('name')->get()
                ->map(fn (User $record) => ['value' => $record->getKey(), 'label' => $record->name])
                ->all(),
        ];
    }
}
