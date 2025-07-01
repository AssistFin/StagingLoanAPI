<!-- Blade UI Template -->
@extends('admin.layouts.app')

@section('panel')
<div class="row justify-content-center">
    <div class="col-lg-12">
        <div class="card b-radius--10">
            <div class="card-body p-0">

                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th>@lang('Unique Request Number')</th>
                                <th>@lang('User')</th>
                                <th>@lang('Beneficiary Name')</th>
                                <th>@lang('Amount')</th>
                                <th>@lang('Payment Mode')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Created At')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $transfer)
                            <tr>
                                <td>{{ $transfer->unique_request_number }}</td>
                                <td>
                                    <a href="{{ route('admin.users.detail', $transfer->user->id) }}" class="fw-bold">
                                        {{ $transfer->user->fullname ?? 'N/A' }}
                                    </a>
                                </td>
                                <td>{{ $transfer->beneficiary_name }}</td>
                                <td>{{ __($transfer->currency) }}{{ number_format($transfer->amount, 2) }}</td>
                                <td>{{ ucfirst($transfer->payment_mode) }}</td>
                                <td>
                                    <span class="badge {{ $transfer->status === 'success' ? 'bg-success' : ($transfer->status === 'pending' ? 'bg-warning' : 'bg-danger') }}">
                                        {{ ucfirst($transfer->status) }}
                                    </span>
                                </td>
                                <td>{{ $transfer->created_at->format('d M Y, H:i') }}</td>
                                <td>
                                    <a href="" class="btn btn-sm btn-outline--primary">
                                        <i class="la la-desktop"></i> @lang('Details')
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">@lang('No transfers found.')</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($transfers->hasPages())
            <div class="card-footer py-4">
                {{ $transfers->links('pagination::bootstrap-4') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
<x-search-form dateSearch='yes' />
@endpush
