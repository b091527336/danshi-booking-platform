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
            ->withCount('bookings')
            ->when($filters['keyword'] ?? null, function (Builder $query, string $keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%")
                        ->orWhere('external_id', 'like', "%{$keyword}%");
                });
            })
            ->when(array_key_exists('active', $filters), fn (Builder $query) =>
                $query->where('is_active', (bool) $filters['active']))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('organizations.index', compact('organizations'));
    }

    public function show(Organization $organization): View
    {
        $organization->load([
            'bookings' => fn ($query) => $query
                ->with('customer')
                ->latest('starts_at')
                ->limit(10),
        ])->loadCount('bookings');

        return view('organizations.show', compact('organization'));
    }

    public function edit(Organization $organization): View
    {
        return view('organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('organizations')->ignore($organization)],
            'external_provider' => ['required', 'string', 'max:50'],
            'external_id' => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('organizations')
                    ->where(fn ($query) => $query->where('external_provider', $request->string('external_provider')))
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
