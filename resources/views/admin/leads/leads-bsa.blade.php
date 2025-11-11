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
<style>
    .modal-body .row {
        font-size: 15px;
    }
    .modal-body .fw-bold {
        color: #2c3e50;
    }
    .modal-body .alert-success {
        background-color: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
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
                                <!--th>@lang('Action')</th-->
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
                                    <!--td>
                                      @if(!empty($lead->digitapRequest->report_json_data) && $lead->digitapRequest->status == 'xlsx_report_saved' )
                                      <!--button class="btn btn-primary btn-check-status" data-lead-id="{{ $lead->id }}">Check Status</button-->
                                      @else
                                      {{ 'BSA Report Not Generated' }}
                                      @endif
                                      <!--button class="btn btn-primary btn-sm checkUWBtn"
                                      data-loan-id="{{ $lead->id }}"
                                      data-client-id="{{ $lead->user->id }}"
                                      data-client-name="{{ $lead->user ? $lead->user->firstname.' '.$lead->user->lastname : '' }}"
                                      data-loan-app-no="{{ $lead->loan_no }}"
                                      data-apply-date="{{ $lead->created_at->format('Y-m-d') }}"
                                      data-cpa-name="{{ auth()->guard('admin')->user()->name }}"
                                      data-bs-toggle="modal"
                                      data-bs-target="#underwritingModal" >Check UW</button></td-->
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
      <form id="underwritingForm" method="POST" action="{{ route('admin.leads.underwriting-store') }}">
        @csrf
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
                <input type="text" class="form-control" id="cpa_name" name="cpa_name" readonly >
              </div>
              <div class="col-md-3">
                <label>Loan App. No.</label>
                <input type="text" name="loan_app_no" id="loan_app_no" class="form-control" readonly>
              </div>
              <div class="col-md-3">
                <label>Client Name</label>
                <input type="text" name="client_name" id="client_name" class="form-control" readonly>
              </div>
              <div class="col-md-3">
                <label>Apply Date</label>
                <input type="text" name="apply_date" id="apply_date" class="form-control" readonly>
                <input type="hidden" name="admin_id" id="admin_id" value="{{ auth()->guard('admin')->user()->id }}" >
                <input type="hidden" name="loan_id" id="loan_id">
                <input type="hidden" name="user_id" id="user_id">
              </div>
            </div>

            <!-- BANKING SECTION -->
            <h5 class="mt-4 mb-2 text-primary border-bottom pb-1">Banking</h5>

            <div class="row mb-3">
              <div class="col-md-12"><strong>Salary in Last 3 Months</strong></div>
              <div class="col-md-2"><input type="number" class="form-control" name="salary_month1" id="salary1" value="" required placeholder="3000"></div>
              <div class="col-md-2"><input type="number" class="form-control" name="salary_month2" id="salary2" value="" required placeholder="4000"></div>
              <div class="col-md-2"><input type="number" class="form-control" name="salary_month3" id="salary3" value="" required placeholder="4000"></div>
              <div class="col-md-3"><input type="number" class="form-control" name="average_salary" id="avg_salary" value="" readonly placeholder="3000"></div>
              <div class="col-md-3"><input type="text" class="form-control" name="bank_score" id="bank_score" value="" required placeholder="Bank Score"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-3"><label>Min Balance</label><input type="text" class="form-control" name="min_balance" id="min_balance" required></div>
              <div class="col-md-3"><label>Avg Balance</label><input type="text" class="form-control" name="avg_balance" id="avg_balance" required></div>
              <div class="col-md-3"><label>Bounce/Return (Last 1 Month)</label><input type="text" class="form-control" name="bounce_1_month" id="bounce_1_month" required></div>
              <div class="col-md-3"><label>Bounce/Return (Last 3 Months)</label><input type="text" class="form-control" name="bounce_3_month" id="bounce_3_month" required></div>
            </div>

            <!-- BUREAU SECTION -->
            <h5 class="mt-4 mb-2 text-primary border-bottom pb-1">Bureau</h5>
            <div class="row mb-3">
              <div class="col-md-3"><label>Bureau Score</label><input type="text" class="form-control" name="bureau_score" id="bureau_score" required></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12"><strong>DPD in Last 30 Days</strong></div>
              <div class="col-md-1">1.</div>
              <div class="col-md-2"><input type="number" class="form-control" name="dpd_30_1" id="dpd_30_1" required placeholder="12"></div>
              <div class="col-md-2"><input type="number" class="form-control" name="dpd_30_amt1" id="dpd_30_amt1" required placeholder="240000"></div>
              <div class="col-md-2"></div>
              <div class="col-md-1">2.</div>
              <div class="col-md-2"><input type="number" class="form-control" name="dpd_30_2" id="dpd_30_2" required placeholder="32"></div>
              <div class="col-md-2"><input type="number" class="form-control" name="dpd_30_amt2" id="dpd_30_amt2" required placeholder="420000"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12"><strong>DPD in Last 90 Days</strong></div>
              <div class="col-md-1">1.</div>
              <div class="col-md-2"><input type="number" class="form-control" name="dpd_90_1" id="dpd_90_1" required placeholder="23"></div>
              <div class="col-md-2"><input type="number" class="form-control" name="dpd_90_amt1" id="dpd_90_amt1" required placeholder="340000"></div>
              <div class="col-md-2"></div>
              <div class="col-md-1">2.</div>
              <div class="col-md-2"><input type="number" class="form-control" name="dpd_90_2" id="dpd_90_2" required placeholder="35"></div>
              <div class="col-md-2"><input type="number" class="form-control" name="dpd_90_amt2" id="dpd_90_amt2" required placeholder="300000"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-3"><label>Experience in Unsecured Loan (Last 6 months)</label>
                <select class="form-select" name="unsecured_loan_experience" id="unsecured_loan_experience" required>
                  <option value="No">No</option>
                  <option value="Yes">Yes</option>
                </select>
              </div>
              <div class="col-md-3"><label>No. of Loan Accounts Open (Last 6 months)</label><input type="number" class="form-control" name="loan_open_6m" id="loan_open_6m" required ></div>
              <div class="col-md-3"><label>No. of Loan Accounts Closed (Last 6 months)</label><input type="number" class="form-control" name="loan_closed_6m" id="loan_closed_6m" required ></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12"><strong>Amount of Last 2 Loan Open</strong></div>
              <div class="col-md-4"><input type="number" class="form-control" name="last2_open_1" id="last2_open_1" required placeholder="1"></div>
              <div class="col-md-4"><input type="number" class="form-control" name="last2_open_2" id="last2_open_2" required placeholder="2"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12"><strong>Last Unsecured Loan Closed</strong></div>
              <div class="col-md-4"><input type="number" class="form-control" name="last2_closed_1" id="last2_closed_1" required placeholder="1"></div>
              <div class="col-md-4"><input type="number" class="form-control" name="last2_closed_2" id="last2_closed_2" required placeholder="2"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12"><strong>Leverage</strong></div>
              <div class="col-md-4"><input type="number" class="form-control" name="leverage_avg_salary" id="leverage_avg_salary" required placeholder="4560060"></div>
              <div class="col-md-4"><input type="number" class="form-control" name="leverage_unsecured_loan" id="leverage_unsecured_loan" required placeholder="45670000"></div>
            </div>

          </div> <!-- /.container-fluid -->
        </div> <!-- /.modal-body -->

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" id="submitBtn" class="btn btn-success">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="decisionModal" tabindex="-1" aria-labelledby="decisionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="decisionModalLabel">Underwriting Decision</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <h4 id="decisionText"></h4>
        <p id="eligibleAmountText"></p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ✅ Modal -->
<div class="modal fade" id="analyzeModal" tabindex="-1" aria-labelledby="analyzeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="analyzeModalLabel">Check Status Result</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="analyzeModalBody">
        <p class="text-center text-muted">Click "Check Status" to start analysis.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
  function calculateAverageSalary() {
    const s1 = parseFloat(document.getElementById('salary1').value) || 0;
    const s2 = parseFloat(document.getElementById('salary2').value) || 0;
    const s3 = parseFloat(document.getElementById('salary3').value) || 0;

    if (s1 > 0 && s2 > 0 && s3 > 0) {
      // Find the minimum of 3 months
      const minSalary = Math.min(s1, s2, s3);
      document.getElementById('avg_salary').value = minSalary;
    } else {
      document.getElementById('avg_salary').value = '';
    }
  }

  // Attach event listeners for real-time update
  document.getElementById('salary1').addEventListener('input', calculateAverageSalary);
  document.getElementById('salary2').addEventListener('input', calculateAverageSalary);
  document.getElementById('salary3').addEventListener('input', calculateAverageSalary);
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const buttons = document.querySelectorAll('.checkUWBtn');

  buttons.forEach(button => {
    button.addEventListener('click', function() {
      // Get data attributes
      const loanId = this.dataset.loanId;
      const clientId = this.dataset.clientId;
      const clientName = this.dataset.clientName;
      const loanAppNo = this.dataset.loanAppNo;
      const applyDate = this.dataset.applyDate;
      const cpaName = this.dataset.cpaName;

      // Fill modal fields
      document.getElementById('loan_id').value = loanId;
      document.getElementById('user_id').value = clientId;
      document.getElementById('client_name').value = clientName;
      document.getElementById('loan_app_no').value = loanAppNo;
      document.getElementById('apply_date').value = applyDate;
      document.getElementById('cpa_name').value = cpaName;
    });
  });
});
</script>
<script>
    document.getElementById('underwritingForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);

      const tokenMeta = document.querySelector('meta[name="csrf-token"]');
      const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

      fetch("{{ route('admin.leads.underwriting-store') }}", {
        method: "POST",
        headers: {
          "X-CSRF-TOKEN": csrfToken
        },
        body: formData
      })
      .then(response => response.text())
      .then(text => {
        console.log("Raw Response:", text); // log it
        try {
          const data = JSON.parse(text); // try parsing JSON
          if (data.status === 'success') {
            const underwritingModal = bootstrap.Modal.getInstance(document.getElementById('underwritingModal'));
            underwritingModal.hide();

            // Wait until it's fully hidden before showing the decision modal
            $('#underwritingModal').on('hidden.bs.modal', function () {
              showDecisionModal(data.decision, data.eligible_amount);
              // Unbind the event so it doesn't trigger multiple times
              $(this).off('hidden.bs.modal');
            });
          }
        } catch (e) {
          console.error("Invalid JSON:", e);
          alert("Server returned unexpected data. Check console for details.");
        }
      })
      .catch(error => console.error('Error:', error));
    });

    function showDecisionModal(decision, eligibleAmount) {
      const decisionText = document.getElementById('decisionText');
      const eligibleAmountText = document.getElementById('eligibleAmountText');

      decisionText.innerHTML = decision === 'Approved'
        ? `<span class="text-success">✅ Loan Approved</span>`
        : `<span class="text-danger">❌ Loan Rejected</span>`;

      eligibleAmountText.innerHTML = decision === 'Approved'
        ? `Eligible Loan Amount : <strong> ₹ ${eligibleAmount}</strong>`
        : 'Applicant did not meet all underwriting criteria.';

      const decisionModal = new bootstrap.Modal(document.getElementById('decisionModal'));
      decisionModal.show();
    }

</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const analyzeModal = new bootstrap.Modal(document.getElementById('analyzeModal'));
    const modalBody = document.getElementById('analyzeModalBody');

    // Handle click on any "Check Status" button
    document.querySelectorAll('.btn-check-status').forEach(btn => {
        btn.addEventListener('click', function() {
            const leadId = this.dataset.leadId;

            // Show loading state in modal
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Checking Digitap Status for Lead #${leadId}...</p>
                </div>
            `;
            analyzeModal.show();

            // Make AJAX POST request
            fetch(`/admin/digitap/analyze/${leadId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    const result = data.data || {};

                    let html = `
                    <div class="alert alert-success d-flex align-items-center mb-3" style="font-weight:600;">
                        <i class="bi bi-check-circle-fill me-2"></i> 
                        ${data.message}
                    </div>
                    `;

                    // ✅ DIGITAP DATA
                    if (result.digitap) {
                        html += `
                        <div class="p-3 rounded mb-3" style="background-color:#e8f5e9;">
                            <h6 class="fw-bold mb-2 text-success">Digitap Report</h6>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-secondary">Approval Amount :</div>
                                <div class="col-7 text-dark">${result.digitap.approved_amount ?? '-'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-secondary">Tag :</div>
                                <div class="col-7 text-dark">${result.digitap.salary_or_business_tag ?? '-'}</div>
                            </div>
                            <div class="row">
                                <div class="col-5 fw-bold text-secondary">Reason :</div>
                                <div class="col-7 text-dark">${result.digitap.rejected_reason ?? '-'}</div>
                            </div>
                        </div>
                        `;
                    }

                    // ✅ EXPERIAN DATA
                    if (result.experian) {
                        html += `
                        <div class="p-3 rounded mb-3" style="background-color:#e3f2fd;">
                            <h6 class="fw-bold mb-2 text-primary">Experian Report</h6>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-secondary">Bureau Score :</div>
                                <div class="col-7 text-dark">${result.experian.score ?? '-'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-secondary">Approval Amount :</div>
                                <div class="col-7 text-dark">${result.experian.approved_amount ?? '-'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-secondary">Tag :</div>
                                <div class="col-7 text-dark">${result.experian.salary_or_business_tag ?? '-'}</div>
                            </div>
                            <div class="row">
                                <div class="col-5 fw-bold text-secondary">Reason :</div>
                                <div class="col-7 text-dark">${result.experian.rejected_reason ?? '-'}</div>
                            </div>
                        </div>
                        `;
                    }

                    // ✅ 3️⃣ MONTHLY SALARY × 0.35 CHECK
                    if (result.monthly_salary_check) {
                        html += `
                        <div class="p-3 rounded" style="background-color:#fff3cd;">
                            <h6 class="fw-bold mb-2 text-warning">Monthly Salary</h6>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-secondary">Bureau Score :</div>
                                <div class="col-7 text-dark">${result.monthly_salary_check.bureau_score ?? '-'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-secondary">Approval Amount :</div>
                                <div class="col-7 text-dark">${result.monthly_salary_check.approved_amount ?? '-'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-secondary">Tag :</div>
                                <div class="col-7 text-dark">${result.monthly_salary_check.salary_or_business_tag ?? '-'}</div>
                            </div>
                            <div class="row">
                                <div class="col-5 fw-bold text-secondary">Reason :</div>
                                <div class="col-7 text-dark">${result.monthly_salary_check.rejected_reason ?? '-'}</div>
                            </div>
                        </div>
                        `;
                    }

                    // ✅ FINAL RESULT
                    if (result.final) {
                        html += `
                        <div class="p-3 rounded" style="background-color:#e8f0fe;">
                            <h6 class="fw-bold mb-2 text-primary">Final Decision</h6>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-secondary">Final Approved Amount :</div>
                                <div class="col-7 text-dark">${result.final.final_approved_amount ?? '-'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-secondary">Decision :</div>
                                <div class="col-7 text-dark">${result.final.decision ?? '-'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-secondary">Reason :</div>
                                <div class="col-7 text-dark">${result.final.reason ?? '-'}</div>
                            </div>
                            <div class="row">
                                <div class="col-5 fw-bold text-secondary">Logic :</div>
                                <div class="col-7 text-dark">${result.final.logic ?? '-'}</div>
                            </div>
                        </div>
                        `;
                    }

                    modalBody.innerHTML = html;

                } else {
                    modalBody.innerHTML = `
                        <div class="alert alert-warning text-center">
                            ⚠️ ${data.message}
                        </div>
                    `;
                }
            })
            .catch(err => {
                modalBody.innerHTML = `
                    <div class="alert alert-danger text-center">
                        ❌ Error: ${err.message}
                    </div>
                `;
            });

        });
    });
});
</script>

@endpush