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

    .filter-row {
        display: flex;
        gap: 10px;
        padding: 10px 0;
    }

    .filter-row select,
    .filter-row input[type="text"] {
        height: 40px;
        padding: 6px 12px;
        font-size: 14px;
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    .custom-date-container {
        display: none;
        margin-top: 10px;
    }

    .custom-date-container input[type="date"] {
        padding: 6px 12px;
        font-size: 14px;
        border-radius: 6px;
        border: 1px solid #ccc;
        margin-right: 10px;
    }

    .section-heading {
        font-weight: bold;
        margin-bottom: 5px;
    }

    .custom-date-container {
        display: none;
        margin-top: 10px;
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
                    <div class="flex flex-wrap items-center gap-3 py-4">
                        <div class="filter-row">
                            <select id="date_range" name="date_range" class="form-control">
                                <option value="">Select Date Range</option>
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="last_3_days">Last 3 Days</option>
                                <option value="last_7_days">Last 7 Days</option>
                                <option value="last_15_days">Last 15 Days</option>
                                <option value="current_month">Current Month</option>
                                <option value="previous_month">Previous Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                            
                            <input type="text" id="pendingempdetails-lead-search-input" class="form-control" 
                            placeholder="Search by Name / Mobile No / Loan App " 
                            style="max-width: 400px;">

                            <input type="text" id="total_records" placeholder="Total Records"  class="form-control" value="{{ $totalRecords }}" readonly >

                            <button type="button" id="pendingempdetails_lead_export" class="btn btn-primary form-control">Export CSV</button>
                        </div>

                        {{-- Custom Date Row --}}
                        <div id="customDateSection" class="custom-date-container" style="margin-top: 10px;">
                            <div class="section-heading">Select Custom Date Range:</div>
                            <div class="flex gap-3">
                                <input type="text" id="from_date" name="from_date" class="datepicker" placeholder="From Date" autocomplete="off" />
                                <input type="text" id="to_date" name="to_date" class="datepicker" placeholder="To Date" autocomplete="off" />
                            </div>
                        </div>
                        
                    </div>
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>
                                <th></th>
                                <th>@lang('Last Activity')</th>
                                <th>@lang('Loan Application No.')</th>
                                <th>@lang('Customer Name')</th>
                                <th>@lang('Mobile No')</th>
                                <th>@lang('Loan Amount')</th>
                                <th>@lang('Purpose Of Loan')</th>
                                <th>@lang('Personal Details')</th>
                                <th>@lang('KYC Details')</th>
                                <th>@lang('Selfie Document')</th>
                                <th>@lang('Address Details')</th>
                                <th>@lang('Employement Details')</th>
                            </tr>
                            </thead>
                            <tbody id="pendingempdetailsLeadsTable">
                            @forelse($leads as $lead)
                                    <tr>
                                        <td style="cursor: pointer">
                                            <a href="{{route('admin.leads.verify', $lead->id)}}"><i class="fas fa-eye"></i></a>
                                        </td>
                                        <td> @if($lead->last_activity_at)
                                                {{ showDateTime($lead->last_activity_at) }} <br>
                                                <small class="text-muted">
                                                    {{ $lead->last_activity_at->diffForHumans() }}
                                                </small>
                                            @else
                                                <span class="text-muted">No Activity</span>
                                            @endif</td>
                                        <td>{{ $lead->loan_no }}</td>
                                        <td>{{ $lead->user ? $lead->user->firstname . " " . $lead->user->lastname : '' }}</td>
                                        <td>{{ $lead->user ? $lead->user->mobile : '' }}</td>
                                        <td>{{ number_format($lead->loan_amount, 0) }}</td>
                                        <td>{{ $lead->purpose_of_loan }}</td>
                                        <td>{!! $lead->personalDetails ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' !!}</td>
                                        <td>{!! $lead->kycDetails ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' !!}</td>
                                        <td>{!! $lead->loanDocument ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' !!}</td>
                                        <td>{!! $lead->addressDetails ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' !!}</td>
                                        <td>{!! $lead->employmentDetails ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' !!}</td>
                                    </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">@lang('No data found')</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                        <br>
                        <br>  
                        <div class="sticky-pagination" id="pendingempdetailspaginationLinks">
                            {{ $leads->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    @if(request()->routeIs('admin.leads.bsa'))
        <x-search-form placeholder="Enter Username" />
    @endif
@endpush
@push('script')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-slimScroll/1.3.8/jquery.slimscroll.min.js"></script>
<script>
    function fetchpendingempdetailsLeads(page = 1) {
        const dateRange = $('#date_range').val();
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const searchTerm = $('#pendingempdetails-lead-search-input').val();

        $.ajax({
            url: "{{ route('admin.leads.pendingempdetails') }}",
            type: "GET",
            data: {
                page: page,
                date_range: dateRange,
                from_date: fromDate,
                to_date: toDate,
                missing_info: missingInfo,
                search: searchTerm
            },
            success: function(response) {
                $('#pendingempdetailsLeadsTable').html($(response).find('#pendingempdetailsLeadsTable').html());
                $('#pendingempdetailspaginationLinks').html($(response).find('#pendingempdetailspaginationLinks').html());
                $('#total_records').val($(response).find('#total_records').val());
            }
        });
    }

    $(document).ready(function () {
        $('#date_range').on('change', () => fetchpendingempdetailsLeads());
        $('#pendingempdetails-lead-search-input').on('keyup', () => fetchpendingempdetailsLeads());

        $('#date_range').on('change', function () {
            // Initialize datepickers but don't show them immediately

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            $("#from_date").datepicker({
                dateFormat: "yy-mm-dd",
                maxDate: -1, // disable today and future
                onSelect: function (selectedDate) {
                    const fromDate = $(this).datepicker("getDate");

                    // Set minDate for To Date to be the next day after From Date
                    const minToDate = new Date(fromDate);
                    minToDate.setDate(minToDate.getDate() + 1);

                    $("#to_date").datepicker("option", "minDate", minToDate);
                    $("#to_date").datepicker("option", "maxDate", today);

                    setTimeout(() => $("#to_date").datepicker("show"), 300);
                }
            });

            $("#to_date").datepicker({
                dateFormat: "yy-mm-dd",
                maxDate: today
            });

            if ($(this).val() === 'custom') {
                $('#customDateSection').removeClass('hidden');
                $('#customDateSection').slideDown(200, function () {
                $("#from_date").datepicker("show"); // Auto-open from_date
            });
            } else {
                $('#customDateSection').addClass('hidden');
                $('#customDateSection').slideUp();
                $('#from_date, #to_date').val('');
            }
        });

        $('#from_date, #to_date').on('change', function () {
            const fromDate = $('#from_date').val();
            const toDate = $('#to_date').val();
            if (fromDate && toDate && new Date(toDate) >= new Date(fromDate)) {
                fetchpendingempdetailsLeads();
            }
        });

        $('#pendingempdetailspaginationLinks').on('click', '.pagination .page-item .page-link', function (e) {
            e.preventDefault();

            const href = $(this).attr('href');
            //console.log('test', href);
            if (!href || href === '#') return;

            try {
                const url = new URL(href, window.location.origin);
                const page = url.searchParams.get("page") || 1;
                fetchpendingempdetailsLeads(page);
            } catch (error) {
                console.error("Invalid pagination URL", error);
            }
        });

        $('#pendingempdetails_lead_export').on('click', function () {
            const params = {
                date_range: $('#date_range').val(),
                from_date: $('#from_date').val(),
                to_date: $('#to_date').val(),
                search: $('#pendingempdetails-lead-search-input').val(),
                export: 'csv'
            };
            const query = $.param(params);
            window.location.href = "{{ route('admin.leads.pendingempdetails') }}?" + query;
        });
    });
</script>
@endpush