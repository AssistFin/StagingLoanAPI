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

                            <button type="button" id="report_export" class="btn btn-primary form-control">Export CSV</button>
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
                                <th>Date</th>
                                <th>Leads</th>
                                <th>PAN</th>
                                <th>Aadhar</th>
                                <th>Bureau</th>
                                <th>Selfie</th>
                                <th>Bank Statement</th>
                                <th>Approvals</th>
                                <th>Disbursed</th>
                            </tr>
                            </thead>
                            <tbody id="reportsTable">
                                @forelse($data as $row)
                                    <tr>
                                        <td>{{ $row->creation_date }}</td>
                                        <td>{{ $row->Leads }}</td>
                                        <td>{{ $row->PAN }}</td>
                                        <td>{{ $row->Aadhar }}</td>
                                        <td>{{ $row->Bureau }}</td>
                                        <td>{{ $row->Selfie }}</td>
                                        <td>{{ $row->{'Bank Statement'} }}</td>
                                        <td>{{ $row->Approvals }}</td>
                                        <td>{{ $row->Disbursed }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">No Records Found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <br>
                        <br>
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
<!-- jQuery and jQuery UI -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-slimScroll/1.3.8/jquery.slimscroll.min.js"></script>
<script>
    function fetchReports(page = 1) {
        const dateRange = $('#date_range').val();
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();

        $.ajax({
            url: "{{ route('admin.osreport.findex') }}",
            type: "GET",
            data: {
                page: page,
                date_range: dateRange,
                from_date: fromDate,
                to_date: toDate,
            },
            success: function(response) {
                $('#reportsTable').html($(response).find('#reportsTable').html());
            }
        });
    }

    $(document).ready(function () {
        $('#date_range, #loan_type, #customer_type').on('change', () => fetchReports());

        $('#date_range').on('change', function () {
            // Initialize datepickers but don't show them immediately

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            $("#from_date").datepicker({
                dateFormat: "yy-mm-dd",
                maxDate: 0, // disable today and future
                onSelect: function (selectedDate) {
                    const fromDate = $(this).datepicker("getDate");

                    // Set minDate for To Date to be the next day after From Date
                    const minToDate = new Date(fromDate);
                    minToDate.setDate(minToDate.getDate() + 1);

                    //$("#to_date").datepicker("option", "minDate", minToDate);
                    //$("#to_date").datepicker("option", "maxDate", today);

                    setTimeout(() => $("#to_date").datepicker("show"), 300);
                }
            });

            $("#to_date").datepicker({
                dateFormat: "yy-mm-dd",
                //maxDate: today
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
                fetchReports();
            }
        });

        $('#report_export').on('click', function () {
            const params = {
                date_range: $('#date_range').val(),
                from_date: $('#from_date').val(),
                to_date: $('#to_date').val(),
                export: 'csv'
            };
            const query = $.param(params);
            window.location.href = "{{ route('admin.osreport.findex') }}?" + query;
        });
    });
    </script>
@endpush