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
                        <input type="text" id="bsa-lead-search-input" class="form-control" 
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
                                <th>@lang('Purpose Of Loan')</th>
                                <th>@lang('Personal Details')</th>
                                <th>@lang('KYC Details')</th>
                                <th>@lang('Selfie Document')</th>
                                <th>@lang('Address Details')</th>
                                <th>@lang('Employement Details')</th>
                                <th>@lang('Bank Details')</th>
                            </tr>
                            </thead>
                            <tbody id="bsaLeadsTable">
                            @forelse($leads as $lead)
                                <tr>
                                    <td style="cursor: pointer">
                                        <a href="{{route('admin.leads.verify', $lead->id)}}"><i class="fas fa-eye"></i></a>
                                    </td>
                                    <td>{{ $lead->loan_no }}</td>
                                    <td>{{ $lead->user ? $lead->user->firstname . " " . $lead->user->lastname : '' }}</td>
                                    <td>{{ $lead->user ? $lead->user->mobile : '' }}</td>
                                    <td>{{ $lead->loan_amount }}</td>
                                    <td>{{ $lead->purpose_of_loan }}</td>
                                    <td>{!! $lead->personalDetails ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' !!}</td>
                                    <td>{!! $lead->kycDetails || in_array($lead->user_id, $userIdsWithKyc) ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' !!}</td>
                                    <td>{!! $lead->loanDocument || in_array($lead->user_id, $userIdsWithKyc) ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' !!}</td>
                                    <td>{!! $lead->addressDetails ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' !!}</td>
                                    <td>{!! $lead->employmentDetails ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' !!}</td>
                                    <td>{!! $lead->bankDetails ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' !!}</td>
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
                        <div class="sticky-pagination" id="bsapaginationLinks">
                            {{ $leads->links() }}
                        </div>
                    </div>
                </div>
                {{-- @if ($leads->hasPages())
                    <div class="card-footer py-4">
                        {{ $leads->links() }}
                    </div>
                @endif --}}
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
        const searchInput = document.getElementById('bsa-lead-search-input');
        const bsaLeadsTable = document.getElementById('bsaLeadsTable');
        const bsapaginationLinks = document.getElementById('bsapaginationLinks');
        let searchTimeout;
        let initialPaginationHTML = bsapaginationLinks.innerHTML;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout); // Clear previous timeout if user is still typing

            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                fetchBsaLeads(searchTerm);
            }, 300); // Adjust the delay (in milliseconds) as needed
        });

        function fetchBsaLeads(searchTerm) {
            const url = `/admin/leads/leads-bsa?search=${searchTerm}`;

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
                const newTbody = tempElement.querySelector('#bsaLeadsTable');

                if (newTbody) {
                    bsaLeadsTable.innerHTML = newTbody.innerHTML;
                } else {
                    bsaLeadsTable.innerHTML = '<tr><td colspan="11">No data found.</td></tr>';
                }
                
                // Update pagination links
                if (searchTerm) {
                    bsapaginationLinks.innerHTML = newPagination ? newPagination.innerHTML : '';
                } else {
                    bsapaginationLinks.innerHTML = initialPaginationHTML;
                }
            })
            .catch(error => {
                console.error('Error fetching leads:', error);
            });
        }
    </script>
@endpush