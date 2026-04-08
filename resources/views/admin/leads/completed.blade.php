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
                                <th>@lang('Actions')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($personalDetails as $detail)
                                <tr>
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
                                    <td>
                                        <!-- Define your action buttons here -->
                                        <a href="{{ route('admin.users.detail', $detail->user_id) }}" class="icon-btn">
                                            <i class="las la-desktop"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="16" class="text-center">@lang('No data found')</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($personalDetails->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($personalDetails) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>
@endsection
