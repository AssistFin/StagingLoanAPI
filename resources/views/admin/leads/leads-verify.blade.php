@extends('admin.layouts.app') 
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    td.text-break {
        word-break: break-word;
    }
</style>
@section('panel')
<div class="row gy-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="card-title d-flex justify-content-center gap-3">
                        <h6>
                            <i class="la @if ((isset($lead->kycDetails) && !empty($lead->kycDetails->pan_number)) || ($hasPreviousClosedLoan) ) la-check-circle text--success @else la-times-circle text--danger @endif"></i>
                            @lang('Pan')
                        </h6>
                        <h6>
                            <i class="la @if ((isset($lead->kycDetails) && $lead->kycDetails->aadhar_otp_verified == 1) || ($hasPreviousClosedLoan) ) la-check-circle text--success @else la-times-circle text--danger @endif"></i>
                            @lang('Aadhar')
                        </h6>
                        <h6>
                            <i class="la @if (isset($lead->personalDetails)) la-check-circle text--success @else la-times-circle text--danger @endif"></i>
                            @lang('Mobile')
                        </h6>
                    </div>
                </div>
                <div class="card-body text-center">
                    @if ($hasPreviousClosedLoan)
                            <button type="button" class="btn btn-danger">Existing Customer</button>
                    @endif
                </div>
                <div class="card-body text-center">
                    <img class="account-holder-image rounded border w-100" src="{{ isset($lead->loanDocument) ? url('/admin/secure-file/'.$lead->loanDocument->selfie_image) : asset('assets/admin/images/admin.png') }}" alt="account-holder-image" />
                </div>
                <div class="card-footer">
                    <div class="card-title">
                        <h6>Full Name: {{ isset($lead->user) ? $lead->user->firstname : "" }} {{isset($lead->user) ? $lead->user->lastname : ""}}</h6>
                        <h6>Email: {{ isset($lead->user) ? $lead->user->email : 'NA' }}</h6>
                        <h6>Mobile: {{ isset($lead->user) ? $lead->user->mobile : 'NA' }}</h6>
                        
                    </div>
                </div>
            </div>
        </div>
    
        <div class="col-md-9">
            @php
                $loanApprovalExists = $loanApproval ? true : false;
                $loanDisbursalExists = $loanDisbursal ? true : false;
                $userAcceptanceStatus = $lead["user_acceptance_status"] == "accepted" && $lead["user_acceptance_status"] != null ? true : false;
                $loanApprovalStatus = $lead["admin_approval_status"] == "pending" || $lead["admin_approval_status"] == "accepted" ? true : false;
            @endphp
            
            <nav>
                <div class="nav nav-tabs mb-3" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="loanDetails-tab" data-bs-toggle="tab" data-bs-target="#loanDetails" type="button" role="tab" aria-controls="loanDetails" aria-selected="true">Application</button>
                    
                    <button class="nav-link" id="Approval-tab" data-bs-toggle="tab" data-bs-target="#Approval" type="button" role="tab" aria-controls="Approval" aria-selected="false">Approval</button>
                    
                    <button class="nav-link {{ $loanApprovalExists && $userAcceptanceStatus ? '' : 'disabled' }}" id="Disbursal-tab" data-bs-toggle="tab" data-bs-target="#Disbursal" type="button" role="tab" aria-controls="Disbursal" aria-selected="false" {{ $loanApprovalExists && $userAcceptanceStatus ? '' : 'disabled' }}>Disbursal</button>
                    
                    <button class="nav-link {{ $loanDisbursalExists ? '' : 'disabled' }}" id="UTR-tab" data-bs-toggle="tab" data-bs-target="#UTR" type="button" role="tab" aria-controls="UTR" aria-selected="false" {{ $loanDisbursalExists ? '' : 'disabled' }}>Collection</button>
                    
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">History</button>

                    <button class="nav-link {{ $loanApprovalStatus ? '' : 'disabled' }}" id="Creditbureau-tab" data-bs-toggle="tab" data-bs-target="#Creditbureau" type="button" role="tab" aria-controls="Creditbureau" aria-selected="false" {{ $loanApprovalStatus ? '' : 'disabled' }}>Credit Bureau</button>

                    <button class="nav-link {{ $loanApprovalStatus ? '' : 'disabled' }}" id="Bsareport-tab" data-bs-toggle="tab" data-bs-target="#Bsareport" type="button" role="tab" aria-controls="Bsareport" aria-selected="false" {{ $loanApprovalStatus ? '' : 'disabled' }}>BSA Report</button>
                </div>
            </nav>
        
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="loanDetails" role="tabpanel" aria-labelledby="loanDetails-tab">
                    <div class="accordion" id="loanAccordion">
                
                        <!-- Loan Details -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingLoan">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLoan" aria-expanded="true" aria-controls="collapseLoan">
                                    Loan Details
                                </button>
                            </h2>
                            <div id="collapseLoan" class="accordion-collapse collapse show" aria-labelledby="headingLoan" data-bs-parent="#loanAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th>Loan Application No</th>
                                                    <td class="text-break">{{ $lead->loan_no }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Loan Amount</th>
                                                    <td class="text-break">{{ $lead->loan_amount }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Purpose of Loan</th>
                                                    <td class="text-break">{{ $lead->purpose_of_loan }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Created At</th>
                                                    <td class="text-break">{{ $lead->created_at->format('Y-m-d') }}</td>
                                                </tr>
                                            </tbody>
                                        </table>                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                
                        <!-- Personal Details -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingPersonal">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePersonal" aria-expanded="false" aria-controls="collapsePersonal">
                                    Personal Details
                                </button>
                            </h2>
                            <div id="collapsePersonal" class="accordion-collapse collapse" aria-labelledby="headingPersonal" data-bs-parent="#loanAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th>Pin Code</th>
                                                    <td class="text-break">{{ $lead->personalDetails->pin_code ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>City</th>
                                                    <td class="text-break">{{ $lead->personalDetails->city ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Employment Type</th>
                                                    <td class="text-break">{{ $lead->personalDetails->employment_type ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Monthly Income</th>
                                                    <td class="text-break">{{ $lead->personalDetails->monthly_income ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Income Received In</th>
                                                    <td class="text-break">{{ $lead->personalDetails->income_received_in ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Marital Status</th>
                                                    <td class="text-break">{{ $lead->employmentDetails->marital_status ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Education Qualification</th>
                                                    <td class="text-break">{{ $lead->employmentDetails->education_qualification ?? '' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                
                        <!-- Employment Details -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingEmployment">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEmployment" aria-expanded="false" aria-controls="collapseEmployment">
                                    Employment Details
                                </button>
                            </h2>
                            <div id="collapseEmployment" class="accordion-collapse collapse" aria-labelledby="headingEmployment" data-bs-parent="#loanAccordion">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th>Company Name</th>
                                                    <td class="text-break">{{ $lead->employmentDetails->company_name ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Designation</th>
                                                    <td class="text-break">{{ $lead->employmentDetails->designation ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Email</th>
                                                    <td class="text-break">{{ $lead->employmentDetails->email ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Office Address</th>
                                                    <td class="text-break">{{ $lead->employmentDetails->office_address ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Work Experience</th>
                                                    <td class="text-break">{{ $lead->employmentDetails->work_experience_years ?? '' }} years</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                
                        <!-- KYC Details -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingKYC">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKYC" aria-expanded="false" aria-controls="collapseKYC">
                                    KYC Details
                                </button>
                            </h2>
                            <div id="collapseKYC" class="accordion-collapse collapse" aria-labelledby="headingKYC" data-bs-parent="#loanAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h5>PAN Details</h5>
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th>PAN Number</th>
                                                    <td>{{ isset($panData->pan) ? $panData->pan : '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Full Name</th>
                                                    <td>{{ isset($panData->full_name) ? $panData->full_name : '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Date of Birth</th>
                                                    <td>{{ isset($panData->date_of_birth) ? \Carbon\Carbon::parse($panData->date_of_birth)->format('d/m/Y') : '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Status</th>
                                                    <td>{{ isset($panData->status) ? $panData->status : '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Aadhar Seeding Status</th>
                                                    <td>{{ isset($panData->aadhaar_seeding_status) && $panData->aadhaar_seeding_status == 'y' ? "Yes" : 'No' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                        
                                        <div class="col-md-12">
                                            <h5>Aadhaar Details</h5>
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th>Aadhar Number</th>
                                                    <td>{{ isset($lead->kycDetails->aadhar_number) ? $lead->kycDetails->aadhar_number : '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Full Name</th>
                                                    <td>{{ isset($aadharData->name) ? $aadharData->name : '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Care Of</th>
                                                    <td>{{ isset($aadharData->care_of) ? $aadharData->care_of : '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Date of Birth</th>
                                                    <td>{{ isset($aadharData->date_of_birth) ? $aadharData->date_of_birth : '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Gender</th>
                                                    <td>{{ isset($aadharData->gender) && $aadharData->gender == 'M' ? 'Male' : 'Female' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Address</th>
                                                    <td style="word-wrap: break-word; white-space: normal;">{{ $aadharData->full_address ?? 'Not Provided' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Status</th>
                                                    <td>{{ isset($aadharData->status) ? $aadharData->status : '' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
    
                        <!-- Selfie Document -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="selfieDocument">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSelefieDocument" aria-expanded="false" aria-controls="collapseSelefieDocument">
                                    Selfie Document
                                </button>
                            </h2>
                            <div id="collapseSelefieDocument" class="accordion-collapse collapse" aria-labelledby="selfieDocument" data-bs-parent="#loanAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-3"><strong>User Photo:</strong> 
                                            <img src="{{ isset($lead->loanDocument) ? url('/admin/secure-file/'.$lead->loanDocument->selfie_image) : "" }}" alt="" height="100px" width="100px">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
    
                         <!-- Address Details -->
                         <div class="accordion-item">
                            <h2 class="accordion-header" id="addressDetails">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAddressDetails" aria-expanded="false" aria-controls="collapseAddressDetails">
                                    Address Details
                                </button>
                            </h2>
                            <div id="collapseAddressDetails" class="accordion-collapse collapse" aria-labelledby="addressDetails" data-bs-parent="#loanAccordion">
                                <div class="accordion-body">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th>Address Type</th>
                                                <td class="text-break">{{ $lead->addressDetails->address_type ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th>House No</th>
                                                <td class="text-break">{{ $lead->addressDetails->house_no ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Locality</th>
                                                <td class="text-break">{{ $lead->addressDetails->locality ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Pincode</th>
                                                <td class="text-break">{{ $lead->addressDetails->pincode ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th>City</th>
                                                <td class="text-break">{{ $lead->addressDetails->city ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th>State</th>
                                                <td class="text-break">{{ $lead->addressDetails->state ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Relation</th>
                                                <td class="text-break">{{ $lead->addressDetails->relation ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Relative Name</th>
                                                <td class="text-break">{{ $lead->addressDetails->relative_name ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Contact Number</th>
                                                <td class="text-break">{{ $lead->addressDetails->contact_number ?? '' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>                                    
                                </div>
                            </div>
                        </div>
                
                        <!-- Bank Details -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingBank">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBank" aria-expanded="false" aria-controls="collapseBank">
                                    Bank Details
                                </button>
                            </h2>
                            <div id="collapseBank" class="accordion-collapse collapse" aria-labelledby="headingBank" data-bs-parent="#loanAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th>Bank Name</th>
                                                    <td class="text-break">{{ $lead->bankDetails->bank_name ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Account Number</th>
                                                    <td class="text-break">{{ $lead->bankDetails->account_number ?? 'Not Provided' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>IFSC Code</th>
                                                    <td class="text-break">{{ $lead->bankDetails->ifsc_code ?? 'Not Provided' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Password</th>
                                                    <td class="text-break">{{ $lead->bankDetails->bank_statement_password ?? 'Not Provided' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Bank Statement</th>
                                                    <td>
                                                        @php
                                                            $filename = isset($lead->bankDetails) ? basename($lead->bankDetails->bank_statement) : '';
                                                        @endphp
                                                        @if(isset($lead->bankDetails) && $lead->bankDetails->bank_statement)
                                                            <a href="{{ url('/admin/secure-document/'.$filename) }}" target="_blank">
                                                                View Statement
                                                            </a>
                                                        @else
                                                            Not Provided
                                                        @endif
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="Approval" role="tabpanel" aria-labelledby="Approval-tab">
                    <h3 class="mb-3">Loan Approval @if(!empty($loanApproval->disbursal_amount)) - (Amount Approved: {{round($loanApproval->disbursal_amount, 2)}}) @endif</h3>
                    <form action="{{ route('admin.loan.approval.store') }}" method="POST" id="loanApprovalForm">
                        @csrf
                        <div class="row">
                            <input type="hidden" name="loan_application_id" value="{{ $lead->id }}">
                            <input type="hidden" name="loan_number" value="{{ $lead->loan_no }}">
                            <input type="hidden" name="user_id" value="{{ $lead->user_id }}">
                            <input type="hidden" name="credited_by" value="{{ auth('admin')->id() }}">
                
                            <!-- Loan Type -->
                            <div class="col-md-6">
                                <label for="loan_type" class="form-label">Loan Type</label>
                                <select class="form-control" id="loan_type" name="loan_type" required>
                                    {{-- <option value="">Select Type</option> --}}
                                    <option value="Personal Loan" {{isset($loanApproval) && $loanApproval->loan_type == "Personal Loan" ? "selected" : ""}}>Personal Loan</option>
                                    {{-- <option value="Home Loan" {{isset($loanApproval) && $loanApproval == "Home Loan" ? "selected" : ""}}>Home Loan</option>
                                    <option value="Car Loan" {{isset($loanApproval) && $loanApproval == "Car Loan" ? "selected" : ""}}>Car Loan</option> --}}
                                </select>
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- Branch -->
                            <div class="col-md-6">
                                <label for="branch" class="form-label">Branch</label>
                                <input type="text" class="form-control" id="branch" name="branch" required value="{{isset($loanApproval) ? $loanApproval->branch : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- Approval Amount -->
                            <div class="col-md-6">
                                <label for="approval_amount" class="form-label">Approval Amount</label>
                                <input type="number" class="form-control" id="approval_amount" name="approval_amount" required min="0" max="100000" required value="{{isset($loanApproval) ? round($loanApproval->approval_amount) : "0"}}">
                                <span class="error-message text-danger"></span>
                            </div>
    
                            <!-- Processing Fee -->
                            <div class="col-md-6">
                                <label for="processing_fee" class="form-label">Processing Fee (%)</label>
                                <input type="number" class="form-control" id="processing_fee" name="processing_fee" required step="0.01" value="{{isset($loanApproval) ? $loanApproval->processing_fee : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>

                             <!-- Processing Fee -->
                             <div class="col-md-6">
                                <label for="processing_fee_amount" class="form-label">Processing Fee Amount</label>
                                <input type="number" class="form-control" id="processing_fee_amount" name="processing_fee_amount" required step="0.01" value="{{isset($loanApproval) ? $loanApproval->processing_fee_amount : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- GST -->
                            <div class="col-md-6">
                                <label for="gst" class="form-label">GST (%)</label>
                                <input type="text" class="form-control" id="gst" readonly value="18" required name="gst" value="{{isset($loanApproval) ? $loanApproval->gst : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>


                            <div class="col-md-6">
                                <label for="gst_amount" class="form-label">GST Amount</label>
                                <input type="text" class="form-control" id="gst_amount" readonly required name="gst_amount" value="{{isset($loanApproval) ? $loanApproval->gst_amount : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- Salary Date -->
                            <div class="col-md-6">
                                <label for="salary_date" class="form-label">Salary Date</label>
                                <input type="text" class="form-control datepicker" id="salary_date" name="salary_date" value="{{isset($loanApproval) ? $loanApproval->salary_date : ""}}" required>
                                <span class="error-message text-danger"></span>
                            </div>
    
                            <div class="col-md-6">
                                <label for="tentative_disbursal_date" class="form-label">Tentative Disbursal Date</label>
                                <input type="text" class="form-control" id="tentative_disbursal_date" name="tentative_disbursal_date" value="{{isset($loanApproval) ? $loanApproval->tentative_disbursal_date : ""}}" required>
                                <span class="error-message text-danger"></span>
                            </div>
    
                            <!-- Repay Date -->
                            <div class="col-md-6">
                                <label for="repay_date" class="form-label">Repay Date</label>
                                <input type="text" class="form-control" id="repay_date" name="repay_date" value="{{isset($loanApproval) ? $loanApproval->repay_date : ""}}" required>
                                <span class="error-message text-danger"></span>
                            </div>
    
                            <div class="col-md-6">
                                <label>Loan Tenure Days</label>
                                <input type="text" name="loan_tenure_days" id="loan_tenure_days" class="form-control" required readonly value="{{ isset($loanApproval) ? $loanApproval->loan_tenure_days : '' }}">
                                <span class="error-message text-danger"></span>
                            </div>
                            
                            {{-- <div class="col-md-6">
                                <label>Loan Tenure Date</label>
                                <input type="text" name="loan_tenure_date" id="loan_tenure_date" class="form-control datepicker" required readonly value="{{ isset($loanApproval) ? $loanApproval->loan_tenure_date : '' }}">
                                <span class="error-message text-danger"></span>
                            </div> --}}

                            <!-- ROI -->
                            <div class="col-md-6">
                                <label for="roi" class="form-label">Rate of Interest (%)</label>
                                <input type="number" class="form-control" id="roi" name="roi" step="0.01" required value="{{isset($loanApproval) ? $loanApproval->roi : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
    
                             <!-- repayment_amount -->
                             <div class="col-md-6">
                                <label for="repayment_amount" class="form-label">Repayment Amount</label>
                                <input type="number" class="form-control" id="repayment_amount" name="repayment_amount" readonly required min="0" required value="{{isset($loanApproval) ? round($loanApproval->repayment_amount) : "0"}}">
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- CIBIL Score -->
                            <div class="col-md-6">
                                <label for="cibil_score" class="form-label">CIBIL Score</label>
                                <input type="number" class="form-control" id="cibil_score" name="cibil_score" required value="{{isset($loanApproval) ? $loanApproval->cibil_score : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- Monthly Income -->
                            <div class="col-md-6">
                                <label for="monthly_income" class="form-label">Monthly Income</label>
                                <input type="number" class="form-control" id="monthly_income" name="monthly_income" required step="0.01" value="{{isset($loanApproval) ? round($loanApproval->monthly_income) : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- Status -->
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="loanApprovalForm_status" name="status" required>
                                    <option value="0" {{isset($loanApproval) && $loanApproval->status == "0" ? "selected" : ""}}>Pending</option>
                                    <option value="1" {{isset($loanApproval) && $loanApproval->status == "1" ? "selected" : ""}}>Approved</option>
                                    <option value="2" {{isset($loanApproval) && $loanApproval->status == "2" ? "selected" : ""}}>Rejected</option>
                                </select>
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- Approval Date -->
                            <div class="col-md-6">
                                <label for="approval_date" class="form-label">Approval Date</label>
                                <input type="text" class="form-control datepicker" id="approval_date" name="approval_date" required value="{{isset($loanApproval) ? $loanApproval->approval_date : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- Loan Purpose -->
                            {{-- <div class="col-md-6">
                                <label for="loan_purpose" class="form-label">Loan Purpose</label>
                                <select name="loan_purpose" id="loan_purpose" class="form-control">
                                    <option value="Medical Loan" {{$lead->purpose_of_loan == "Medical Loan" ? "selected" : ""}}>Medical Loan</option>
                                    <option value="Education Loan" {{$lead->purpose_of_loan == "Education Loan" ? "selected" : ""}}>Education Loan</option>
                                    <option value="House Loan" {{$lead->purpose_of_loan == "House Loan" ? "selected" : ""}}>House Loan</option>
                                    <option value="Business Loan" {{$lead->purpose_of_loan == "Business Loan" ? "selected" : ""}}>Business Loan</option>
                                    <option value="Car Loan" {{$lead->purpose_of_loan == "Car Loan" ? "selected" : ""}}>Car Loan</option>
                                    <option value="Personal Loan" {{$lead->purpose_of_loan == "Personal Loan" ? "selected" : ""}}>Personal Loan</option>
                                </select>
                                <span class="error-message text-danger"></span>
                            </div> --}}
                
                            <!-- Final Remark -->
                            <div class="col-md-6">
                                <label for="final_remark" class="form-label">Final Remark</label>
                                <textarea class="form-control" id="final_remark" name="final_remark">{!! isset($loanApproval) ? $loanApproval->final_remark : "" !!}</textarea>
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- Additional Remark -->
                            <div class="col-md-6">
                                <label for="additional_remark" class="form-label">Additional Remark</label>
                                <textarea class="form-control" id="additional_remark" name="additional_remark">{!! isset($loanApproval) ? $loanApproval->additional_remark : "" !!}</textarea>
                                <span class="error-message text-danger"></span>
                            </div>
                            <!-- Bank Acc No -->
                            <div class="col-md-6">
                                <label for="bank_acc_no" class="form-label">Bank Account No</label>
                                <input type="number" class="form-control" id="bank_acc_no" name="bank_acc_no" required step="0.01" value="">
                                <input type="hidden" id="bank_lead_id" value="{{ $lead->id }}">
                                <input type="hidden" id="bank_user_id" value="{{ $lead->user_id }}">
                                <span id="bankError" class="error-message text-danger"></span>
                            </div>
                            {{-- @if(isset($loanApproval) && !$loanApproval->status == "2")
                                
                            @endif --}}
                            @if(!isset($loanDisbursal))
                                    <!-- Submit Button -->
                                <div class="col-12 mt-3">
                                    <button type="submit" id="loanApprovalForm_submitbtn" class="btn btn-primary">{{isset($loanApproval) && $loanApproval->status == "1" ? "Modify Loan" : "Approve Loan"}}</button>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>            
                
                <div class="tab-pane fade" id="Disbursal" role="tabpanel" aria-labelledby="Disbursal-tab">
                    <h2>Loan Disbursal @if(!empty($loanApproval->disbursal_amount)) - (Amount Approved: {{round($loanApproval->disbursal_amount, 2)}}) @endif</h2>
                    @if(!empty($loanApproval->kfs_path))
                        <a class="btn btn-info" href="{{ url('/admin/kfs-document/'.$loanApproval->kfs_path.'/'.$lead->id) }}" target="_blank">
                            View KFS Signed Document
                        </a>
                    @endif
                    <form action="{{ route('admin.loan.disbursal.store') }}" method="POST" id="loanDisbursalForm">
                        @csrf
                        <div class="row">
                            <input type="hidden" name="loan_application_id" value="{{ $lead->id }}">
                            <input type="hidden" name="loan_number" value="{{ $lead->loan_no }}">
                            <input type="hidden" name="user_id" value="{{ $lead->user_id }}">
                            <input type="hidden" name="disbursed_by" value="{{ auth('admin')->id() }}">
                            
                            <div class="col-md-6">
                                <label>UTR No</label>
                                <input type="text" name="utr_no" class="form-control" value="{{isset($loanDisbursal) ? $loanDisbursal->utr_no : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>

                            <div class="col-md-6">
                                <label>E-natch Reference Number </label>
                                <input type="text" name="enach_reference_number" class="form-control" value="{{isset($loanDisbursal) ? $loanDisbursal->enach_reference_number : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
    
                            <div class="col-md-6">
                                <label>Disbursal Amount</label>
                                <input type="number" name="disbursal_amount" class="form-control" max="{{isset($loanApproval) ? round($loanApproval->approval_amount) : 100000}}" readonly required value="{{isset($loanApproval) ? $loanApproval->disbursal_amount : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <div class="col-md-6">
                                <label>Customer Account No</label>
                                <input type="text" name="account_no" class="form-control" required value="{{isset($loanDisbursal) ? $loanDisbursal->account_no : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <div class="col-md-6">
                                <label>IFSC Code</label>
                                <input type="text" name="ifsc" class="form-control" required value="{{isset($loanDisbursal) ? $loanDisbursal->ifsc : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <div class="col-md-6">
                                <label>Account Type</label>
                                <select name="account_type" class="form-control" required>
                                    <option value="">Select Account Type</option>
                                    <option value="CA" {{ isset($loanDisbursal) && $loanDisbursal->account_type == 'CA' ? 'selected' : '' }}>Current Account</option>
                                    <option value="SA" {{ isset($loanDisbursal) && $loanDisbursal->account_type == 'SA' ? 'selected' : '' }}>Saving Account</option>
                                </select>
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <div class="col-md-6">
                                <label>Bank Name</label>
                                <select name="bank_name" class="form-control" required>
                                    <option value="">Select Bank</option>
                                    <option value="SBIN" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'SBIN' ? 'selected' : '' }}>State Bank of India</option>
                                    <option value="HDFC" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'HDFC' ? 'selected' : '' }}>HDFC Bank</option>
                                    <option value="ICIC" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'ICIC' ? 'selected' : '' }}>ICICI Bank</option>
                                    <option value="PNB" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'PNB' ? 'selected' : '' }}>Punjab National Bank</option>
                                    <option value="AXIS" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'AXIS' ? 'selected' : '' }}>Axis Bank</option>
                                    <option value="BOB" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'BOB' ? 'selected' : '' }}>Bank of Baroda</option>
                                    <option value="CANB" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'CANB' ? 'selected' : '' }}>Canara Bank</option>
                                    <option value="IDBI" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'IDBI' ? 'selected' : '' }}>IDBI Bank</option>
                                    <option value="YESB" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'YESB' ? 'selected' : '' }}>Yes Bank</option>
                                    <option value="KOTK" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'KOTK' ? 'selected' : '' }}>Kotak Mahindra Bank</option>
                                    <option value="UNION" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'UNION' ? 'selected' : '' }}>Union Bank of India</option>
                                    <option value="BOM" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'BOM' ? 'selected' : '' }}>Bank of Maharashtra</option>
                                    <option value="INDUS" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'INDUS' ? 'selected' : '' }}>IndusInd Bank</option>
                                    <option value="UCO" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'UCO' ? 'selected' : '' }}>UCO Bank</option>
                                    <option value="IOB" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'IOB' ? 'selected' : '' }}>Indian Overseas Bank</option>
                                    <option value="SYNB" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'SYNB' ? 'selected' : '' }}>Syndicate Bank</option>
                                    <option value="FEDB" {{ isset($loanDisbursal) && $loanDisbursal->bank_name == 'FEDB' ? 'selected' : '' }}>Federal Bank</option>
                                </select>
                                <span class="error-message text-danger"></span>
                            </div>                        
                    
                            <div class="col-md-6">
                                <label>Branch</label>
                                <input type="text" name="branch" class="form-control" required value="{{isset($loanDisbursal) ? $loanDisbursal->branch : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <div class="col-md-6">
                                <label>Disbursal Date</label>
                                <input type="text" name="disbursal_date" id="disbursal_date" class="form-control datepicker" required 
                                       value="{{ isset($loanDisbursal) ? $loanDisbursal->disbursal_date : '' }}">
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <div class="col-md-6">
                                <label>Final Remark</label>
                                <textarea name="final_remark" class="form-control" required>{!!isset($loanDisbursal) ? $loanDisbursal->final_remark : ""!!}</textarea>
                                <span class="error-message text-danger"></span>
                            </div>
                        </div>
                

                        @if(!isset($loanDisbursal))
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        @endif
                    </form>
                </div>            
                
                <div class="tab-pane fade" id="UTR" role="tabpanel" aria-labelledby="UTR-tab">
                    <h1>Collection Form</h1>
                    <form action="{{ route('admin.loan.utr.store') }}" method="POST" id="loanUtrForm">
                        @csrf
                        <input type="hidden" name="loan_application_id" value="{{ $lead->id }}">
                        <input type="hidden" name="loan_number" value="{{ $lead->loan_no }}">
                        <input type="hidden" name="user_id" value="{{ $lead->user_id }}">
                        <input type="hidden" name="remaining_principal_amt" id="remaining_principal_amt" value="{{ (!empty($loans->remaining_principal)) ? number_format($loans->remaining_principal, 2) : 0 }}">
                        <input type="hidden" name="created_by" value="{{ auth('admin')->id() }}">
                        <!-- Collection Amount -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="principal">Principal</label>
                                <input type="number" step="0.01" class="form-control" name="principal" id="principal" required>
                                <span class="error-message text-danger" id="principal-error"></span>
                            </div>
                            <div class="col-md-6">
                                <label for="interest">Interest</label>
                                <input type="number" step="0.01" class="form-control" name="interest" id="interest" required>
                                <span class="error-message text-danger"></span>
                            </div>
                            <div class="col-md-6">
                                <label for="penal">Penal</label>
                                <input type="number" step="0.01" class="form-control" name="penal" id="penal" required>
                                <span class="error-message text-danger"></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="collection_amt">Total Collection Amount</label>
                                <input type="number" step="0.01" class="form-control" name="collection_amt" id="collection_amt" required readonly>
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <!-- Collection Date -->
                            <div class="col-md-6 mb-3">
                                <label for="collection_date">Collection Date</label>
                                <input type="date" class="form-control" name="collection_date" id="collection_date" required>
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <!-- Mode -->
                            <div class="col-md-6 mb-3">
                                <label for="mode">Mode</label>
                                <select class="form-control" name="mode" id="mode" required>
                                    <option value="PG">PG</option>
                                    <option value="Bank">Bank</option>
                                    <option value="Cash">Cash</option>
                                </select>
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <!-- Discount Fields -->
                            <div class="col-md-6">
                                <label for="discount_principal">Principal Discount</label>
                                <input type="number" step="0.01" class="form-control" name="discount_principal" id="discount_principal">
                                <span class="error-message text-danger"></span>
                            </div>
                            <div class="col-md-6">
                                <label for="discount_interest">Interest Discount</label>
                                <input type="number" step="0.01" class="form-control" name="discount_interest" id="discount_interest">
                                <span class="error-message text-danger"></span>
                            </div>
                            <div class="col-md-6">
                                <label for="discount_penal">Penal Discount</label>
                                <input type="number" step="0.01" class="form-control" name="discount_penal" id="discount_penal">
                                <span class="error-message text-danger"></span>
                            </div>
    
                            <!-- Reference ID -->
                            <div class="col-md-6 mb-3">
                                <label for="payment_id">Reff ID / Payment ID</label>
                                <input type="text" class="form-control" name="payment_id" id="payment_id" required>
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <label for="status">Status</label>
                                <select class="form-control" name="status" id="status" required>
                                    <option value="Closed">Closed</option>
                                    <option value="Part Payment">Part Payment</option>
                                    <option value="Settlement">Settlement</option>
                                </select>
                                <span class="error-message text-danger"></span>
                            </div>
                        </div>
                        <!-- Submit -->
                        @if($lead->loan_closed_status != "closed")
                            <button type="submit" class="btn btn-primary">Submit</button>
                        @endif
                        
                    </form>
                </div>
                
                <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                    <h3>Loan Collection History</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Loan App. No</th>
                                    <th>Loan A/c No</th>
                                    <th>User</th>
                                    <th>Principal</th>
                                    <th>Interest</th>
                                    <th>Penal</th>
                                    <th>Discount Principal</th>
                                    <th>Discount Interest</th>
                                    <th>Discount Penal</th>
                                    <th>Collection Amount</th>
                                    <th>Collection Date</th>
                                    <th>Mode</th>
                                    <th>Payment ID</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($loanUtrCollections as $collection)
                                    <tr>
                                        <td>{{ $collection->loan_no }}</td>
                                        <td>{{ $collection->loan_disbursal_number }}</td>
                                        <td>{{ $collection->user_name }}</td>
                                        <td>{{ number_format($collection->principal, 2) }}</td>
                                        <td>{{ number_format($collection->interest, 2) }}</td>
                                        <td>{{ number_format($collection->penal, 2) }}</td>
                                        <td>{{ number_format($collection->discount_principal, 2) }}</td>
                                        <td>{{ number_format($collection->discount_interest, 2) }}</td>
                                        <td>{{ number_format($collection->discount_penal, 2) }}</td>
                                        <td>{{ number_format($collection->collection_amt, 2) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($collection->collection_date)->format('d M Y') }}</td>
                                        <td>{{ $collection->mode }}</td>
                                        <td>{{ $collection->payment_id }}</td>
                                        <td>{{ $collection->status }}</td>
                                        <td>{{ \Carbon\Carbon::parse($collection->created_at)->format('d M Y h:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <br><br>
                    <h3>Current Dues</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Loan App. No</th>
                                    <th>Loan A/c No</th>
                                    <th>User</th>
                                    <th>Approval Loan Amount</th>
                                    <th>Remaining Principal Due</th>
                                    <th>Interest Due</th>
                                    <th>Penal Due</th>
                                    <th>Total Due</th>
                                    <th>Payment Link</th>
                                </tr>
                            </thead>
                            @if($loans)
                            <tbody>
                                <tr>
                                    <td>{{ $loans->loan_no }}</td>
                                    <td>{{ $loans->loan_disbursal_number }}</td>
                                    <td>{{ isset($lead->user) ? $lead->user->firstname : "" }} {{isset($lead->user) ? $lead->user->lastname : ""}}</td>
                                    <td>₹{{ number_format($loans->approval_amount, 2) }}</td>
                                    <td>₹{{ number_format($loans->remaining_principal, 2) }}</td>
                                    <td>₹{{ number_format($loans->interest, 2) }}</td>
                                    <td>₹{{ number_format($loans->penal_interest, 2) }}</td>
                                    <td>₹{{ number_format($loans->total_dues, 2) }}</td>
                                    
                                    <td><div style="display: -webkit-box;-webkit-line-clamp: 2;
                                                -webkit-box-orient: vertical;overflow: hidden;text-overflow: ellipsis;word-break: break-all;">
                                        <button onclick="copyToClipboard('{{ $paymentLink }}')" class="btn btn-sm btn-outline-primary mt-2">Copy </button>
                                        {{ $paymentLink }}</div>
                                    </td>
                                </tr>
                            </tbody>
                            @endif
                        </table>
                    </div>
                    
                </div>         
                
                <div class="tab-pane fade" id="Creditbureau" role="tabpanel" aria-labelledby="Creditbureau-tab">
                    <h3>Experian Credit Bureau</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Mobile</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ isset($lead->user) ? $lead->user->firstname : "" }} {{ isset($lead->user) ? $lead->user->lastname : "" }}</td>
                                    <td>{{ isset($lead->user) ? $lead->user->mobile : "" }}</td>
                                    <input type="hidden" id="loanno_{{ $lead->user->id }}" value="{{ $lead->loan_no }}">
                                    <input type="hidden" id="userid_{{ $lead->user->id }}" value="{{ $lead->user->id }}">
                                    <input type="hidden" id="firstname_{{ $lead->user->id }}" value="{{ $lead->user->firstname }}">
                                    <input type="hidden" id="lastname_{{ $lead->user->id }}" value="{{ $lead->user->lastname }}">
                                    <input type="hidden" id="mobile_{{ $lead->user->id }}" value="{{ $lead->user->mobile }}">
                                    <input type="hidden" id="dob_{{ $lead->user->id }}" value="{{ $panData->date_of_birth ?? '' }}">
                                    <input type="hidden" id="pan_{{ $lead->user->id }}" value="{{ $panData->pan ?? '' }}">
                                    <input type="hidden" id="houseno_{{ $lead->user->id }}" value="{{ $lead->addressDetails->house_no ?? '' }}">
                                    <input type="hidden" id="city_{{ $lead->user->id }}" value="{{ $lead->addressDetails->city ?? '' }}">
                                    <input type="hidden" id="pincode_{{ $lead->user->id }}" value="{{ $lead->addressDetails->pincode ?? '' }}">
                                    <input type="hidden" id="state_{{ $lead->user->id }}" value="{{ $lead->addressDetails->state ?? '' }}">
                                    <input type="hidden" id="verify_{{ $lead->user->id }}" value="{{ 2 }}">
                                    <td>
                                        @if (empty($experianCreditBureau->pdf_url))
                                        <button type="button" onclick="checkCreditScore({{ $lead->user->id }})" class="btn btn-danger">Check Credit Score</button>
                                        @endif
                                        @if (!empty($experianCreditBureau->pdf_url))
                                            <a href="{{ $experianCreditBureau->pdf_url }}" class="btn btn-primary" id="{{ $panData->pan }}" target="_blank">View Credit Score</a>
                                            </br></br>
                                            <a href="{{ $experianCreditBureau->pdf_url }}" class="btn btn-secondary" download>Download PDF</a>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="Bsareport" role="tabpanel" aria-labelledby="Bsareport-tab">
                    <h3>Score Me BSA Report</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>User Name</th>
                                    <th>Bank Statement</th>
                                    <th>Bank Statement Password</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ isset($lead->user) ? $lead->user->firstname : "" }} {{ isset($lead->user) ? $lead->user->lastname : "" }}</td>
                                    <td>@php
                                            $filename = isset($lead->bankDetails) ? basename($lead->bankDetails->bank_statement) : '';
                                        @endphp
                                        @if(isset($lead->bankDetails) && $lead->bankDetails->bank_statement)
                                            <a href="{{ url('/admin/secure-document/'.$filename) }}" target="_blank">
                                                View Statement
                                            </a>
                                        @else
                                            Not Provided
                                        @endif</td>
                                    <td>{{ $lead->bankDetails->bank_statement_password ?? 'Not Provided' }}</td>
                                    <td>
                                    <input type="hidden" id="bank_statement_filename" value="{{ isset($lead->bankDetails) && $lead->bankDetails->bank_statement ? $filename : '' }}">
                                    <input type="hidden" id="bank_statement" value="{{ isset($lead->bankDetails) && $lead->bankDetails->bank_statement ? url('/admin/secure-document/'.$filename) : '' }}">
                                    <input type="hidden" id="bank_statement_pass" value="{{ $lead->bankDetails->bank_statement_password ?? '' }}">
                                    <button type="button" onclick="checkBSAScore({{ $lead->id }})" class="btn btn-danger">Check BSA Report</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div> 
    </div>   
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="copyToast" class="toast align-items-center text-white bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body">
        Link copied to clipboard!
      </div>
    </div>
  </div>
</div>
@endsection 

@push('style')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@endpush

@push('script')
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script>
    $(document).ready(function() {
        $(".datepicker").datepicker({
            dateFormat: "yy-mm-dd", 
            minDate: 0 
        });
        $('#loanApprovalForm_submitbtn').click(function(e) {
            e.preventDefault();

            const statusVal = $('#loanApprovalForm_status').val();

            if (!statusVal) {
                alert("Please select a status");
                return;
            }

            if (statusVal == '2') {
                $("#loanApprovalForm").validate().resetForm();
                $(".is-invalid").removeClass("is-invalid");

                $('#loanApprovalForm')[0].submit();
            } else {
                if ($("#loanApprovalForm").valid()) {
                    $('#loanApprovalForm')[0].submit();
                }
            }
        });

        $("#loanApprovalForm").validate({
            errorClass: "is-invalid", 
            errorElement: "span",
            errorPlacement: function(error, element) {
                error.addClass("text-danger"); 
                element.closest("div").find(".error-message").html(error); 
            },
            highlight: function(element) {
                $(element).addClass("is-invalid");
            },
            unhighlight: function(element) {
                $(element).removeClass("is-invalid");
                $(element).closest("div").find(".error-message").html(""); 
            },
            rules: {
                approval_amount: {
                    required: true,
                    min: 1000,
                    max: 100000
                },
                roi: {
                    required: true,
                    min: 0.1,
                    max: 100
                },
                salary_date: {
                    required: true,
                    date: true
                },
                repay_date: {
                    required: true,
                    date: true
                }
            },
            messages: {
                approval_amount: {
                    required: "Approval amount is required",
                    min: "Minimum amount is 1000",
                    max: "Maximum amount is 100000"
                },
                roi: {
                    required: "Rate of Interest is required",
                    min: "Minimum ROI is 1%",
                    max: "Maximum ROI is 50%"
                },
                salary_date: {
                    required: "Salary date is required",
                    date: "Enter a valid date"
                },
                repay_date: {
                    required: "Repay date is required",
                    date: "Enter a valid date"
                }
            }
        });

        $.validator.addMethod("ifscRegex", function(value, element) {
            return /^[A-Z]{4}0[A-Z0-9]{6}$/.test(value);
        }, "Enter a valid IFSC code.");

        $("#loanDisbursalForm").validate({
            errorClass: "is-invalid", 
            errorElement: "span",
            errorPlacement: function(error, element) {
                error.addClass("text-danger"); 
                element.closest("div").find(".error-message").html(error); 
            },
            highlight: function(element) {
                $(element).addClass("is-invalid");
            },
            unhighlight: function(element) {
                $(element).removeClass("is-invalid");
                $(element).closest("div").find(".error-message").html(""); 
            },
            rules: {
                utr_no: {
                    required: true
                },
                disbursal_amount: {
                    required: true,
                    number: true,
                    min: 1
                },
                account_no: {
                    required: true,
                    digits: true,
                    minlength: 6,
                    maxlength: 18
                },
                ifsc: {
                    required: true,
                    ifscRegex: true
                },
                account_type: {
                    required: true
                },
                bank_name: {
                    required: true
                },
                branch: {
                    required: true
                },
                disbursal_date: {
                    required: true,
                    date: true
                },
                loan_tenure_days: {
                    required: true,
                    digits: true,
                    min: 1
                },
                loan_tenure_date: {
                    required: true,
                    date: true
                },
                final_remark: {
                    required: true
                }
            },
            messages: {
                utr_no: {
                    required: "UTR Number is required."
                },
                disbursal_amount: {
                    required: "Disbursal Amount is required.",
                    number: "Enter a valid number.",
                    min: "Amount must be greater than 0."
                },
                account_no: {
                    required: "Customer Account Number is required.",
                    digits: "Only numbers are allowed.",
                    minlength: "Account number must be at least 6 digits.",
                    maxlength: "Account number cannot exceed 18 digits."
                },
                ifsc: {
                    required: "IFSC Code is required."
                },
                account_type: {
                    required: "Please select Account Type."
                },
                bank_name: {
                    required: "Please select a Bank."
                },
                branch: {
                    required: "Branch is required."
                },
                disbursal_date: {
                    required: "Disbursal Date is required."
                },
                loan_tenure_days: {
                    required: "Loan Tenure Days are required.",
                    digits: "Only numbers are allowed.",
                    min: "Loan tenure must be at least 1 day."
                },
                loan_tenure_date: {
                    required: "Loan Tenure Date is required."
                },
                final_remark: {
                    required: "Final Remark is required."
                }
            }
        });

        $("#loanUtrForm").validate({
            errorClass: "is-invalid",
            errorElement: "span",
            errorPlacement: function (error, element) {
                error.addClass("text-danger");
                element.closest("div").find(".error-message").html(error);
            },
            highlight: function (element) {
                $(element).addClass("is-invalid");
            },
            unhighlight: function (element) {
                $(element).removeClass("is-invalid");
                $(element).closest("div").find(".error-message").html("");
            },
            rules: {
                principal: {
                    required: true,
                    number: true,
                    min: 0
                },
                interest: {
                    required: true,
                    number: true,
                    min: 0
                },
                penal: {
                    required: true,
                    number: true,
                    min: 0
                },
                collection_amt: {
                    required: true,
                    number: true,
                    min: 0
                },
                collection_date: {
                    required: true,
                    date: true
                },
                mode: {
                    required: true
                },
                discount_principal: {
                    number: true,
                    min: 0
                },
                discount_interest: {
                    number: true,
                    min: 0
                },
                discount_penal: {
                    number: true,
                    min: 0
                },
                payment_id: {
                    required: true,
                    minlength: 3
                },
                status: {
                    required: true
                }
            },
            messages: {
                principal: {
                    required: "Principal amount is required",
                    number: "Enter a valid number",
                    min: "Amount cannot be negative"
                },
                interest: {
                    required: "Interest amount is required",
                    number: "Enter a valid number",
                    min: "Amount cannot be negative"
                },
                penal: {
                    required: "Penal amount is required",
                    number: "Enter a valid number",
                    min: "Amount cannot be negative"
                },
                collection_amt: {
                    required: "Total collection amount is required",
                    number: "Enter a valid number",
                    min: "Amount cannot be negative"
                },
                collection_date: {
                    required: "Collection date is required",
                    date: "Enter a valid date"
                },
                mode: {
                    required: "Please select a mode of payment"
                },
                discount_principal: {
                    number: "Enter a valid number",
                    min: "Discount cannot be negative"
                },
                discount_interest: {
                    number: "Enter a valid number",
                    min: "Discount cannot be negative"
                },
                discount_penal: {
                    number: "Enter a valid number",
                    min: "Discount cannot be negative"
                },
                payment_id: {
                    required: "Payment ID is required",
                    minlength: "Payment ID must be at least 3 characters"
                },
                status: {
                    required: "Please select a status"
                }
            }
        });

        // Auto-calculate total collection amount
        function calculateTotal() {
            let principal = parseFloat($("#principal").val()) || 0;
            let interest = parseFloat($("#interest").val()) || 0;
            let penal = parseFloat($("#penal").val()) || 0;
            let discountPrincipal = parseFloat($("#discount_principal").val()) || 0;
            let discountInterest = parseFloat($("#discount_interest").val()) || 0;
            let discountPenal = parseFloat($("#discount_penal").val()) || 0;

            let total = (principal - discountPrincipal) + (interest - discountInterest) + (penal - discountPenal);
            $("#collection_amt").val(total.toFixed(2));
        }

        // Bind calculation to input fields
        $("#principal, #interest, #penal, #discount_principal, #discount_interest, #discount_penal").on("input", calculateTotal);
    });

    $(document).ready(function () {
        //console.log("Script loaded successfully!");
        let today = new Date();
        let todayFormatted = today.toISOString().split("T")[0]; 

        $("#tentative_disbursal_date").datepicker({
            dateFormat: "yy-mm-dd",
            minDate: 0,
            onSelect: function (dateText) {
                console.log("Tentative Disbursal Date selected: " + dateText);
                updateRepayDateRange(dateText);
                calculateLoanTenure();
            }
        });

        $("#repay_date").datepicker({
            dateFormat: "yy-mm-dd",
            minDate: 0,
            onSelect: function (dateText) {
                console.log("Repay Date selected: " + dateText);
                calculateLoanTenure();
            }
        });

        function updateRepayDateRange(disbursalDateStr) {
            let minRepayDate = new Date(disbursalDateStr);
            let maxRepayDate = new Date(disbursalDateStr);
            maxRepayDate.setDate(maxRepayDate.getDate() + 45);

            $("#repay_date").datepicker("option", "minDate", minRepayDate);
            $("#repay_date").datepicker("option", "maxDate", maxRepayDate);

            console.log(`Repay Date range set: Min = ${minRepayDate.toISOString().split("T")[0]}, Max = ${maxRepayDate.toISOString().split("T")[0]}`);
        }

        function calculateLoanTenure() {
            let disbursalDateStr = $("#tentative_disbursal_date").val();
            let repayDateStr = $("#repay_date").val();

            if (disbursalDateStr && repayDateStr) {
                let disbursalDate = new Date(disbursalDateStr);
                let repaymentDate = new Date(repayDateStr);

                if (!isNaN(disbursalDate.getTime()) && !isNaN(repaymentDate.getTime())) {
                    let timeDiff = repaymentDate.getTime() - disbursalDate.getTime();
                    let tenureDays = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));

                    if (tenureDays >= 0) {
                        $("#loan_tenure_days").val(tenureDays);
                        $("#loan_tenure_date").val(disbursalDateStr);
                    } else {
                        alert("Repay Date cannot be before the Tentative Disbursal Date.");
                        $("#repay_date").val("");
                        $("#loan_tenure_days").val("");
                    }
                } else {
                    console.error("Invalid date values");
                }
            }
        }

        function calculateRepaymentAmount() {
            let approvalAmount = parseFloat($("#approval_amount").val()) || 0;
            let loanTenureDays = parseInt($("#loan_tenure_days").val()) || 0;
            let dailyInterestRate = parseFloat($("#roi").val()) / 100 || 0; 

            if (approvalAmount > 0 && loanTenureDays > 0 && dailyInterestRate > 0) {
                let totalInterest = approvalAmount * dailyInterestRate * loanTenureDays;
                let repaymentAmount = approvalAmount + totalInterest;

                $("#repayment_amount").val(repaymentAmount.toFixed(2));
            } else {
                $("#repayment_amount").val(0);
            }
        }

        $("#loan_tenure_days, #roi, #approval_amount, #loan_tenure_days").on("input", function () {
            calculateRepaymentAmount();
        });

        calculateRepaymentAmount();
    });

    $(document).ready(function () {
        function calculateProcessingFeeAmount() {
            let approvalAmount = parseFloat($("#approval_amount").val()) || 0;
            let processingFee = parseFloat($("#processing_fee").val()) || 0;
            let gstRate = 18; // GST is fixed at 18%

            // Calculate Processing Fee Amount
            let processingFeeAmount = (approvalAmount * processingFee) / 100;
            $("#processing_fee_amount").val(processingFeeAmount.toFixed(2));

            // Calculate GST Amount (18% of Processing Fee Amount)
            let gstAmount = (processingFeeAmount * gstRate) / 100;
            $("#gst_amount").val(gstAmount.toFixed(2));
        }

        // Trigger calculation when input values change
        $("#approval_amount, #processing_fee").on("input", function () {
            calculateProcessingFeeAmount();
        });

        // Run calculation on page load if values are already set
        calculateProcessingFeeAmount();
    });

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            const toastEl = document.getElementById('copyToast');
            const toast = new bootstrap.Toast(toastEl, { delay: 2000 });
            toast.show();
        }).catch(err => {
            console.error("Copy failed:", err);
        });
    }

    const principalInput = document.getElementById('principal');
    const statusSelect = document.getElementById('status');
    const remainingPrincipal = parseFloat(document.getElementById('remaining_principal_amt').value.replace(',', '').replace('.00', ''));
    const principalError = document.getElementById('principal-error');
    const loanUtrForm = document.getElementById('loanUtrForm');

    loanUtrForm.addEventListener('submit', function (e) {
        // Reset error message
        principalInput.setCustomValidity('');
        principalError.textContent = '';
        principalError.classList.add('error-message');

        const enteredPrincipal = parseFloat(principalInput.value || 0);

        // Check if principal is empty
        if (!principalInput.value.trim()) {
            e.preventDefault();
            principalError.textContent = 'Principal amount is required';
            principalError.classList.remove('error-message');
            principalInput.setCustomValidity('Invalid');
            principalInput.reportValidity();
            return;
        }

        // Check if principal is less than remaining principal
        if (enteredPrincipal < remainingPrincipal) {
            e.preventDefault();
            principalError.textContent = 'Principal amount cannot be less than the remaining principal amount';
            principalError.classList.remove('error-message');
            principalInput.setCustomValidity('Invalid');
            principalInput.reportValidity();
            return;
        }

        // Additional check if status is 'Closed' and principal doesn't match remaining
        if (statusSelect.value === 'Closed' && enteredPrincipal !== remainingPrincipal) {
            e.preventDefault();
            principalError.textContent = 'Principal must match the remaining principal amount for closed status';
            principalError.classList.remove('error-message');
            principalInput.setCustomValidity('Invalid');
            principalInput.reportValidity();
        }
    });

    // ✅ Auto-clear error when user corrects the input
    principalInput.addEventListener('input', function () {
        const currentVal = parseFloat(this.value || 0);
        //alert('current - '+currentVal+' === Remaining - '+remainingPrincipal);
        if (this.value.trim() && currentVal >= remainingPrincipal) {
            if (statusSelect.value === 'Closed' && currentVal === remainingPrincipal) {
                principalInput.setCustomValidity('');
                principalError.textContent = '';
                principalError.classList.add('error-message');
            } else if (statusSelect.value !== 'Closed') {
                principalInput.setCustomValidity('');
                principalError.textContent = '';
                principalError.classList.add('error-message');
            }
        } else if (!this.value.trim()) {
            principalError.textContent = 'Principal amount is required';
            principalError.classList.remove('error-message');
        } else if (currentVal < remainingPrincipal) {
            principalError.textContent = 'Principal amount cannot be less than the remaining principal amount {{ (!empty($loans->remaining_principal)) ? number_format($loans->remaining_principal, 2) : 0 }}';
            principalError.classList.remove('error-message');
        }
    });

    statusSelect.addEventListener('change', function() {
        // Re-validate on status change, especially for the 'Closed' scenario
        principalInput.dispatchEvent(new Event('input'));
    });

    function checkCreditScore(id) {
        var user_id = document.getElementById('userid_'+id).value;
        var loan_no = document.getElementById('loanno_'+id).value;
        
        var firstname = document.getElementById('firstname_'+id).value;
        var lastname = document.getElementById('lastname_'+id).value;
        var mobile = document.getElementById('mobile_'+id).value;
        var date_of_birth = document.getElementById('dob_'+id).value;
        var dob = date_of_birth.replace(/-/g, "");
        var pan = document.getElementById('pan_'+id).value;
        var house_no = document.getElementById('houseno_'+id).value;
        var city = document.getElementById('city_'+id).value;
        var pincode = document.getElementById('pincode_'+id).value;
        var state = document.getElementById('state_'+id).value;
        var verify = document.getElementById('verify_'+id).value;

        if(!loan_no) return false;
        if(!user_id) return false;
        if(!firstname) return false;
        if(!lastname) return false;
        if(!mobile) return false;
        if(!dob) return false;
        if(!pan) return false;
        if(!house_no) return false;
        if(!city) return false;
        if(!pincode) return false;
        if(!state) return false;

        if(firstname && lastname && mobile && dob && pan && house_no && city && pincode && state && loan_no && user_id){
            $.ajax({
                url: "{{ route('admin.creditbureau.checkReport') }}",
                type: "GET",
                data: {
                    firstname : firstname, lastname : lastname, mobile : mobile, dob : dob, pan : pan, house_no : house_no, city : city, pincode : pincode, state : state, loan_no : loan_no, user_id : user_id, verify : verify,
                },
                success: function(response) {
                    //alert("Success");
                    location.reload();
                }
            });
        }else{
            alert("Something went wrong....");
        }
        
    }

    function checkBSAScore(id) {
        var bank_statement_filename = document.getElementById('bank_statement_filename').value;
        var bank_statement = document.getElementById('bank_statement').value;
        var bank_statement_pass = document.getElementById('bank_statement_pass').value;

        if(!bank_statement) return false;

        if(bank_statement){
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "{{ route('admin.creditbureau.scoremeuploaddoc') }}",
                type: "POST",
                data: {
                    bank_statement_filename : bank_statement_filename, bank_statement : bank_statement, bank_statement_pass : bank_statement_pass, loan_id : id
                },
                success: function(response) {
                    alert("Success");
                    location.reload();
                }
            });
        }else{
            alert("Something went wrong....");
        }
        
    }

    document.addEventListener('DOMContentLoaded', function() {
        const bank_acc_no = document.getElementById('bank_acc_no');
        const user_id = document.getElementById('bank_user_id').value.trim();
        const lead_id = document.getElementById('bank_lead_id').value.trim();
        let currentTimeout;

        bank_acc_no.addEventListener('input', function() {
            const bank_acc_no = this.value.trim();
            // Clear previous timeout if it exists
        if (currentTimeout) {
            clearTimeout(currentTimeout);
        }

        // Set a new timeout to prevent excessive requests
        currentTimeout = setTimeout(function() {
        if (bank_acc_no.length >= 11 || bank_acc_no.length === 12) {
            //alert('gggg ++++'+user_id+'----'+lead_id)
            fetchBankDetails(bank_acc_no, user_id, lead_id);
        }
        }, 300);
        });
    });

    function fetchBankDetails(bank_acc_no, user_id, lead_id){
        //console.log('val -');
        if(!bank_acc_no) return false;

        if(bank_acc_no){
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "{{ route('admin.creditbureau.checkBankAccNo') }}",
                type: "GET",
                data: {
                    bank_acc_no : bank_acc_no, user_id : user_id, lead_id : lead_id
                },
                success: function(response) {
                    //console.log(response.bank_status)
                    const errorMessageSpan = $("#bankError");
                    if(response.bank_status){
                        errorMessageSpan.text("Not Exist");
                        $('#loanApprovalForm_submitbtn').removeClass('disabled');
                    }else{
                        errorMessageSpan.text("Already Exist With User Id "+response.user_id);
                        $('#loanApprovalForm_submitbtn').addClass('disabled');
                    }
                }
            });
        }else{
            alert("Something went wrong....");
        }

    }
</script>
@endpush
