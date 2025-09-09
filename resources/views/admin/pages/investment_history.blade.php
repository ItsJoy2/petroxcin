@extends('admin.layouts.app')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Investment History</h4>
    </div>

    <div class="card-body">

        <!-- Filter Form -->
        <form action="{{ route('admin.investments') }}" method="GET" class="mb-3 d-flex align-items-center gap-2 flex-wrap">
            <!-- Search by Name / Email -->
            <input type="text" name="search" class="form-control w-auto" placeholder="Search by Name / Email"
                   value="{{ request('search') }}">

            <!-- From Date -->
            <input type="date" name="from_date" class="form-control w-auto" value="{{ request('from_date') }}">

            <!-- To Date -->
            <input type="date" name="to_date" class="form-control w-auto" value="{{ request('to_date') }}">

            <!-- Filter Button -->
            <button type="submit" class="btn btn-primary">Filter</button>

            <!-- Reset Button -->
            @if(request()->has('search') || request()->has('from_date') || request()->has('to_date'))
                <a href="{{ route('admin.investments') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </form>

        <div class="table-responsive">
                        <!-- Investment Table -->
            <table class="table table-striped table-hover table-head-bg-primary mt-4">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Package</th>
                        <th>Investment</th>
                        <th>Duration</th>
                        <th>Total Received</th>
                        {{-- <th>Total Due</th> --}}
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($investors as $key => $inv)
                        <tr>
                            <td>{{ $investors->firstItem() + $key }}</td>
                            <td>{{ $inv->user->name ?? 'N/A' }}</td>
                            <td>{{ $inv->user->email ?? 'N/A' }}</td>
                            <td>{{ $inv->package_name ?? 'N/A' }}</td>
                            <td>${{ number_format($inv->investment, 2) }}</td>
                            {{-- <td>${{ number_format($inv->payable_amount, 2) }}</td> --}}
                            <td>{{ $inv->duration == 0 ? 'Unlimited' : $inv->duration . ' Days' }}</td>
                            <td>{{ $inv->total_receive_day }} Days</td>
                            {{-- <td>{{ $inv->total_due_day }} Days</td> --}}
                            <td>
                                @if($inv->status)
                                    <span class="badge bg-success">Running</span>
                                @else
                                    <span class="badge bg-danger">Cancelled</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No investment history found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $investors->appends(request()->query())->links('admin.layouts.partials.__pagination') }}
        </div>
</div>
@endsection
