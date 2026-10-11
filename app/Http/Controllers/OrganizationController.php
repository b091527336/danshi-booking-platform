<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'in:1,0'],
        ]);

        $organizations = Organization::query()
            ->whereIn('id', $request->user()->organizationIds())
            ->withCount('bookings')
            ->when($filters['keyword'] ?? null, function (Builder $query, string $keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%")
                        ->orWhere('external_id', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('active'), fn (Builder $query) =>
                $query->where('is_active', $request->boolean('active')))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('organizations.index', compact('organizations'));
    }

    public function show(Organization $organization): View
    {
        abort_unless(in_array($organization->id, request()->user()->organizationIds()), 403);
        $organization->load([
            'bookings' => fn ($query) => $query
                ->with('customer')
                ->latest('starts_at')
                ->limit(10),
        ])->loadCount('bookings');

        return view('organizations.show', compact('organization'));
    }

    public function create(): View
    {
        abort_unless(request()->user()->isAdmin(), 403);
        return view('organizations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $provider = (string) $request->input('external_provider');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', 'unique:organizations,slug'],
            'external_provider' => ['required', 'string', 'max:50'],
            'external_id' => [
                'required',
                'string',
                'max:150',
                Rule::unique('organizations')
                    ->where(fn ($query) => $query->where('external_provider', $provider)),
            ],
            'timezone' => ['required', 'timezone'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $organization = Organization::create($data);

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', '據點已新增。');
    }

    public function edit(Organization $organization): View
    {
        abort_unless(request()->user()->isAdmin(), 403);
        return view('organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $provider = (string) $request->input('external_provider');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('organizations')->ignore($organization)],
            'external_provider' => ['required', 'string', 'max:50'],
            'external_id' => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('organizations')
                    ->where(fn ($query) => $query->where('external_provider', $provider))
                    ->ignore($organization),
            ],
            'timezone' => ['required', 'timezone'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $organization->update($data);

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', '據點設定已更新。');
    }
}
