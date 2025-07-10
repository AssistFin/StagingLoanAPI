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
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" id="experian_cb_export" class="btn btn-primary form-control" style="max-width: 150px;" >Export CSV</button>
                    </div>
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>
                                <th>@lang('Name')</th>
                                <th>@lang('DOB')</th>
                                <th>@lang('PAN No')</th>
                                <th>@lang('Full Address')</th>
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
                                        {{ $record->addressDetails->state ?? '' }}
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

    $('#experian_cb_export').on('click', function () {
        const params = {
            export: 'csv'
        };
        const query = $.param(params);
        window.location.href = "{{ route('admin.experiancreditbureau.index') }}?" + query;
    });


</script>
@endpush