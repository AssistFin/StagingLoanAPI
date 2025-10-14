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
<style>
.modal-xl {
  max-width: 95%;
}
.modal-body {
  max-height: 75vh;
  overflow-y: auto;
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
                            style="max-width: 300px;">&nbsp;&nbsp;
                            @php
                                $allowedRoles = ['Admin', 'Superadmin', 'Sub Admin', 'Chief Technical Officer'];
                                $user = auth('admin')->user();
                                //dd(auth('admin'));
                            @endphp
                            @if($user && $user->roles()->whereIn('name', $allowedRoles)->exists())
                                <input type="text" id="total_records" placeholder="Total Records"  class="form-control" value="{{ $totalRecords }}" readonly >&nbsp;&nbsp;

                                <button type="button" id="bsa_lead_export" class="btn btn-primary form-control">Export CSV</button>
                            @endif
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
                                <th>@lang('Action')</th>
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
                                    <td><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#underwritingModal" >Check UW</button></td>
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

<!-- Modal -->
<div class="modal fade" id="underwritingModal" tabindex="-1" aria-labelledby="underwritingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form id="underwritingForm">
        <div class="modal-header">
          <h5 class="modal-title" id="underwritingModalLabel">Underwriting Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="container-fluid">
            
            <!-- Applicant Info -->
            <div class="row mb-3">
              <div class="col-md-3">
                <label>CPA Name</label>
                <input type="text" class="form-control" placeholder="13419">
              </div>
              <div class="col-md-3">
                <label>Loan App. No.</label>
                <input type="text" class="form-control" placeholder="Sample">
              </div>
              <div class="col-md-3">
                <label>Client Name</label>
                <input type="text" class="form-control" placeholder="Enter client name">
              </div>
              <div class="col-md-3">
                <label>Apply Date</label>
                <input type="date" class="form-control">
              </div>
            </div>

            <!-- BANKING SECTION -->
            <h5 class="mt-4 mb-2 text-primary border-bottom pb-1">Banking</h5>

            <div class="row mb-3">
              <div class="col-md-12"><strong>Salary in Last 3 Months</strong></div>
              <div class="col-md-2"><input type="number" class="form-control" placeholder="Month 1"></div>
              <div class="col-md-2"><input type="number" class="form-control" placeholder="Month 2"></div>
              <div class="col-md-2"><input type="number" class="form-control" placeholder="Month 3"></div>
              <div class="col-md-3"><input type="number" class="form-control" placeholder="Average"></div>
              <div class="col-md-3"><input type="text" class="form-control" placeholder="Bank Score"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-3"><label>Min Balance</label><input type="text" class="form-control"></div>
              <div class="col-md-3"><label>Avg Balance</label><input type="text" class="form-control"></div>
              <div class="col-md-3"><label>Bounce/Return (Last 1 Month)</label><input type="text" class="form-control"></div>
              <div class="col-md-3"><label>Bounce/Return (Last 3 Months)</label><input type="text" class="form-control"></div>
            </div>

            <!-- BUREAU SECTION -->
            <h5 class="mt-4 mb-2 text-primary border-bottom pb-1">Bureau</h5>
            <div class="row mb-3">
              <div class="col-md-3"><label>Bureau Score</label><input type="text" class="form-control"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12"><strong>DPD in Last 30 Days</strong></div>
              <div class="col-md-3"><input type="number" class="form-control" placeholder="Account Type"></div>
              <div class="col-md-3"><input type="number" class="form-control" placeholder="DPD"></div>
              <div class="col-md-3"><input type="number" class="form-control" placeholder="Amount"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12"><strong>DPD in Last 90 Days</strong></div>
              <div class="col-md-3"><input type="number" class="form-control" placeholder="Account Type"></div>
              <div class="col-md-3"><input type="number" class="form-control" placeholder="DPD"></div>
              <div class="col-md-3"><input type="number" class="form-control" placeholder="Amount"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-3"><label>Experience in Unsecured Loan (Last 6 months)</label>
                <select class="form-select">
                  <option>No</option>
                  <option>Yes</option>
                </select>
              </div>
              <div class="col-md-3"><label>No. of Loan Accounts Open (Last 6 months)</label><input type="number" class="form-control"></div>
              <div class="col-md-3"><label>No. of Loan Accounts Closed (Last 6 months)</label><input type="number" class="form-control"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12"><strong>Amount of Last 2 Loan Open</strong></div>
              <div class="col-md-4"><input type="number" class="form-control" placeholder="1"></div>
              <div class="col-md-4"><input type="number" class="form-control" placeholder="2"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12"><strong>Last Unsecured Loan Closed</strong></div>
              <div class="col-md-4"><input type="number" class="form-control" placeholder="1"></div>
              <div class="col-md-4"><input type="number" class="form-control" placeholder="2"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12"><strong>Leverage</strong></div>
              <div class="col-md-4"><input type="number" class="form-control" placeholder="Average Salary"></div>
              <div class="col-md-4"><input type="number" class="form-control" placeholder="Unsecured Loan"></div>
            </div>

          </div> <!-- /.container-fluid -->
        </div> <!-- /.modal-body -->

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Submit</button>
        </div>
      </form>
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

        $(document).ready(function () {
            $('#bsa_lead_export').on('click', function () {
                const params = {
                    search: $('#bsa-lead-search-input').val(),
                    export: 'csv'
                };
                const query = $.param(params);
                window.location.href = "{{ route('admin.leads.bsa') }}?" + query;
            });
        });
    </script>
<script>
    document.getElementById('underwritingForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default form submission
    // You can collect data here if needed, e.g. FormData(this)
    
    // Optional: show success message or close modal first
    const modal = bootstrap.Modal.getInstance(document.getElementById('underwritingModal'));
    modal.hide();

    // Redirect to Google
    window.location.href = "https://www.google.com";
    });
</script>

@endpush