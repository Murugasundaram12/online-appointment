@extends('layouts.app')

@section('title', 'Subscription')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-3 px-4 border-bottom">
        <h2 class="fs-4 m-0 fw-bold">Subscription</h2>
    </nav>
    <div class="container-fluid px-4 pt-4">
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

        <div class="row g-3 mb-4">
            @foreach($plans as $plan)
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h3 class="fs-5 fw-bold mb-1">{{ $plan->name }}</h3>
                                    <div class="text-muted small">{{ $plan->description }}</div>
                                </div>
                                @if($currentSubscription && $currentSubscription->subscription_plan_id === $plan->id)
                                    <span class="badge bg-light text-primary">Current</span>
                                @endif
                            </div>
                            <div class="display-6 fw-bold mb-3">${{ number_format($plan->price, 2) }} <span class="fs-6 text-muted">/{{ $plan->billing_cycle }}</span></div>
                            <div class="small text-muted mb-2"><i class='bx bx-group me-1'></i> Staff limit: {{ $plan->staff_limit ?? 'Unlimited' }}</div>
                            <div class="small text-muted mb-2"><i class='bx bx-map me-1'></i> Location limit: {{ $plan->location_limit ?? 'Unlimited' }}</div>
                            <div class="small text-muted mb-3"><i class='bx bx-calendar me-1'></i> Appointment limit: {{ $plan->appointment_limit ?? 'Unlimited' }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="fs-6 fw-bold">Current subscription</h3>
                        @if($currentSubscription)
                            <p class="mb-1"><strong>Plan:</strong> {{ $currentSubscription->plan->name }}</p>
                            <p class="mb-1"><strong>Status:</strong> {{ ucfirst($currentSubscription->status) }}</p>
                            <p class="mb-1"><strong>Start:</strong> {{ $currentSubscription->start_date->format('M j, Y') }}</p>
                            <p class="mb-1"><strong>Expiry:</strong> {{ optional($currentSubscription->end_date)->format('M j, Y') ?: '-' }}</p>
                            <p class="mb-0"><strong>Payment:</strong> {{ ucfirst($currentSubscription->payment_status) }}</p>
                        @else
                            <p class="text-muted mb-0">No active subscription.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="fs-6 fw-bold">Activate or change plan</h3>
                        <form method="POST" action="{{ route('subscription.activate') }}" class="row g-3">
                            @csrf
                            <div class="col-md-4">
                                <select class="form-select" name="subscription_plan_id" required>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->name }} - ${{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3"><input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                            <div class="col-md-3"><input type="date" name="end_date" class="form-control" value="{{ now()->addMonth()->toDateString() }}"></div>
                            <div class="col-md-2">
                                <select class="form-select" name="payment_status"><option value="paid">Paid</option><option value="unpaid">Unpaid</option></select>
                            </div>
                            <div class="col-12"><button class="btn btn-primary px-4">Activate</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Plan</th><th>Dates</th><th>Status</th><th>Amount</th><th>Payment</th></tr></thead>
                    <tbody>
                    @forelse($history as $subscription)
                        <tr>
                            <td>{{ $subscription->plan->name }}</td>
                            <td>{{ $subscription->start_date->format('M j, Y') }} - {{ optional($subscription->end_date)->format('M j, Y') ?: '-' }}</td>
                            <td>{{ ucfirst($subscription->status) }}</td>
                            <td>${{ number_format($subscription->amount, 2) }}</td>
                            <td>{{ ucfirst($subscription->payment_status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No subscription history.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $history->links() }}</div>
        </div>
    </div>
@endsection
