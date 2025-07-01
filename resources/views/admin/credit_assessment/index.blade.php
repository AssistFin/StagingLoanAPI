@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-none-30">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>
                                <th>@lang('S.No')</th>
                                <th>@lang('User ID')</th>
                                <th>@lang('Full Name')</th>
                                <th>@lang('Date of Birth')</th>
                                <th>@lang('Gender')</th>
                                <th>@lang('Mobile Number')</th>
                                <th>@lang('Email')</th>
                                <th>@lang('Marital Status')</th>
                                <th>@lang('Spouse Name')</th>
                                <th>@lang('Number of Kids')</th>
                                <th>@lang('Mother Name')</th>
                                <th>@lang('Qualification')</th>
                                <th>@lang('PAN Number')</th>
                                <th>@lang('Aadhar Number')</th>
                                <th>@lang('Purpose of Loan')</th>
                                <th>@lang('Eligibility Amount')</th>
                                <th>@lang('Credit Score')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Actions')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php $serial = 1; @endphp
                            @forelse($personalDetails as $detail)
                                <tr>
                                    <td>{{ $serial++ }}</td>
                                    <td>{{ $detail->user_id }}</td>
                                    <td>{{ $detail->full_name }}</td>
                                    <td>{{ $detail->date_of_birth }}</td>
                                    <td>{{ $detail->gender }}</td>
                                    <td>{{ $detail->mobile_number }}</td>
                                    <td>{{ $detail->email }}</td>
                                    <td>{{ $detail->marital_status }}</td>
                                    <td>{{ $detail->spouse_name }}</td>
                                    <td>{{ $detail->number_of_kids }}</td>
                                    <td>{{ $detail->mother_name }}</td>
                                    <td>{{ $detail->qualification }}</td>
                                    <td>{{ $detail->pan_number }}</td>
                                    <td>{{ $detail->aadhar_number }}</td>
                                    <td>{{ $detail->purpose_of_loan }}</td>
                                    <td>{{ $detail->eligibility_amount }}</td>
                                    <td>{{ $detail->cibilscore }}</td>
                                    <td>@lang($detail->eligibilityStatus)</td>
                                    <td>
                                        <a href="{{ route('admin.users.detail', $detail->user_id) }}" class="icon-btn">
                                            <i class="las la-desktop"></i>
                                        </a>
                                        <button class="icon-btn delete-btn" data-id="{{ $detail->id }}" onclick="confirmDeletion({{ $detail->id }})">
                                            <i class="las la-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="19" class="text-center">@lang('No data found')</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($personalDetails->hasPages())
                    <div class="card-footer py-4">
                        {{ $personalDetails->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
    function confirmDeletion(id) {
        if(confirm('Are you sure you want to delete this entry?')) {
            fetch('/admin/creditassessment/delete/' + id, { // Adjust the URL to match your application's route
                method: 'DELETE', // Use POST as the method
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded', // Change the content type
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', // CSRF token for Laravel form protection
                },

            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Success:', data);
                window.location.reload(); // Reload the page to reflect the deletion
            })
            .catch((error) => {
                console.error('Error:', error);
            });
        }
    }
</script>


@endsection

@push('breadcrumb-plugins')
    @if(request()->routeIs('admin.creditassessment.new'))
        <x-search-form placeholder="Enter Username" />
    @endif
@endpush
