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
                        <input type="text" id="total_records" placeholder="Total Records"  class="form-control" value="{{ $totalRecords }}" style="max-width: 150px;" readonly >
                        &nbsp;&nbsp;
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
                            <tbody id="ecbTable">
                            @forelse($userRecords as $record)
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
                        <div class="sticky-pagination" id="ecbPaginationLinks" class="card-footer py-4">
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

    function fetchECBs(page = 1) {

        $.ajax({
            url: "{{ route('admin.experiancreditbureau.index') }}",
            type: "GET",
            data: {
                page: page,
            },
            success: function(response) {
                $('#ecbTable').html($(response).find('#ecbTable').html());
                $('#ecbPaginationLinks').html($(response).find('#ecbPaginationLinks').html());
                $('#total_records').val($(response).find('#total_records').val());
            }
        });
    }

    $(document).ready(function () {

        $('#ecbPaginationLinks').on('click', '.pagination .page-item .page-link', function (e) {
            e.preventDefault();

            const href = $(this).attr('href');
            //console.log('test', href);
            if (!href || href === '#') return;

            try {
                const url = new URL(href, window.location.origin);
                const page = url.searchParams.get("page") || 1;
                fetchECBs(page);
            } catch (error) {
                console.error("Invalid pagination URL", error);
            }
        });

        $('#experian_cb_export').on('click', function () {
            const params = {
                export: 'csv'
            };
            const query = $.param(params);
            window.location.href = "{{ route('admin.experiancreditbureau.index') }}?" + query;
        });
    });


</script>
@endpush