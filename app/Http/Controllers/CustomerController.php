<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
        ]);

        $customers = Customer::query()
            ->whereHas('bookings', fn (Builder $query) => $query->whereIn('organization_id', $request->user()->organizationIds()))
            ->withCount('bookings')
            ->when($filters['keyword'] ?? null, function (Builder $query, string $keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function show(Customer $customer): View
    {
        $this->authorizeCustomer($customer);
        $organizationIds = request()->user()->organizationIds();
        $customer->load([
            'bookings' => fn ($query) => $query
                ->with('organization')
                ->whereIn('organization_id', $organizationIds)
                ->latest('starts_at')
                ->limit(10),
        ])->loadCount(['bookings' => fn ($query) => $query->whereIn('organization_id', $organizationIds)]);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $this->authorizeCustomer($customer);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($customer);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $customer->update($data);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', '客戶資料已更新。');
    }

    private function authorizeCustomer(Customer $customer): void
    {
        abort_unless($customer->bookings()->whereIn('organization_id', request()->user()->organizationIds())->exists(), 403);
    }
}
