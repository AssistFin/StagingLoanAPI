@extends('admin.layouts.app')
@push('style')
<style>
    .sticky-pagination {
        position: fixed;
        bottom: 0;
        left: 260px; /* adjust based on your sidebar width */
        right: 0;
        background-color: white;
        padding: 10px 0;
        text-align: center;
        z-index: 1000;
        border-top: 1px solid #ccc;
    }

    .pagination {
        display: flex;
        justify-content: flex-end;
    }
</style>
@endpush
@section('panel')
    <div class="row mb-none-30">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>
                                <th>@lang('Name')</th>
                                <th>@lang('DOB')</th>
                                <th>@lang('PAN No')</th>
                                <th>@lang('Full Address')</th>
                                <th>@lang('Action')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($userRecords as $record)
                            @if( $record->user->firstname && $record->user->lastname && $record->user->mobile && $record->date_of_birth && $record->pan && isset($record->addressDetails->house_no) && isset($record->addressDetails->city) && isset($record->addressDetails->pincode) && isset($record->addressDetails->state))
                                <tr>
                                    <td>{{ $record->user->firstname }} {{ $record->user->lastname }}
                                    </br>({{ $record->user->mobile }})</td>
                                    <td>{{ $record->date_of_birth ?? '' }}</td>
                                    <td>{{ $record->pan ?? '' }}</td>
                                    <td>{{ $record->addressDetails->house_no ?? '' }}</br>
                                        {{ $record->addressDetails->city ?? '' }}</br>
                                        {{ $record->addressDetails->pincode ?? '' }}</br>
                                        {{ $record->addressDetails->state ?? '' }}</td>

                                    <td>
                                        
                                        @if (empty($record->pdf_url))
                                            <button type="button" onclick="checkCreditScore({{ $record->user->id }})" class="btn btn-danger">Check Credit Score</button></br></br>
                                            <input type="hidden" id="loanno_{{ $record->user->id }}" value="{{ $record->loan_no }}">
                                            <input type="hidden" id="userid_{{ $record->user->id }}" value="{{ $record->user->id }}">
                                            <input type="hidden" id="firstname_{{ $record->user->id }}" value="{{ $record->user->firstname }}">
                                            <input type="hidden" id="lastname_{{ $record->user->id }}" value="{{ $record->user->lastname }}">
                                            <input type="hidden" id="mobile_{{ $record->user->id }}" value="{{ $record->user->mobile }}">
                                            <input type="hidden" id="dob_{{ $record->user->id }}" value="{{ $record->date_of_birth ?? '' }}">
                                            <input type="hidden" id="pan_{{ $record->user->id }}" value="{{ $record->pan ?? '' }}">
                                            <input type="hidden" id="houseno_{{ $record->user->id }}" value="{{ $record->addressDetails->house_no ?? '' }}">
                                            <input type="hidden" id="city_{{ $record->user->id }}" value="{{ $record->addressDetails->city ?? '' }}">
                                            <input type="hidden" id="pincode_{{ $record->user->id }}" value="{{ $record->addressDetails->pincode ?? '' }}">
                                            <input type="hidden" id="state_{{ $record->user->id }}" value="{{ $record->addressDetails->state ?? '' }}">
                                        @endif
                                        @if (!empty($record->pdf_url))
                                            <a href="{{ $record->pdf_url }}" class="btn btn-primary" id="{{ $record->pan }}" target="_blank">View Credit Score</a>
                                            </br></br>
                                            <a href="{{ $record->pdf_url }}" class="btn btn-secondary" download>Download PDF</a>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">@lang('No User data found')</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    </br></br>
                    @if($userRecords->hasPages())
                        <div class="sticky-pagination" class="card-footer py-4">
                            {{ paginateLinks($userRecords) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder="Search by source, medium, campaign or user" />
@endpush

@push('script')
<script>
    // Add tooltip for full user agent string
    $(document).ready(function(){
        $('.fa-info-circle').tooltip();
    });

    function checkCreditScore(id) {
        var user_id = document.getElementById('userid_'+id).value;
        var loan_no = document.getElementById('loanno_'+id).value;
        
        var firstname = document.getElementById('firstname_'+id).value;
        var lastname = document.getElementById('lastname_'+id).value;
        var mobile = document.getElementById('mobile_'+id).value;
        var date_of_birth = document.getElementById('dob_'+id).value;
        var dob = date_of_birth.replace(/-/g, "");
        var pan = document.getElementById('pan_'+id).value;
        var house_no = document.getElementById('houseno_'+id).value;
        var city = document.getElementById('city_'+id).value;
        var pincode = document.getElementById('pincode_'+id).value;
        var state = document.getElementById('state_'+id).value;

        if(!loan_no) return false;
        if(!user_id) return false;
        if(!firstname) return false;
        if(!lastname) return false;
        if(!mobile) return false;
        if(!dob) return false;
        if(!pan) return false;
        if(!house_no) return false;
        if(!city) return false;
        if(!pincode) return false;
        if(!state) return false;

        if(firstname && lastname && mobile && dob && pan && house_no && city && pincode && state && loan_no && user_id){
            $.ajax({
                url: "{{ route('admin.creditbureau.checkReport') }}",
                type: "GET",
                data: {
                    firstname : firstname, lastname : lastname, mobile : mobile, dob : dob, pan : pan, house_no : house_no, city : city, pincode : pincode, state : state, loan_no : loan_no, user_id : user_id
                },
                success: function(response) {
                    alert("Success");
                    location.reload();
                }
            });
        }else{
            alert("Something went wrong....");
        }
        
    }
</script>
@endpush