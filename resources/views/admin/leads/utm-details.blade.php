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
                            <tbody>
                            @forelse($utmRecords as $record)
                                <tr>
                                    <td data-label="@lang('Date')">
                                        {{ showDateTime(\Carbon\Carbon::parse($record->created_at)->timezone('Asia/Kolkata')) }}<br>
                                        {{ \Carbon\Carbon::parse($record->created_at)->timezone('Asia/Kolkata')->diffForHumans() }}
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
                    </div>
                    
                    @if($utmRecords->hasPages())
                        <div class="card-footer py-4">
                            {{ paginateLinks($utmRecords) }}
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
</script>
@endpush