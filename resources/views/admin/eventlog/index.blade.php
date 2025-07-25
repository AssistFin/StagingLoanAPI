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
                        <input type="text" id="d-approved-search-input" class="form-control" 
                            placeholder="Search by Name / Mobile No / Loan App No / Email..." 
                            style="max-width: 400px;">
                    </div>
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>
                                <th>@lang('Sr No.')</th>
                                <th>@lang('Employee Name')</th>
                                <th>@lang('User Name')</th>
                                <th>@lang('Event')</th>
                                <th>@lang('Event Date Time')</th>
                            </tr>
                            </thead>
                            <tbody id="dApprovedTable">
                            @forelse($userRecords as $index => $eventlog)
                                <tr>
                                    <td>{{ $userRecords->firstItem() + $index }}</td>
                                    <td>{{ $eventlog->admin_id ? $eventlog->admin->name : '' }}</td>
                                    <td>{{ ($eventlog->user_id && isset($eventlog->user->firstname)) ? $eventlog->user->firstname : $eventlog->admin->name }}</td>
                                    <td>{{ $eventlog->event ? $eventlog->event : '' }}</td>
                                    <td>{{ $eventlog->created_at->format('d-m-Y H:i') }}</td>
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
                        <div class="sticky-pagination" id="dApprovePaginationLinks">
                            {{ $userRecords->links() }}
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
        const searchInput = document.getElementById('d-approved-search-input');
        const dApprovedTable = document.getElementById('dApprovedTable');
        const dApprovePaginationLinks = document.getElementById('dApprovePaginationLinks');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout); // Clear previous timeout if user is still typing

            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                fetchLeads(searchTerm);
            }, 300); // Adjust the delay (in milliseconds) as needed
        });

        function fetchLeads(searchTerm) {
            const url = `/admin/decision/decision-approved?search=${searchTerm}`;

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
                const newTbody = tempElement.querySelector('#dApprovedTable');

                if (newTbody) {
                    dApprovedTable.innerHTML = newTbody.innerHTML;
                } else {
                    dApprovedTable.innerHTML = '<tr><td colspan="11">No data found.</td></tr>';
                }
                
                // Update pagination links
                if (searchTerm) {
                    dApprovePaginationLinks.innerHTML = newPagination ? newPagination.innerHTML : '';
                } else {
                    dApprovePaginationLinks.innerHTML = initialPaginationHTML;
                }
            })
            .catch(error => {
                console.error('Error fetching approved decision:', error);
            });
        }
    </script>
@endpush
