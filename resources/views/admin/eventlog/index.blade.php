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
                            
                            <select id="employee" name="employee" class="form-control">
                                <option value="">Select Employee</option>
                                @foreach($adminData as $aData)
                                    <option value="{{ $aData->id }}">{{ $aData->name }}</option>
                                @endforeach
                            </select>

                            <select id="customer" name="customer" class="form-control select2">
                                <option value="">Select Customer</option>
                                @foreach($userData as $uData)
                                    <option value="{{ $uData->id }}">{{ $uData->firstname }}  {{ $uData->lastname }}</option>
                                @endforeach
                            </select>

                            <select id="event" name="event" class="form-control">
                                <option value="">Select Event</option>
                                <option value="Login">Login</option>
                                <option value="Logout">Logout</option>
                                <option value="Loan Approval">Loan Approval</option>
                                <option value="Loan Disbursed">Loan Disbursed</option>
                                <option value="Loan Closed">Loan Closed</option>
                                <option value="Loan Part Payment">Loan Part Payment</option>
                                <option value="Loan Settlement">Loan Settlement</option>
                            </select>

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
                                <th>@lang('Sr No.')</th>
                                <th>@lang('Employee Name')</th>
                                <th>@lang('User Name')</th>
                                <th>@lang('Event')</th>
                                <th>@lang('Description')</th>
                                <th>@lang('Event Date Time')</th>
                            </tr>
                            </thead>
                            <tbody id="eventTable">
                            @forelse($eventRecords as $index => $eventlog)
                                <tr>
                                    <td>{{ $eventRecords->firstItem() + $index }}</td>
                                    <td>{{ $eventlog->admin_id ? $eventlog->admin->name : '' }}</td>
                                    <td>{{ ($eventlog->user_id && isset($eventlog->user->firstname)) ? $eventlog->user->firstname : $eventlog->admin->name }}</td>
                                    <td>{{ $eventlog->event ? $eventlog->event : '' }}</td>
                                    <td><button class="btn btn-info view-btn" data-id="{{ $eventlog->id }}" 
                                        data-json='@json($eventlog->description)' >View</button></td>
                                    <td>{{ $eventlog->created_at->format('d-m-Y H:i:s') }}</td>
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
                        <div class="sticky-pagination" id="eventPaginationLinks">
                            {{ $eventRecords->links() }}
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<div class="modal fade" id="jsonModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Event Description</h5>
        <!--button type="button" class="close" data-dismiss="modal">&times;</button-->
      </div>
      <div class="modal-body" id="jsonTableData">
        <!-- JSON data will be rendered here -->
      </div>
    </div>
  </div>
</div>
@push('script')

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-slimScroll/1.3.8/jquery.slimscroll.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).on('click', '.view-btn', function () {
        let rawJsonData = $(this).data('json'); // Get the raw data from the data attribute

        // Log the raw data and its type for debugging purposes
        //console.log("Raw data from data-json:", rawJsonData);
        //console.log("Type of raw data:", typeof rawJsonData);

        let dataToDisplay;

        // Check for the specific success messages first, which are plain strings
        if (rawJsonData === 'Logged Out Successfully.' || rawJsonData === 'Logged In Successfully.') {
            dataToDisplay = rawJsonData; // It's a simple string, display as is
        } else if (typeof rawJsonData === 'string') {
            // If it's a string and not a success message, attempt to parse it as JSON
            try {
                dataToDisplay = JSON.parse(rawJsonData);
            } catch (e) {
                // If JSON parsing fails, log the error and treat the raw string as the data to display.
                // This handles cases where the 'description' might not always be valid JSON.
                console.error("Error parsing JSON string:", e);
                dataToDisplay = rawJsonData; // Fallback to displaying the raw string
                $('#jsonTableData').html('<div class="alert alert-warning">Data is not valid JSON, displaying as plain text.</div>');
                $('#jsonModal').modal('show');
                return; // Exit the function as we've already displayed a warning and content
            }
        } else if (typeof rawJsonData === 'object' && rawJsonData !== null) {
            // If jQuery's .data() method has already parsed it into an object, use it directly
            dataToDisplay = rawJsonData;
        } else {
            // Fallback for any other unexpected data types (e.g., undefined, number, boolean)
            console.warn("Unexpected data type for description:", typeof rawJsonData, rawJsonData);
            $('#jsonTableData').html('<div class="alert alert-warning">Unexpected data format.</div>');
            $('#jsonModal').modal('show');
            return; // Exit the function
        }

        // Now, render the data based on whether dataToDisplay is a string or an object
        if (typeof dataToDisplay === 'string') {
            // If it's a string (like "Logged In Successfully."), display it in a paragraph
            let html = `<p>${dataToDisplay}</p>`;
            $('#jsonTableData').html(html);
            $('#jsonModal').modal('show');
        } else if (typeof dataToDisplay === 'object' && dataToDisplay !== null) {
            // If it's a valid JSON object, create a table to display its key-value pairs
            let html = `<table class="table table-bordered table-striped">`;
            html += `<thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>`;

            // Iterate over the object's entries and create table rows
            Object.entries(dataToDisplay).forEach(([key, value]) => {
                // Handle null or undefined values to display as an empty string for better readability
                const displayValue = (value === null || value === undefined) ? '' : value;
                html += `<tr><td>${key}</td><td>${displayValue}</td></tr>`;
            });

            html += `</tbody></table>`;
            $('#jsonTableData').html(html);
            $('#jsonModal').modal('show');
        } else {
            // This case should ideally not be reached if the above logic is sound
            $('#jsonTableData').html('<div class="alert alert-danger">Could not display data.</div>');
            $('#jsonModal').modal('show');
        }
    });

    $(document).ready(function() {
        $('#customer').select2({
            placeholder: 'Select Customer',
            allowClear: true,
            width: '100%' // Ensures it fits the container
        });
    });

    function fetchEvents(page = 1) {
        const dateRange = $('#date_range').val();
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const employee = $('#employee').val();
        const customer = $('#customer').val();
        const event = $('#event').val();
        const searchTerm = $('#search-input').val();

        $.ajax({
            url: "{{ route('admin.eventlog.index') }}",
            type: "GET",
            data: {
                page: page,
                date_range: dateRange,
                from_date: fromDate,
                to_date: toDate,
                employee: employee,
                customer: customer,
                event: event,
                search: searchTerm
            },
            success: function(response) {
                $('#eventTable').html($(response).find('#eventTable').html());
                $('#eventPaginationLinks').html($(response).find('#eventPaginationLinks').html());
            }
        });
    }

    $(document).ready(function () {
        $('#date_range, #employee, #customer, #event').on('change', () => fetchEvents());
        $('#search-input').on('keyup', () => fetchEvents());

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
                fetchEvents();
            }
        });

        $('#eventPaginationLinks').on('click', '.pagination .page-item .page-link', function (e) {
            e.preventDefault();

            const href = $(this).attr('href');
            //console.log('test', href);
            if (!href || href === '#') return;

            try {
                const url = new URL(href, window.location.origin);
                const page = url.searchParams.get("page") || 1;
                fetchEvents(page);
            } catch (error) {
                console.error("Invalid pagination URL", error);
            }
        });
    });
</script>
@endpush
