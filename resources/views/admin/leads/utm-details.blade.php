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
                        <form id="filterForm" method="GET" action="{{ route('admin.utm.tracking') }}">
                            <div class="filter-row">
                                <select id="date_range" name="date_range" class="form-control">
                                    <option value="">Select Date Range</option>
                                    <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Today</option>
                                    <option value="yesterday" {{ request('date_range') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                    <option value="last_3_days" {{ request('date_range') == 'last_3_days' ? 'selected' : '' }}>Last 3 Days</option>
                                    <option value="last_7_days" {{ request('date_range') == 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                                    <option value="last_15_days" {{ request('date_range') == 'last_15_days' ? 'selected' : '' }}>Last 15 Days</option>
                                    <option value="current_month" {{ request('date_range') == 'current_month' ? 'selected' : '' }}>Current Month</option>
                                    <option value="previous_month" {{ request('date_range') == 'previous_month' ? 'selected' : '' }}>Previous Month</option>
                                    <option value="custom">Custom Range</option>
                                </select>

                                <select id="source" name="source" class="form-control">
                                    <option value="">Select Source</option>
                                    <option value="fb" {{ request('source') == 'fb' ? 'selected' : '' }}>Facebook</option>
                                    <option value="google" {{ request('source') == 'google' ? 'selected' : '' }}>Google</option>
                                    <option value="vortexia" {{ request('source') == 'vortexia' ? 'selected' : '' }}>Vortexia</option>
                                    <option value="digisoftnexus" {{ request('source') == 'digisoftnexus' ? 'selected' : '' }}>Digisoftnexus</option>
                                </select>

                                <select name="campaign_id" id="campaign_id" class="form-control">
                                    <option value="">All</option>
                                    @if(!empty($campaignIds))
                                        @foreach($campaignIds as $id)
                                            <option value="{{ $id }}" {{ request('campaign_id') == $id ? 'selected' : '' }}>
                                                {{ $id }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                
                                <select id="utm_records" name="utm_records" class="form-control">
                                    <option value="">Select UTM Records</option>
                                    <option value="tur" {{ request('utm_records') == 'tur' ? 'selected' : '' }}>Total UTM Records</option>
                                    <option value="tusr" {{ request('utm_records') == 'tusr' ? 'selected' : '' }}>Total Userwise Records</option>
                                    <option value="tca" {{ request('utm_records') == 'tca' ? 'selected' : '' }}>Total Complete Application</option>
                                    <option value="taa" {{ request('utm_records') == 'taa' ? 'selected' : '' }}>Total Approved Application</option>
                                    <option value="tra" {{ request('utm_records') == 'tra' ? 'selected' : '' }}>Total Rejected Application</option>
                                    <option value="tda" {{ request('utm_records') == 'tda' ? 'selected' : '' }}>Total Disbursed Application</option>
                                </select>

                                <input type="text" id="utm-search-input" value="{{ request('search') }}" class="form-control" 
                                placeholder="Search by Mobile No" 
                                style="max-width: 400px;">

                                <input type="text" id="total_records" placeholder="Total Records"  class="form-control" value="{{ $totalRecords }}" readonly >

                                <button type="button" id="utm_export" class="btn btn-primary form-control">Export CSV</button>
                            </div>

                            {{-- Custom Date Row --}}
                            <div id="customDateSection" class="custom-date-container" style="margin-top: 10px;">
                                <div class="section-heading">Select Custom Date Range:</div>
                                <div class="flex gap-3">
                                    <input type="text" id="from_date" name="from_date" class="datepicker" placeholder="From Date" autocomplete="off" />
                                    <input type="text" id="to_date" name="to_date" class="datepicker" placeholder="To Date" autocomplete="off" />
                                </div>
                            </div>

                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Apply Filter</button>
                                <a href="{{ route('admin.utm.tracking') }}" class="btn btn-secondary ml-2">Reset</a>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>
                                <th>@lang('Date')</th>
                                <th>@lang('User')</th>
                                <th>@lang('Source')</th>
                                <th>@lang('Medium')</th>
                                <th>@lang('Campaign')</th>
                                <th>@lang('Term')</th>
                                <th>@lang('Content')</th>
                                <th>@lang('Landing Page')</th>
                                <th>@lang('IP Address')</th>
                                <th>@lang('Device Info')</th>
                            </tr>
                            </thead>
                            <tbody id="utmTable">
                            @forelse($utmRecords as $record)
                                <tr>
                                    <td data-label="@lang('Date')">
                                        {{ showDateTime(\Carbon\Carbon::parse($record->created_at)) }}<br>
                                        {{ \Carbon\Carbon::parse($record->created_at)->diffForHumans() }}
                                    </td>
                                    <td data-label="@lang('User')">
                                        @if($record->user_id)
                                            <a href="{{ route('admin.users.detail', $record->user_id) }}">
                                                {{ $record->firstname }} {{ $record->lastname }}<br>
                                                {{ $record->mobile }}<br>
                                                {{ $record->email }}
                                            </a>
                                        @else
                                            @lang('Anonymous')
                                        @endif
                                    </td>
                                    <td data-label="@lang('Source')">{{ $record->utm_source ?? 'N/A' }}</td>
                                    <td data-label="@lang('Medium')">{{ $record->utm_medium ?? 'N/A' }}</td>
                                    <td data-label="@lang('Campaign')">{{ $record->utm_campaign ?? 'N/A' }}</td>
                                    <td data-label="@lang('Term')">{{ $record->utm_term ?? 'N/A' }}</td>
                                    <td data-label="@lang('Content')">{{ $record->utm_content ?? 'N/A' }}</td>
                                    <td data-label="@lang('Landing Page')">
                                        @if($record->landing_page)
                                            <a href="{{ $record->landing_page }}" target="_blank" rel="noopener noreferrer">
                                                View Page
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td data-label="@lang('IP Address')">{{ $record->ip_address }}</td>
                                    <td data-label="@lang('Device Info')">
                                        @php
                                            // Simple truncation without str_limit()
                                            $ua = $record->user_agent;
                                            $displayUA = strlen($ua) > 50 ? substr($ua, 0, 50).'...' : $ua;
                                        @endphp
                                        {{ $displayUA }}
                                        @if(strlen($ua) > 50)
                                            <i class="fas fa-info-circle" title="{{ $ua }}"></i>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">@lang('No UTM data found')</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                        <br>
                        <br>
                    </div>
                    
                        <div class="sticky-pagination" id="utmpaginationLinks">
                            {{ paginateLinks($utmRecords) }}
                        </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder="Search by source, medium, campaign or user" />
@endpush

@push('script')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-slimScroll/1.3.8/jquery.slimscroll.min.js"></script>
<script>
    // Add tooltip for full user agent string
    $(document).ready(function(){
        $('.fa-info-circle').tooltip();
    });
</script>
<script>
    function fetchUTMLeads(page = 1) {
        const dateRange = $('#date_range').val();
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        const utm_records = $('#utm_records').val();
        const source = $('#source').val();
        const campaign_id = $('#campaign_id').val();
        const searchTerm = $('#utm-search-input').val();

        $.ajax({
            url: "{{ route('admin.utm.tracking') }}",
            type: "GET",
            data: {
                page: page,
                date_range: dateRange,
                from_date: fromDate,
                to_date: toDate,
                utm_records: utm_records,
                source : source,
                campaign_id : campaign_id,
                search: searchTerm
            },
            success: function(response) {
                $('#utmTable').html($(response).find('#utmTable').html());
                $('#utmpaginationLinks').html($(response).find('#utmpaginationLinks').html());
                $('#total_records').val($(response).find('#total_records').val());
            }
        });
    }

    $(document).ready(function () {

    });

        //$('#date_range, #utm_records, #source, #campaign_id').on('change', () => fetchUTMLeads());
        //$('#utm-search-input').on('keyup', () => fetchUTMLeads());

        $('#date_range').on('change', function () {
            // Initialize datepickers but don't show them immediately

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            $("#from_date").datepicker({
                dateFormat: "yy-mm-dd",
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
                dateFormat: "yy-mm-dd"
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

        /*$('#from_date, #to_date').on('change', function () {
            const fromDate = $('#from_date').val();
            const toDate = $('#to_date').val();
            if (fromDate && toDate && new Date(toDate) >= new Date(fromDate)) {
                fetchUTMLeads();
            }
        }); */

        $('#utmpaginationLinks').on('click', '.pagination .page-item .page-link', function (e) {
            e.preventDefault();

            const href = $(this).attr('href');
            //console.log('test', href);
            if (!href || href === '#') return;

            try {
                const url = new URL(href, window.location.origin);
                const page = url.searchParams.get("page") || 1;
                fetchUTMLeads(page);
            } catch (error) {
                console.error("Invalid pagination URL", error);
            }
        });

        $('#utm_export').on('click', function () {
            const params = {
                date_range: $('#date_range').val(),
                from_date: $('#from_date').val(),
                to_date: $('#to_date').val(),
                utm_records: $('#utm_records').val(),
                source: $('#source').val(),
                campaign_id:$('#campaign_id').val(),
                search: $('#utm-search-input').val(),
                export: 'csv'
            };
            const query = $.param(params);
            window.location.href = "{{ route('admin.utm.tracking') }}?" + query;
        });

    $(document).ready(function () {
    $('#source').on('change', function () {
        let source = $(this).val();
        let selectedCampaignId = "{{ request('campaign_id') }}";
            $.ajax({
                url: "{{ route('admin.campaign.ids') }}",
                type: "GET",
                data: { source: source },
                success: function (data) {
                    let campaignSelect = $('#campaign_id');
                    campaignSelect.empty();
                    campaignSelect.append('<option value="">All</option>');

                    $.each(data, function (index, value) {
                        let isSelected = (value === selectedCampaignId) ? 'selected' : '';
                        campaignSelect.append('<option value="' + value + '" ' + isSelected + '>' + value + '</option>');
                    });
                }
            });
        });
    });
</script>
@endpush