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
</style>
@endpush
@section('panel')
    <div class="row mb-none-30">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body">
                    <div class="d-flex justify-content-end mb-3">
                        <input type="text" id="d-closed-search-input" class="form-control" 
                            placeholder="Search by Name / Mobile No / Loan App No / Email..." 
                            style="max-width: 400px;">
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
<script>
        const searchInput = document.getElementById('d-closed-search-input');
        const dClosedTable = document.getElementById('dClosedTable');
        const dClosePaginationLinks = document.getElementById('dClosePaginationLinks');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout); // Clear previous timeout if user is still typing

            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                fetchLeads(searchTerm);
            }, 300); // Adjust the delay (in milliseconds) as needed
        });

        function fetchLeads(searchTerm) {
            const url = `/admin/decision/decision-closed?search=${searchTerm}`;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Create a temporary element to hold the HTML
                const tempElement = document.createElement('div');
                tempElement.innerHTML = html;

                // Find the tbody within the temporary element
                const newTbody = tempElement.querySelector('#dClosedTable');

                if (newTbody) {
                    dClosedTable.innerHTML = newTbody.innerHTML;
                } else {
                    dClosedTable.innerHTML = '<tr><td colspan="11">No data found.</td></tr>';
                }
                
                // Update pagination links
                if (searchTerm) {
                    dClosePaginationLinks.innerHTML = newPagination ? newPagination.innerHTML : '';
                } else {
                    dClosePaginationLinks.innerHTML = initialPaginationHTML;
                }
            })
            .catch(error => {
                console.error('Error fetching closed decision:', error);
            });
        }
    </script>
@endpush
