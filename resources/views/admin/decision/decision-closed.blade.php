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

                            <input type="text" id="d-closed-search-input" class="form-control" placeholder="Search by Name / Mobile No / Loan App No / Email..." style="max-width: 400px;">

                            <button type="button" id="all_closed_export" class="btn btn-primary form-control">Export CSV</button>
                        
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
                                <th>@lang('Loan Application No.')</th>
                                <th>@lang('Customer Name')</th>
                                <th>@lang('Mobile No')</th>
                                <th>@lang('Loan Amount')</th>
                                <th>@lang('Total Collection Amt')</th>
                                <th>@lang('Closed Date')</th>
                                <th>@lang('Status')</th>
                            </tr>
                            </thead>
                            <tbody id="dClosedTable">
                                
                            @forelse($leads as $lead)
                                <tr>
                                    <td style="cursor: pointer">
                                        <a href="{{route('admin.leads.verify', $lead->id)}}"><i class="fas fa-eye"></i></a>
                                    </td>
                                    <td>{{ $lead->loan_no }}</td>
                                    <td>{{ $lead->user ? $lead->user->firstname . " " . $lead->user->lastname : '' }}</td>
                                    <td>{{ $lead->user ? $lead->user->mobile : '' }}</td>
                                    <td>{{ number_format($lead->loanApproval->approval_amount,0) }}</td>
                                    <td>{{ $lead->collections->sum('collection_amt') }}</td>
                                    <td>{{ optional($lead->collections->last())->collection_date }}</td>
                                    <td>{{ optional($lead->collections->last())->status }}</td>
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
                        <div class="sticky-pagination" id="dClosePaginationLinks">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js">
</script>
<script>
        const searchInput = document.getElementById('d-closed-search-input');
        const dateRangeSelect = document.getElementById('date_range');
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');
        const customDateSection = document.getElementById('customDateSection');
        const dClosedTable = document.getElementById('dClosedTable');
        const dClosePaginationLinks = document.getElementById('dClosePaginationLinks');
        let searchTimeout;
        let selectedRange = '';
        let fromDate = '';
        let toDate = '';

        dateRangeSelect.addEventListener('change', function() {
            selectedRange = this.value;

            if (selectedRange == 'custom') {
                customDateSection.style.display = 'block';
            } else {
                customDateSection.style.display = 'none';
                fromDateInput.value = '';
                toDateInput.value = '';
                fromDate = '';
                toDate = '';
                fetchLeads(searchInput.value.trim()); // Trigger fetch when changing date range
            }
        });

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout); // Clear previous timeout if user is still typing

            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                fetchLeads(searchTerm);
            }, 300); // Adjust the delay (in milliseconds) as needed
        });

        function fetchLeads(searchTerm = '') {
            const params = new URLSearchParams({
                search: searchTerm,
                date_range: selectedRange,
                from_date: fromDate,
                to_date: toDate
            });
            //console.log({ searchTerm, selectedRange, fromDate, toDate });
            const url = `/admin/decision/decision-closed?${params.toString()}`;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const tempElement = document.createElement('div');
                tempElement.innerHTML = html;

                const newTbody = tempElement.querySelector('#dClosedTable');
                const newPagination = tempElement.querySelector('#dClosePaginationLinks');

                dClosedTable.innerHTML = newTbody ? newTbody.innerHTML : '<tr><td colspan="11">No data found.</td></tr>';
                dClosePaginationLinks.innerHTML = newPagination ? newPagination.innerHTML : '';
            })
            .catch(error => {
                console.error('Error fetching filtered leads:', error);
            });
        }

        // CSV Export
        $(document).ready(function () {

            // Initialize datepicker + custom date logic
            $('#from_date, #to_date').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            }).on('changeDate', function () {
                fromDate = $('#from_date').val();
                toDate = $('#to_date').val();

                if (fromDate && toDate) {
                    fetchLeads($('#d-closed-search-input').val().trim());
                }
            });

            $('#all_closed_export').on('click', function () {
                const params = {
                    search: $('#d-closed-search-input').val(),
                    date_range: $('#date_range').val(),
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    export: 'csv'
                };
                const query = $.param(params);
                window.location.href = "{{ route('admin.decision.closed') }}?" + query;
            });

            // Initialize Bootstrap datepickers
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            }).on('changeDate', function (e) {
                // Trigger input event manually when date selected
                $(this).trigger('change');
            });
        });
    </script>
@endpush
