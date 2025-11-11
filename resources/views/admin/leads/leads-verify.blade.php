@extends('admin.layouts.app') 
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    td.text-break {
        word-break: break-word;
    }

    #confirmModal2 .raise-payment-modal {
    border-radius: 12px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.1);
    }

    #confirmModal2 .modal-title {
    font-weight: 600;
    color: #1a1a1a;
    }

    #confirmModal2 .form-label {
    font-weight: 500;
    color: #333;
    }

    #confirmModal2 .input-group-text {
    background-color: #f8f9fa;
    font-weight: 500;
    }

    #confirmModal2 .btn-primary {
    background-color: #6f42c1;
    border-color: #6f42c1;
    border-radius: 8px;
    padding: 6px 18px;
    }

    #confirmModal2 .btn-primary:hover {
    background-color: #5a34a5;
    }

    #confirmModal2 .btn-outline-secondary {
    border-radius: 8px;
    padding: 6px 18px;
    }

    #confirmModal2 textarea.form-control {
    resize: none;
    }

    /* Make table compact & fit nicely in tab */
    .table-fit {
        padding: 0.75rem 1rem;
    }

    .table-responsive-sm {
        overflow-x: auto; /* removes scroll */
    }

    /* Reduce font and padding slightly for compactness */
    .small-table th, .small-table td {
        padding: 8px 12px;
        font-size: 1rem; /* Adjust font size */
    }

    /* Make headers more balanced */
    .small-table th {
        font-weight: 600;
        background-color: #f8f9fa;
    }

    .small-table td {
        word-wrap: break-word;
        white-space: normal;
    }

    /* Rounded card corners & soft shadow */
    .card {
        border-radius: 12px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.1);
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
                    @php
                        $selfiePath = null;

                        if (isset($lead->loanDocument) && is_object($lead->loanDocument) && !empty($lead->loanDocument->selfie_image)) {
                            $selfiePath = url('/admin/secure-file/'.$lead->loanDocument->selfie_image);
                        } elseif (!empty($selfieDoc->selfie_image ?? '')) {
                            $selfiePath = url('/admin/secure-file/'.$selfieDoc->selfie_image);
                        } else {
                            $selfiePath = asset('assets/admin/images/admin.png');
                        }
                    @endphp
                    <img class="account-holder-image rounded border w-100" src="{{ $selfiePath }}" alt="account-holder-image" />
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

                    <button class="nav-link" id="Creditbureau-tab" data-bs-toggle="tab" data-bs-target="#Creditbureau" type="button" role="tab" aria-controls="Creditbureau" aria-selected="false">Credit Bureau</button>

                    <button class="nav-link" id="Bsareport-tab" data-bs-toggle="tab" data-bs-target="#Bsareport" type="button" role="tab" aria-controls="Bsareport" aria-selected="false" >BSA Report</button>
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
                                    
                                    {{-- View Mode --}}
                                    <div id="addressView">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr><th>Address Type</th><td id="v_address_type">{{ $lead->addressDetails->address_type ?? '' }}</td></tr>
                                                <tr><th>House No</th><td id="v_house_no">{{ $lead->addressDetails->house_no ?? '' }}</td></tr>
                                                <tr><th>Locality</th><td id="v_locality">{{ $lead->addressDetails->locality ?? '' }}</td></tr>
                                                <tr><th>Pincode</th><td id="v_pincode">{{ $lead->addressDetails->pincode ?? '' }}</td></tr>
                                                <tr><th>City</th><td id="v_city">{{ $lead->addressDetails->city ?? '' }}</td></tr>
                                                <tr><th>State</th><td id="v_state">{{ $lead->addressDetails->state ?? '' }}</td></tr>
                                                <tr><th>Relation</th><td id="v_relation">{{ $lead->addressDetails->relation ?? '' }}</td></tr>
                                                <tr><th>Relative Name</th><td id="v_relative_name">{{ $lead->addressDetails->relative_name ?? '' }}</td></tr>
                                                <tr><th>Contact Number</th><td id="v_contact_number">{{ $lead->addressDetails->contact_number ?? '' }}</td></tr>
                                            </tbody>
                                        </table>
                                        @if(!$loanDisbursalExists)
                                        <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                                            <button class="btn btn-primary" id="editAddressBtn">Edit</button>
                                        </div>
                                        @endif
                                    </div>

                                    {{-- Edit Mode --}}
                                    <div id="addressEdit" style="display:none;">
                                        <form id="addressForm">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $lead->addressDetails->id ?? '' }}">
                                            <input type="hidden" name="lead_id" value="{{ $lead->id }}">

                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <label>Relation</label>
                                                    <input type="text" name="relation" class="form-control" value="{{ $lead->addressDetails->relation ?? '' }}">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label>Relative Name</label>
                                                    <input type="text" name="relative_name" class="form-control" value="{{ $lead->addressDetails->relative_name ?? '' }}">
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <label>Contact Number</label>
                                                    <input type="text" name="contact_number" class="form-control" value="{{ $lead->addressDetails->contact_number ?? '' }}">
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-success">Save</button>
                                            <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                                        </form>
                                    </div>

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
                                <input type="text" class="form-control" id="branch" name="branch" value="{{isset($loanApproval) ? $loanApproval->branch : ""}}">
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- Approval Amount -->
                            <div class="col-md-6">
                                <label for="approval_amount" class="form-label">Approval Amount</label>
                                <input type="number" class="form-control" id="approval_amount" name="approval_amount" min="0" max="40000" required value="{{isset($loanApproval) ? round($loanApproval->approval_amount) : "0"}}" oninput="validateApprovalAmount(this)">
                                <span class="error-message text-danger"></span>
                            </div>
    
                            <!-- Processing Fee -->
                            <div class="col-md-6">
                                <label for="processing_fee" class="form-label">Processing Fee (%)</label>
                                <input type="number" class="form-control" id="processing_fee" name="processing_fee" step="0.01" required value="{{isset($loanApproval) ? $loanApproval->processing_fee : ""}}" oninput="validateProcessingFee(this)">
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
                                <input type="text" name="loan_tenure_days" id="loan_tenure_days" class="form-control" required readonly min="15" max="45" value="{{ isset($loanApproval) ? $loanApproval->loan_tenure_days : '' }}">
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
                                <input type="number" class="form-control" id="roi" name="roi" step="0.01" required value="{{isset($loanApproval) ? $loanApproval->roi : ""}}" oninput="validateROI(this)">
                                <span class="error-message text-danger"></span>
                            </div>
    
                             <!-- repayment_amount -->
                             <div class="col-md-6">
                                <label for="repayment_amount" class="form-label">Repayment Amount</label>
                                <input type="number" class="form-control" id="repayment_amount" name="repayment_amount" readonly required min="0" value="{{isset($loanApproval) ? round($loanApproval->repayment_amount) : "0"}}">
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- CIBIL Score -->
                            <div class="col-md-6">
                                <label for="cibil_score" class="form-label">CIBIL Score</label>
                                <input type="number" class="form-control" id="cibil_score" name="cibil_score" required value="{{isset($loanApproval) ? $loanApproval->cibil_score : ""}}" min="550" oninput="validateCIBIL(this)">
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- Monthly Income -->
                            <div class="col-md-6">
                                <label for="monthly_income" class="form-label">Monthly Income</label>
                                <input type="number" class="form-control" id="monthly_income" name="monthly_income" required step="0.01" value="{{isset($loanApproval) ? round($loanApproval->monthly_income) : ""}}" oninput="validateMonthlyIncome(this)" >
                                <span class="error-message text-danger"></span>
                            </div>
                
                            <!-- Status -->
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="loanApprovalForm_status" name="status" required>
                                    <option value="0" {{isset($loanApproval) && $loanApproval->status == "0" ? "selected" : ""}}>Pending / Hold</option>
                                    <option value="1" {{isset($loanApproval) && $loanApproval->status == "1" ? "selected" : ""}}>Approved</option>
                                    <option value="2" {{isset($loanApproval) && $loanApproval->status == "2" ? "selected" : ""}}>Rejected</option>
                                    <option value="3" {{isset($loanApproval) && $loanApproval->status == "3" ? "selected" : ""}}>Not Interested</option>
                                    <option value="4" {{isset($loanApproval) && $loanApproval->status == "4" ? "selected" : ""}}>Approved (Not Interested)</option>
                                </select>
                                <span class="error-message text-danger"></span>
                            </div>

                            <!-- Hidden by default -->
                            <div class="col-md-12" id="rejectReasonDiv" style="display: none;">
                                <label for="rejection_reason" class="form-label">Rejection Reason</label>
                                <select class="form-control" id="rejection_reason" name="rejection_reason">
                                    <option value="">Select Reason</option>
                                    <option value="AFR -01 - Cibil default (latest dpds, written off, suite filed, settled etc.)" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -01 - Cibil default (latest dpds, written off, suite filed, settled etc.)" ? "selected" : ""}}>AFR -01 - Cibil default (latest dpds, written off, suite filed, settled etc.)</option>
                                    <option value="AFR -02 - Salary not verified, Cash salary" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -02 - Salary not verified, Cash salary" ? "selected" : ""}}>AFR -02 - Salary not verified, Cash salary</option>
                                    <option value="AFR -03 - No business proof" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -03 - No business proof" ? "selected" : ""}}>AFR -03 - No business proof</option>
                                    <option value="AFR -04 - Bounces in banking (more than 2 in last 3 months not cleared)" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -04 - Bounces in banking (more than 2 in last 3 months not cleared)" ? "selected" : ""}}>AFR -04 - Bounces in banking (more than 2 in last 3 months not cleared)</option>
                                    <option value="AFR -05 - Poor banking txns and low ABB balances." {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -05 - Poor banking txns and low ABB balances." ? "selected" : ""}}>AFR -05 - Poor banking txns and low ABB balances.</option>
                                    <option value="AFR -06 - Customer is not Interested" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -06 - Customer is not Interested" ? "selected" : ""}}>AFR -06 - Customer is not Interested</option>
                                    <option value="AFR -07 - Number Switched off, Temporarily out of service, not reachable etc." {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -07 - Number Switched off, Temporarily out of service, not reachable etc." ? "selected" : ""}}>AFR -07 - Number Switched off, Temporarily out of service, not reachable etc.</option>
                                    <option value="AFR -08 - Details provided in application and on call are different" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -08 - Details provided in application and on call are different" ? "selected" : ""}}>AFR -08 - Details provided in application and on call are different</option>
                                    <option value="AFR -09 - Heavy usage of overdrafting (negative balances in banking)" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -09 - Heavy usage of overdrafting (negative balances in banking)" ? "selected" : ""}}>AFR -09 - Heavy usage of overdrafting (negative balances in banking)</option>
                                    <option value="AFR -10 - Manipulating details over call, Prompting found in verification, Someone else was giving details over the call" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -10 - Manipulating details over call, Prompting found in verification, Someone else was giving details over the call" ? "selected" : ""}}>AFR -10 - Manipulating details over call, Prompting found in verification, Someone else was giving details over the call</option>
                                    <option value="AFR -11 - Fraud documents/Customer" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -11 - Fraud documents/Customer" ? "selected" : ""}}>AFR -11 - Fraud documents/Customer</option>
                                    <option value="AFR -12 - Not co operating over call for details" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -12 - Not co operating over call for details" ? "selected" : ""}}>AFR -12 - Not co operating over call for details</option>
                                    <option value="AFR -13 - Seasonal Business, No business setup" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -13 - Seasonal Business, No business setup" ? "selected" : ""}}>AFR -13 - Seasonal Business, No business setup</option>
                                    <option value="AFR -14 - Doubtful profile (Details mismatch completely)" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -14 - Doubtful profile (Details mismatch completely)" ? "selected" : ""}}>AFR -14 - Doubtful profile (Details mismatch completely)</option>
                                    <option value="AFR -15 - Negative profile (Defence person, Policeman, lawyer, astrologer, priest, media reporter, commission agent, collection agent, lottery etc)" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -15 - Negative profile (Defence personell, Policeman, lawyer, astrologer, priest, media reporter, commission agent, collection agent, lottery etc)" ? "selected" : ""}}>AFR -15 - Negative profile (Defence personell, Policeman, lawyer, astrologer, priest, media reporter, commission agent, collection agent, lottery etc)</option>
                                    <option value="AFR -16 - Heavy txns of shares and online gaming" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -16 - Heavy txns of shares and online gaming" ? "selected" : ""}}>AFR -16 - Heavy txns of shares and online gaming</option>
                                    <option value="AFR -17 - Income less than 25k" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -17 - Income less than 25k" ? "selected" : ""}}>AFR -17 - Income less than 25k</option>
                                    <option value="AFR -18 - Overdebt customer (insufficient repayment capacity, negative NDI)" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -18 - Overdebt customer (insufficient repayment capacity, negative NDI)" ? "selected" : ""}}>AFR -18 - Overdebt customer (insufficient repayment capacity, negative NDI)</option>
                                    <option value="AFR -19 - Negative reference feedback, reference denied to provide loan to applicant" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -19 - Negative reference feedback, reference denied to provide loan to applicant" ? "selected" : ""}}>AFR -19 - Negative reference feedback, reference denied to provide loan to applicant</option>
                                    <option value="AFR -20 - Rented RCO profile" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -20 - Rented RCO profile" ? "selected" : ""}}>AFR -20 - Rented RCO profile</option>
                                    <option value="AFR -21 - Rented bachelor/shared accommodation age less than 23 years and income less than 20k" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -21 - Rented bachelor/shared accommodation age less than 23 years and income less than 20k" ? "selected" : ""}}>AFR -21 - Rented bachelor/shared accommodation age less than 23 years and income less than 20k</option>
                                    <option value="AFR -22 - As per our policy/Internal rule" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -22 - As per our policy/Internal rule" ? "selected" : ""}}>AFR -22 - As per our policy/Internal rule</option>
                                    <option value="AFR -23 - DPD in internal loan" {{isset($loanApproval) && $loanApproval->reject_reason == "AFR -23 - DPD in internal loan" ? "selected" : ""}}>AFR -23 - DPD in internal loan</option>

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
                                <input type="number" class="form-control" id="bank_acc_no" name="bank_acc_no" step="0.01" value="{{ $lead->bankDetails->account_number ?? '' }}">
                                <input type="hidden" id="bank_lead_id" value="{{ $lead->id }}">
                                <input type="hidden" id="bank_loan_id" name="loan_application_id" value="{{ $lead->id}}">
                                <input type="hidden" id="bank_user_id" value="{{ $lead->user_id }}">
                                <span id="bankError" class="error-message text-danger"></span>
                            </div>

                            <div class="col-md-6">
                                <label for="ifsccode" class="form-label">IFSC Code</label>
                                <input type="text" class="form-control" id="ifsccode" name="ifsccode" value="{{ $lead->bankDetails->ifsc_code ?? '' }}">
                                <span class="error-message text-danger"></span>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="bank_name" class="form-label">Bank Name</label>
                                    <select name="bank_name" id="bank_name" class="form-control">
                                        <option value="">Select a Bank</option>
                                        <option value="Axis" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Axis" ? "selected" : "" }}>Axis Bank</option>
                                        <option value="Baroda" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Baroda" ? "selected" : "" }}>Bank of Baroda</option>
                                        <option value="Axis" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "BOI" ? "selected" : "" }}>Bank Of India</option>
                                        <option value="Maharashtra" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Maharashtra" ? "selected" : "" }}>Bank of Maharashtra</option>
                                        <option value="Canara" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Canara" ? "selected" : "" }}>Canara Bank</option>
                                        <option value="Federal" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Federal" ? "selected" : "" }}>Federal Bank</option>
                                        <option value="HDFC" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "HDFC" ? "selected" : "" }}>HDFC Bank</option>
                                        <option value="Induslnd" {{ isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Induslnd" ? "selected" : "" }}>Induslnd Bank</option>
                                        <option value="ICICI" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "ICICI" ? "selected" : "" }}>ICICI Bank</option>
                                        <option value="IDBI" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "IDBI" ? "selected" : "" }}>IDBI Bank</option>
                                        <option value="IDFC" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "IDFC" ? "selected" : "" }}>IDFC First Bank</option>
                                        <option value="South" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "South" ? "selected" : "" }}>South Indian Bank</option>
                                        <option value="City" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "City" ? "selected" : "" }}>City Union Bank</option>
                                        <option value="HSBC" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "HSBC" ? "selected" : "" }}>HSBC Bank</option>
                                        <option value="Overseas" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Overseas" ? "selected" : "" }}>Indian Overseas Bank</option>
                                        <option value="Kotak" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Kotak" ? "selected" : "" }}>Kotak Mahindra Bank</option>
                                        <option value="PNB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "PNB" ? "selected" : "" }}>Punjab National Bank (PNB)</option>
                                        <option value="SBI" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "SBI" ? "selected" : "" }}>State Bank of India (SBI)</option>
                                        <option value="Syndicate" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Syndicate" ? "selected" : "" }}>Syndicate Bank</option>
                                        <option value="UCO" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "UCO" ? "selected" : "" }}>UCO Bank</option>
                                        <option value="Union" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Union" ? "selected" : "" }}>Union Bank of India</option>
                                        <option value="Yes" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Yes" ? "selected" : "" }}>Yes Bank</option>
                                        <option value="Indian" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Indian" ? "selected" : "" }}>Indian Bank</option>
                                        <option value="Central" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Central" ? "selected" : "" }}>Central Bank of India</option>
                                        <option value="PunjabSind" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "PunjabSind" ? "selected" : "" }}>Punjab & Sind Bank</option>
                                        <option value="AUSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "AUSFB" ? "selected" : "" }}>AU Small Finance Bank</option>
                                        <option value="Equitas" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Equitas" ? "selected" : "" }}>Equitas Small Finance Bank</option>
                                        <option value="Ujjivan" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Ujjivan" ? "selected" : "" }}>Ujjivan Small Finance Bank</option>
                                        <option value="Jana" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Jana" ? "selected" : "" }}>Jana Small Finance Bank</option>
                                        <option value="Suryoday" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Suryoday" ? "selected" : "" }}>Suryoday Small Finance Bank</option>
                                        <option value="CapitalSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "CapitalSFB" ? "selected" : "" }}>Capital Small Finance Bank</option>
                                        <option value="NorthEastSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "NorthEastSFB" ? "selected" : "" }}>North East Small Finance Bank</option>
                                        <option value="UtkarshSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "UtkarshSFB" ? "selected" : "" }}>Utkarsh Small Finance Bank</option>
                                        <option value="ESAFSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "ESAFSFB" ? "selected" : "" }}>ESAF Small Finance Bank</option>
                                        <option value="FincareSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "FincareSFB" ? "selected" : "" }}>Fincare Small Finance Bank</option>
                                        <option value="ShivalikSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "ShivalikSFB" ? "selected" : "" }}>Shivalik Small Finance Bank</option>
                                        <option value="Citi" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Citi" ? "selected" : "" }}>Citi Bank</option>
                                        <option value="Deutsche" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Deutsche" ? "selected" : "" }}>Deutsche Bank</option>
                                        <option value="Standard" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Standard" ? "selected" : "" }}>Standard Chartered Bank</option>
                                        <option value="BankAmerica"{{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "BankAmerica" ? "selected" : "" }}>Bank of America</option>
                                        <option value="Barclays" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Barclays" ? "selected" : "" }}>Barclays Bank</option>
                                        <option value="BNP" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "BNP" ? "selected" : "" }}>BNP Paribas</option>
                                        <option value="DBS" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "DBS" ? "selected" : "" }}>DBS Bank</option>
                                        <option value="RBS" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "RBS" ? "selected" : "" }}>RBS</option>
                                        <option value="Tamilnad" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Tamilnad" ? "selected" : "" }}>Tamilnad Mercantile Bank</option>
                                        <option value="RBL" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "RBL" ? "selected" : "" }}>RBL Bank</option>
                                        <option value="Nainital" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Nainital" ? "selected" : "" }}>Nainital Bank</option>
                                        <option value="Karnataka" {{ isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Karnataka" ? "selected" : "" }}>Karnataka Bank</option>
                                        <option value="Jammu" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Jammu" ? "selected" : "" }}>Jammu & Kashmir Bank</option>
                                        <option value="DCB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "DCB" ? "selected" : "" }}>DCB Bank</option>
                                        <option value="CSB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "CSB" ? "selected" : "" }}>CSB Bank</option>
                                        <option value="Bandhan" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Bandhan" ? "selected" : "" }}>Bandhan Bank</option>
                                        <option value="Airtel" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Airtel" ? "selected" : "" }}>Airtel Payments Bank</option>
                                        <option value="Paytm" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Paytm" ? "selected" : "" }}>Paytm Payments Bank</option>
                                        <option value="IndiaPost" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "IndiaPost" ? "selected" : "" }}>India Post Payments Bank</option>
                                        <option value="Fino" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Fino" ? "selected" : "" }}>Fino Payments Bank</option>
                                        <option value="APGVB" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "APGVB" ? "selected" : "" }}>Andhra Pradesh Grameena Vikas Bank</option>
                                        <option value="Aryavart" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Aryavart" ? "selected" : "" }}>Aryavart Bank</option>
                                        <option value="BarodaUP" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "BarodaUP" ? "selected" : "" }}>Baroda UP Bank</option>
                                        <option value="Kerala" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Kerala" ? "selected" : "" }}>Kerala Gramin Bank</option>
                                        <option value="KarnatakaG" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "KarnatakaG" ? "selected" : "" }}>Karnataka Gramin Bank</option>
                                        <option value="MadhyaPradesh" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "MadhyaPradesh" ? "selected" : "" }}>Madhya Pradesh Gramin Bank</option>
                                        <option value="MaharashtraG" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "MaharashtraG" ? "selected" : "" }}>Maharashtra Gramin Bank</option>
                                        <option value="Rajasthan" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Rajasthan" ? "selected" : "" }}>Rajasthan Marudhara Gramin Bank</option>
                                        <option value="Saraswat" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Saraswat" ? "selected" : "" }}>Saraswat Co-operative Bank</option>
                                        <option value="Abhyudaya" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Abhyudaya" ? "selected" : "" }}>Abhyudaya Co-operative Bank</option>
                                        <option value="Cosmos" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Cosmos" ? "selected" : "" }}>Cosmos Co-operative Bank</option>
                                        <option value="SVC" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "SVC" ? "selected" : "" }}>Shamrao Vithal Co-op Bank (SVC)</option>
                                        <option value="PMC" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "PMC" ? "selected" : "" }}>Punjab & Maharashtra Co-op Bank (PMC)</option>
                                        <option value="NKGSB" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "NKGSB" ? "selected" : "" }}>NKGSB Co-op Bank</option>
                                        <option value="TJSB" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "TJSB" ? "selected" : "" }}>TJSB Sahakari Bank</option>
                                        <option value="CRGB" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "CRGB" ? "selected" : "" }}>CHHATTISGARH Rajya Gramin Bank</option>
                                    </select>
                                <span class="error-message text-danger"></span>
                            </div>
                            {{-- @if(isset($loanApproval) && !$loanApproval->status == "2")
                                
                            @endif --}}
                            @if(!isset($loanDisbursal))
                                    <!-- Submit Button -->

                                @if(isset($loanApproval) && $loanApproval->status == "1")
                                    <!-- Show modal trigger button -->
                                    <div class="col-12 mt-3">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal">
                                        Modify Loan</button>
                                    </div>
                                @else
                                    <!-- Direct submit button -->
                                    <div class="col-12 mt-3">
                                        <button type="submit" id="loanApprovalForm_submitbtn" class="btn btn-primary">Approve Loan</button>
                                    </div>
                                @endif
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

                        <a class="btn btn-{{isset($cashfreeData) && $cashfreeData->status == 'ACTIVE' ? 'success' : 'danger'}}" href="#">
                            e-NACH Status - {{isset($cashfreeData) ? $cashfreeData->status : ''}} 
                            @if(isset($cashfreeData) && $cashfreeData->status == 'FAILED')
                                @php
                                    $reasonFailedData = json_decode($cashfreeData->response_data, true);
                                    $reason = $reasonFailedData['failure_details']['failure_reason'] ?? 'Unknown';
                                    echo '</br>'.'('. $reason . ')';
                                @endphp
                            @endif
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
                                <input type="text" name="utr_no" class="form-control" value="{{isset($loanDisbursal) ? $loanDisbursal->utr_no : ''}}">
                                <span class="error-message text-danger"></span>
                            </div>

                            <div class="col-md-6">
                                <label>E-natch Reference Number </label>
                                <input type="text" name="enach_reference_number" class="form-control" value="{{isset($cashfreeData) && $cashfreeData->status == 'ACTIVE' ? $cashfreeData->reference_id : (isset($loanDisbursal) ? $loanDisbursal->enach_reference_number : '')}}" readonly>
                                <span class="error-message text-danger"></span>
                            </div>
    
                            <div class="col-md-6">
                                <label>Disbursal Amount</label>
                                <input type="number" name="disbursal_amount" class="form-control" max="{{isset($loanApproval) ? round($loanApproval->approval_amount) : 100000}}" readonly required value="{{isset($loanApproval) ? $loanApproval->disbursal_amount : ''}}">
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <div class="col-md-6">
                                <label>Customer Account No</label>
                                <input type="text" name="account_no" class="form-control" required value="{{ $lead->bankDetails->account_number ?? '' }}" readonly>
                                <span class="error-message text-danger"></span>
                            </div>
                    
                            <div class="col-md-6">
                                <label>IFSC Code</label>
                                <input type="text" name="ifsc" class="form-control" required value="{{ $lead->bankDetails->ifsc_code ?? '' }}" readonly>
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
                                    <select name="bank_name" id="bank_name" class="form-control" required readonly>
                                        <option value="">Select a Bank</option>
                                        <option value="Axis" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Axis" ? "selected" : "" }}>Axis Bank</option>
                                        <option value="Baroda" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Baroda" ? "selected" : "" }}>Bank of Baroda</option>
                                        <option value="Maharashtra" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Maharashtra" ? "selected" : "" }}>Bank of Maharashtra</option>
                                        <option value="Canara" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Canara" ? "selected" : "" }}>Canara Bank</option>
                                        <option value="Federal" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Federal" ? "selected" : "" }}>Federal Bank</option>
                                        <option value="HDFC" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "HDFC" ? "selected" : "" }}>HDFC Bank</option>
                                        <option value="Induslnd" {{ isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Induslnd" ? "selected" : "" }}>Induslnd Bank</option>
                                        <option value="ICICI" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "ICICI" ? "selected" : "" }}>ICICI Bank</option>
                                        <option value="IDBI" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "IDBI" ? "selected" : "" }}>IDBI Bank</option>
                                        <option value="IDFC" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "IDFC" ? "selected" : "" }}>IDFC First Bank</option>
                                        <option value="South" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "South" ? "selected" : "" }}>South Indian Bank</option>
                                        <option value="City" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "City" ? "selected" : "" }}>City Union Bank</option>
                                        <option value="HSBC" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "HSBC" ? "selected" : "" }}>HSBC Bank</option>
                                        <option value="Overseas" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Overseas" ? "selected" : "" }}>Indian Overseas Bank</option>
                                        <option value="Kotak" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Kotak" ? "selected" : "" }}>Kotak Mahindra Bank</option>
                                        <option value="PNB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "PNB" ? "selected" : "" }}>Punjab National Bank (PNB)</option>
                                        <option value="SBI" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "SBI" ? "selected" : "" }}>State Bank of India (SBI)</option>
                                        <option value="Syndicate" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Syndicate" ? "selected" : "" }}>Syndicate Bank</option>
                                        <option value="UCO" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "UCO" ? "selected" : "" }}>UCO Bank</option>
                                        <option value="Union" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Union" ? "selected" : "" }}>Union Bank of India</option>
                                        <option value="Yes" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Yes" ? "selected" : "" }}>Yes Bank</option>
                                        <option value="Indian" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Indian" ? "selected" : "" }}>Indian Bank</option>
                                        <option value="Central" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Central" ? "selected" : "" }}>Central Bank of India</option>
                                        <option value="PunjabSind" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "PunjabSind" ? "selected" : "" }}>Punjab & Sind Bank</option>
                                        <option value="AUSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "AUSFB" ? "selected" : "" }}>AU Small Finance Bank</option>
                                        <option value="Equitas" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Equitas" ? "selected" : "" }}>Equitas Small Finance Bank</option>
                                        <option value="Ujjivan" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Ujjivan" ? "selected" : "" }}>Ujjivan Small Finance Bank</option>
                                        <option value="Jana" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Jana" ? "selected" : "" }}>Jana Small Finance Bank</option>
                                        <option value="Suryoday" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Suryoday" ? "selected" : "" }}>Suryoday Small Finance Bank</option>
                                        <option value="CapitalSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "CapitalSFB" ? "selected" : "" }}>Capital Small Finance Bank</option>
                                        <option value="NorthEastSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "NorthEastSFB" ? "selected" : "" }}>North East Small Finance Bank</option>
                                        <option value="UtkarshSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "UtkarshSFB" ? "selected" : "" }}>Utkarsh Small Finance Bank</option>
                                        <option value="ESAFSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "ESAFSFB" ? "selected" : "" }}>ESAF Small Finance Bank</option>
                                        <option value="FincareSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "FincareSFB" ? "selected" : "" }}>Fincare Small Finance Bank</option>
                                        <option value="ShivalikSFB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "ShivalikSFB" ? "selected" : "" }}>Shivalik Small Finance Bank</option>
                                        <option value="Citi" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Citi" ? "selected" : "" }}>Citi Bank</option>
                                        <option value="Deutsche" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Deutsche" ? "selected" : "" }}>Deutsche Bank</option>
                                        <option value="Standard" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Standard" ? "selected" : "" }}>Standard Chartered Bank</option>
                                        <option value="BankAmerica"{{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "BankAmerica" ? "selected" : "" }}>Bank of America</option>
                                        <option value="Barclays" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Barclays" ? "selected" : "" }}>Barclays Bank</option>
                                        <option value="BNP" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "BNP" ? "selected" : "" }}>BNP Paribas</option>
                                        <option value="DBS" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "DBS" ? "selected" : "" }}>DBS Bank</option>
                                        <option value="RBS" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "RBS" ? "selected" : "" }}>RBS</option>
                                        <option value="Tamilnad" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Tamilnad" ? "selected" : "" }}>Tamilnad Mercantile Bank</option>
                                        <option value="RBL" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "RBL" ? "selected" : "" }}>RBL Bank</option>
                                        <option value="Nainital" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Nainital" ? "selected" : "" }}>Nainital Bank</option>
                                        <option value="Karnataka" {{ isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Karnataka" ? "selected" : "" }}>Karnataka Bank</option>
                                        <option value="Jammu" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "Jammu" ? "selected" : "" }}>Jammu & Kashmir Bank</option>
                                        <option value="DCB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "DCB" ? "selected" : "" }}>DCB Bank</option>
                                        <option value="CSB" {{ isset($lead->bankDetails->bank_name) && $lead->bankDetails->bank_name  == "CSB" ? "selected" : "" }}>CSB Bank</option>
                                        <option value="Bandhan" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Bandhan" ? "selected" : "" }}>Bandhan Bank</option>
                                        
                                        <option value="Airtel" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Airtel" ? "selected" : "" }}>Airtel Payments Bank</option>
                                        <option value="Paytm" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Paytm" ? "selected" : "" }}>Paytm Payments Bank</option>
                                        <option value="IndiaPost" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "IndiaPost" ? "selected" : "" }}>India Post Payments Bank</option>
                                        <option value="Fino" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Fino" ? "selected" : "" }}>Fino Payments Bank</option>
                                        <option value="APGVB" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "APGVB" ? "selected" : "" }}>Andhra Pradesh Grameena Vikas Bank</option>
                                        <option value="Aryavart" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Aryavart" ? "selected" : "" }}>Aryavart Bank</option>
                                        <option value="BarodaUP" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "BarodaUP" ? "selected" : "" }}>Baroda UP Bank</option>
                                        <option value="Kerala" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Kerala" ? "selected" : "" }}>Kerala Gramin Bank</option>
                                        <option value="KarnatakaG" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "KarnatakaG" ? "selected" : "" }}>Karnataka Gramin Bank</option>
                                        <option value="MadhyaPradesh" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "MadhyaPradesh" ? "selected" : "" }}>Madhya Pradesh Gramin Bank</option>
                                        <option value="MaharashtraG" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "MaharashtraG" ? "selected" : "" }}>Maharashtra Gramin Bank</option>
                                        <option value="Rajasthan" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Rajasthan" ? "selected" : "" }}>Rajasthan Marudhara Gramin Bank</option>
                                        <option value="Saraswat" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Saraswat" ? "selected" : "" }}>Saraswat Co-operative Bank</option>
                                        <option value="Abhyudaya" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Abhyudaya" ? "selected" : "" }}>Abhyudaya Co-operative Bank</option>
                                        <option value="Cosmos" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "Cosmos" ? "selected" : "" }}>Cosmos Co-operative Bank</option>
                                        <option value="SVC" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "SVC" ? "selected" : "" }}>Shamrao Vithal Co-op Bank (SVC)</option>
                                        <option value="PMC" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "PMC" ? "selected" : "" }}>Punjab & Maharashtra Co-op Bank (PMC)</option>
                                        <option value="NKGSB" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "NKGSB" ? "selected" : "" }}>NKGSB Co-op Bank</option>
                                        <option value="TJSB" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "TJSB" ? "selected" : "" }}>TJSB Sahakari Bank</option>
                                        <option value="CRGB" {{isset($lead->bankDetails->bank_name) &&  $lead->bankDetails->bank_name  == "CRGB" ? "selected" : "" }}>CHHATTISGARH Rajya Gramin Bank</option>
                                    </select>
                                <span class="error-message text-danger"></span>
                            </div>                        
                    
                            <div class="col-md-6">
                                <label>Branch</label>
                                <input type="text" name="branch" class="form-control" required value="{{isset($loanDisbursal) ? $loanDisbursal->branch : ''}}">
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
                    @if(isset($cashfreeData) && $cashfreeData->status == 'ACTIVE')
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmModal2">
                                Raise Payment Request</button>
                            </div>
                            
                            @if(isset($cfreeSubsData) && $cfreeSubsData->status == 'Cancelled')
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-success">
                                    Raised Payment Request Cancelled</button>
                                </div>
                            @else
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmModal3">
                                    Cancel Raised Payment Request</button>
                                </div>
                            @endif
                            
                        </div>
                    @endif

                    <!-- Insert Table Section Between Buttons and Inputs -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-body p-3 table-fit">
                                    <div class="table-responsive-sm">
                                        <table class="table table-bordered table-striped mb-0 align-middle text-center small-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Created On</th>
                                                    <th>Subscription ID</th>
                                                    <th>Scheduled On</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($allcfreeSubPayReqData as $req)
                                                    <tr>
                                                        <td>{{ \Carbon\Carbon::parse($req->created_at)->format('d M Y') }}</td>
                                                        <td>{{ $req->subscription_id ?? '--' }}</td>
                                                        <td>{{ !empty($req->payment_schedule_date) ? \Carbon\Carbon::parse($req->payment_schedule_date)->format('d M Y') : '--' }}</td>
                                                        <td>INR {{ number_format($req->payment_amount ?? 0, 2) }}</td>
                                                        <td>
                                                            @if(strtolower($req->status) === 'cancelled')
                                                                <span class="text-danger fw-semibold">Cancelled</span>
                                                            @elseif(strtolower($req->status) === 'active')
                                                                <span class="text-success fw-semibold">Active</span>
                                                            @else
                                                                <span class="text-secondary">{{ ucfirst($req->status ?? '--') }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach

                                                @if(empty($allcfreeSubPayReqData))
                                                    <tr>
                                                        <td colspan="9" class="text-center text-muted">No payment requests found</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                                    <th>DPD</th>
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
                                        <td>@php
                                                $dpd = 0;
                                                if ($collection->status == 'Closed' || $collection->status == 'Settlement') {
                                                    $repayDate = \Carbon\Carbon::parse($collection->repay_date);
                                                    $collectionDate = \Carbon\Carbon::parse($collection->collection_date);

                                                    if ($repayDate->greaterThan($collectionDate)) {
                                                        $dpd = 0;
                                                    } else {
                                                        $dpd = $collectionDate->diffInDays($repayDate);
                                                    }
                                                }
                                            @endphp

                                            @if($collection->status == 'Closed' || $collection->status == 'Settlement')
                                                <span style="color: {{ $dpd > 7 ? 'red' : 'green' }};">
                                                    {{ $dpd }}
                                                </span>
                                            @else
                                                0
                                            @endif
                                        </td>
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
                                    <th>DPD</th>
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
                                    <td>{{ $loans->days_after_due }}</td>
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
                                    <input type="hidden" id="lastname_{{ $lead->user->id }}" value="{{ !empty($lead->user->lastname) ? $lead->user->lastname : $lead->user->firstname }}">
                                    <input type="hidden" id="mobile_{{ $lead->user->id }}" value="{{ $lead->user->mobile }}">
                                    <input type="hidden" id="dob_{{ $lead->user->id }}" value="{{ $panData->date_of_birth ?? '' }}">
                                    <input type="hidden" id="pan_{{ $lead->user->id }}" value="{{ $panData->pan ?? '' }}">
                                    <input type="hidden" id="gender_{{ $lead->user->id }}" value="{{ isset($aadharData->gender) && $aadharData->gender == 'M' ? '1' : '2' }}">
                                    <input type="hidden" id="houseno_{{ $lead->user->id }}" value="{{ !empty($lead->addressDetails->house_no) ? $lead->addressDetails->house_no : '01' }}">
                                    <input type="hidden" id="city_{{ $lead->user->id }}" value="{{ $lead->addressDetails->city ?? '' }}">
                                    <input type="hidden" id="pincode_{{ $lead->user->id }}" value="{{ $lead->addressDetails->pincode ?? '' }}">
                                    <input type="hidden" id="state_{{ $lead->user->id }}" value="{{ $lead->addressDetails->state ?? '' }}">
                                    <input type="hidden" id="verify_{{ $lead->user->id }}" value="{{ 2 }}">
                                    <td>
                                        @if (empty($experianCreditBureau->response_data))
                                        <button type="button" id="checkBtn_{{ $lead->user->id }}" onclick="checkCreditScore({{ $lead->user->id }})" class="btn btn-danger">Check Credit Score</button>
                                        @endif
                                        @if (!empty($experianCreditBureau->response_data))

                                            <button class="btn btn-primary view-report" 
                                                    data-id="{{ $experianCreditBureau->lead_id }}" 
                                                    data-url="{{ route('admin.creditbureau.show', $experianCreditBureau->lead_id) }}">View Report
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="Bsareport" role="tabpanel" aria-labelledby="Bsareport-tab">
                    <h3>BSA Report</h3>
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
                                    <input type="hidden" id="bank_name_bsa" value="{{ $lead->bankDetails->bank_name ?? '' }}">
                                    
                                    @if(isset($digitapBankRequestData) && $digitapBankRequestData->status == 'processing')
                                        {{ $digitapBankRequestData->status }}
                                    @elseif(isset($digitapBankRequestData) && $digitapBankRequestData->status == 'ReportGenerated')
                                        <button type="button" id="checkBSABtn3_{{ $lead->id }}" onclick="checkBSAScoreStatus({{ $lead->id }})" class="btn btn-success">Check Status</button>
                                    @elseif(isset($digitapBankRequestData) && $digitapBankRequestData->status == 'ReportGenerated')
                                        <button type="button" id="checkBSABtn3_{{ $lead->id }}" onclick="checkBSAScoreStatus({{ $lead->id }})" class="btn btn-info">View Report</button>
                                    @elseif(isset($digitapBankRequestData) && $digitapBankRequestData->status == 'xlsx_report_saved' && !empty($digitapBankRequestData->report_xlsx_data))
                                        <a href="{{ url('/admin/digitap_reports/'.$digitapBankRequestData->report_xlsx_data) }}"
                                        target="_blank" class="btn btn-primary btn-check-status"
                                        data-lead-id="{{ $lead->id }}" data-excel-url="{{ url('/admin/digitap_reports/'.$digitapBankRequestData->report_xlsx_data) }}">View Excel Report</a>

                                    @elseif(isset($digitapBankRequestData) && $digitapBankRequestData->status == 'json_report_saved' && !empty($digitapBankRequestData->report_json_data))
                                        <button class="btn btn-primary view-bsa-report" 
                                                data-id="{{ $lead->id }}" 
                                                data-url="{{ route('admin.digitap.bsaDataShow', $lead->id) }}">View Report
                                        </button>
                                    @elseif(isset($digitapBankRequestData) && $digitapBankRequestData->status == 'report_saved' && empty($digitapBankRequestData->report_json_data))
                                        <button type="button" id="checkBSABtn3_{{ $lead->id }}" onclick="checkBSAScoreStatus({{ $lead->id }})" class="btn btn-success">Check Status</button>
                                    @elseif(isset($digitapBankRequestData) && $digitapBankRequestData->status == 'TxnDateRange')
                                        <span>No bank transactions in the expected date range.</span>
                                    @else
                                        <button type="button" id="checkBSABtn2_{{ $lead->id }}" onclick="checkBSAScore({{ $lead->id }})" class="btn btn-danger">Check BSA Report</button>
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

<!-- Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" style="max-width: 95%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Experian Report</h5>
        <button type="button" class="btn-close" onclick="window.location.reload();" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="reportContent">
        <!-- HTML content will be injected here -->
        <div class="text-center text-muted">Loading...</div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="window.location.reload();" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="printDiv('reportContent')">Print</button>
      </div>
      
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Confirm Loan Approval Modification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                Are you sure, you want to modify this approved loan ?
            </div>

            <div class="modal-footer">
                <button type="submit" id="loanApprovalForm_submitbtn" value='1' class="btn btn-primary">Yes, Continue</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <!-- Form submission -->
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal 2-->
<div class="modal fade" id="confirmModal2" tabindex="-1" aria-labelledby="confirmModal2Label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content raise-payment-modal">

      <!-- Header -->
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold" id="confirmModal2Label">Raise Payment Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">

        <!-- Subscription ID -->
        @php $subs_id = ''; @endphp
        @if(isset($cashfreeData) && $cashfreeData->status == 'ACTIVE')
        @endif
        <div class="mb-3">
          <label class="form-label fw-medium">Subscription ID</label>
          <select name="subscription_id" id="alt_sub_id" class="form-select">
            <option value="">-- Select Subscription ID --</option>

                @foreach($allcfreeSubData as $v1)
                    @php
                        $relatedPayments = $allcfreeSubPayReqData->where('subscription_id', $v1->alt_subscription_id);
                        $isCancelled = $relatedPayments->contains(fn($p) => strtolower($p->status ?? '') === 'cancelled');

                        if ($relatedPayments->isEmpty()) {
                            $label = 'Raise Payment Request';
                            $status = 'no_request';
                        } elseif ($isCancelled) {
                            $label = 'Payment Cancelled';
                            $status = 'cancelled';
                        } else {
                            $label = 'Payment Request Raised';
                            $status = 'active';
                        }
                    @endphp

                    <option value="{{ $v1->alt_subscription_id }}" data-status="{{ $status }}"
                        {{ $subs_id == $v1->alt_subscription_id ? 'selected' : '' }}>
                        {{ $v1->alt_subscription_id }}
                    </option>
                @endforeach
            </select>
          
        </div>

        <!-- Payment and Max Amount -->
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-medium">Payment Amount</label>
            <div class="input-group">
              <span class="input-group-text">₹</span>
              <input type="number" class="form-control" placeholder="Enter amount">
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-medium">Maximum Amount</label>
            <div class="input-group">
              <span class="input-group-text">₹</span>
              <input type="text" class="form-control" value="100000" readonly>
            </div>
          </div>
        </div>

        <!-- Schedule -->
        <div class="mb-3">
          <label class="form-label fw-medium">Schedule On</label>
            <div class="input-group">
                <input type="date" class="form-control" id="schedule_date" name="schedule_date" >
                <span class="input-group-text"><i class="bi bi-calendar"></i></span>
            </div>
        </div>

        <!-- Remarks -->
        <div class="mb-2">
          <label class="form-label fw-medium">Remarks <span class="text-muted small">(Optional)</span></label>
          <textarea class="form-control" rows="3" maxlength="200" placeholder="A maximum of 200 characters are allowed."></textarea>
        </div>

        <div class="text-muted small">
          A maximum of 200 characters are allowed.
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer border-0 d-flex justify-content-end">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="raisePaymentBtn">Raise Payment</button>
      </div>

    </div>
  </div>
</div>

<!-- Confirmation Modal 2-->
<div class="modal fade" id="confirmModal3" tabindex="-1" aria-labelledby="confirmModal3Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModal3Label">Confirm Cancellation Raised Payment Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p>Select the subscription you want to cancel:</p>

                <div class="mb-3">
                    <label class="form-label fw-medium">Subscription ID</label>
                    <select class="form-select" id="cancel_subscription_id" name="cancel_subscription_id">
                        <option value="">-- Select Subscription ID --</option>

                        @foreach($allcfreeSubData as $v1)
                            @php
                                // Find related payments
                                $relatedPayments = $allcfreeSubPayReqData->where('subscription_id', $v1->alt_subscription_id);

                                // Check if any payment is cancelled
                                $isCancelled = $relatedPayments->contains(function ($payment) {
                                    return isset($payment->status) && strtolower($payment->status) === 'cancelled';
                                });

                                // Determine status
                                if ($relatedPayments->isEmpty()) {
                                    $label = 'No Request Raised';
                                    $status = 'no_request';
                                } elseif ($isCancelled) {
                                    $label = 'Payment Cancelled';
                                    $status = 'cancelled';
                                } else {
                                    $label = 'Active Request';
                                    $status = 'active';
                                }
                            @endphp

                            <option 
                                value="{{ $v1->alt_subscription_id }}"
                                data-status="{{ $status }}">
                                {{ $v1->alt_subscription_id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <p class="text-danger mt-2 small">
                    Only subscriptions with an active payment request can be cancelled.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" id="cancelPaymentBtn" class="btn btn-primary">Yes, Cancel It</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <!-- Form submission -->
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal 3-->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="copyToast" class="toast align-items-center text-white bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body">
        Link copied to clipboard!
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

<!-- Modal -->
@if(isset($digitapBankRequestData) && $digitapBankRequestData->status == 'report_saved')
<div class="modal fade" id="reportbsaModal" tabindex="-1" aria-labelledby="reportbsaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" style="max-width: 95%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Digitap BSA Report</h5>
        <button type="button" class="btn-close" onclick="window.location.reload();" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="reportbsaContent">
        <!-- HTML content will be injected here -->
        <div class="text-center text-muted">Loading...</div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="window.location.reload();" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="printbsaDiv('reportbsaContent')">Print</button>
      </div>
      
    </div>
  </div>
</div>
@else

@endif
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

            if (statusVal == '1') {
                if ($("#loanApprovalForm").valid()) {
                    $('#loanApprovalForm')[0].submit();
                }

            } else if (statusVal == '2') {
                $("#loanApprovalForm").validate().resetForm();
                $(".is-invalid").removeClass("is-invalid");

                $('#loanApprovalForm')[0].submit();
            } else if(statusVal == '3'){
                $("#loanApprovalForm").validate().resetForm();
                $(".is-invalid").removeClass("is-invalid");

                $('#loanApprovalForm')[0].submit();
            } else {
                if ($("#loanApprovalForm").valid()) {
                    $('#loanApprovalForm')[0].submit();
                }
            }

            if(statusVal != '0'){
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
                        },
                        branch: {
                            required:true,
                        },
                        bank_acc_no: {
                            required:true,
                        },
                        ifsccode: {
                            required:true,
                        },
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
                        },
                        branch: {
                            required: "Branch is required"
                        },
                        bank_acc_no: {
                            required: "Bank Account No is required"
                        },
                        ifsccode: {
                            required: "IFSC Code is required"
                        }
                    }
                });
            }


            $.validator.addMethod("ifscRegex", function(value, element) {
                return /^[A-Z]{4}0[A-Z0-9]{6}$/.test(value);
            }, "Enter a valid IFSC code.");
        });

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
        if (statusSelect.value === 'Closed' && enteredPrincipal < remainingPrincipal) {
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
        } else if (statusSelect.value === 'Closed' && currentVal < remainingPrincipal) {
            principalError.textContent = 'Principal amount cannot be less than the remaining principal amount {{ (!empty($loans->remaining_principal)) ? number_format($loans->remaining_principal, 2) : 0 }}';
            principalError.classList.remove('error-message');
        }
    });

    statusSelect.addEventListener('change', function() {
        // Re-validate on status change, especially for the 'Closed' scenario
        principalInput.dispatchEvent(new Event('input'));
    });

    function checkCreditScore(id) {

            // Disable button immediately on click
        var btn = document.getElementById('checkBtn_' + id);
        btn.disabled = true;
        btn.innerHTML = "Processing...";
        btn.classList.remove("btn-danger");
        btn.classList.add("btn-secondary"); // grey look while processing

        var user_id = document.getElementById('userid_'+id).value;
        var loan_no = document.getElementById('loanno_'+id).value;
        
        var firstname = document.getElementById('firstname_'+id).value;
        var lastname = document.getElementById('lastname_'+id).value;
        if(!lastname){ lastname = firstname; }
        var mobile = document.getElementById('mobile_'+id).value;
        var date_of_birth = document.getElementById('dob_'+id).value;
        var dob = date_of_birth.replace(/-/g, "");
        var pan = document.getElementById('pan_'+id).value;
        var gender = document.getElementById('gender_'+id).value;
        var house_no = document.getElementById('houseno_'+id).value;
        var city = document.getElementById('city_'+id).value;
        var pincode = document.getElementById('pincode_'+id).value;
        var state = document.getElementById('state_'+id).value;
        var verify = document.getElementById('verify_'+id).value;

        if(!loan_no || !user_id || !firstname || !lastname || !mobile || !dob || !pan || !house_no || !city || !pincode || !state || !gender){
            alert("Something went wrong....");

            // Re-enable button if validation fails
            btn.disabled = false;
            btn.innerHTML = "Check Credit Score";
            btn.classList.remove("btn-secondary");
            btn.classList.add("btn-danger");
            return false;
        }
        

        if(firstname && lastname && mobile && dob && pan && gender && house_no && city && pincode && state && loan_no && user_id){
            $.ajax({
                url: "{{ route('admin.creditbureau.checkReport') }}",
                type: "GET",
                data: {
                    firstname : firstname, lastname : lastname, mobile : mobile, dob : dob, pan : pan, gender : gender, house_no : house_no, city : city, pincode : pincode, state : state, loan_no : loan_no, user_id : user_id, verify : verify,
                },
                success: function(response) {
                    alert("Success");
                    location.reload();
                },
                error: function() {
                    alert("Request failed !!");

                    // Re-enable button if error occurs
                    btn.disabled = false;
                    btn.innerHTML = "Check Credit Score";
                    btn.classList.remove("btn-secondary");
                    btn.classList.add("btn-danger");
                }
            });
        }else{
            alert("Error, required data is incomplete so can not process it.");
        }
        
    }

    function checkBSAScore(id) {
        // Disable button immediately on click
        var btn2 = document.getElementById('checkBSABtn2_' + id);
        console.log('btn : '+btn2);
        if(!btn2) {
            console.error("Button not found for id: " + id);
            return;
        }
        btn2.disabled = true;
        btn2.innerHTML = "Processing...";
        btn2.classList.remove("btn-danger");
        btn2.classList.add("btn-secondary"); // grey look while processing

        var bank_statement_filename = document.getElementById('bank_statement_filename').value;
        var bank_statement = document.getElementById('bank_statement').value;
        var bank_statement_pass = document.getElementById('bank_statement_pass').value;
        var bank_name_bsa = document.getElementById('bank_name_bsa').value;

        if(!bank_statement){
            alert("Something went wrong....");

            // Re-enable button if validation fails
            btn2.disabled = false;
            btn2.innerHTML = "Check Credit Score";
            btn2.classList.remove("btn-secondary");
            btn2.classList.add("btn-danger");
            return false;
        }

        if(bank_statement){
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "{{ route('admin.digitap.digitapbsuploaddoc') }}",
                type: "POST",
                data: {
                    bank_statement_filename : bank_statement_filename, bank_statement : bank_statement, bank_statement_pass : bank_statement_pass, loan_id : id, bank_name : bank_name_bsa
                },
                success: function(response) {
                    alert("Success");
                    location.reload();
                },
                error: function() {
                    alert("Request failed!");

                    // Re-enable button if error occurs
                    btn2.disabled = false;
                    btn2.innerHTML = "Check Credit Score";
                    btn2.classList.remove("btn-secondary");
                    btn2.classList.add("btn-danger");
                }
            });
        }else{
            alert("Error, required data is incomplete so can not process it.");
        }
        
    }

    function checkBSAScoreStatus(id) {
        // Disable button immediately on click
        var btn3 = document.getElementById('checkBSABtn3_' + id);
        console.log('btn : '+btn3);
        if(!btn3) {
            console.error("Button not found for id: " + id);
            return;
        }
        btn3.disabled = true;
        btn3.innerHTML = "Processing...";
        btn3.classList.remove("btn-danger");
        btn3.classList.add("btn-secondary"); // grey look while processing

        if(bank_statement){
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "{{ route('admin.digitap.digitapbsuploaddocstatus') }}",
                type: "POST",
                data: {
                    loan_id : id
                },
                success: function(response) {
                    alert("Success");
                    location.reload();
                },
                error: function() {
                    alert("Request failed!");

                    // Re-enable button if error occurs
                    btn2.disabled = false;
                    btn2.innerHTML = "Check Status";
                    btn2.classList.remove("btn-secondary");
                    btn2.classList.add("btn-danger");
                }
            });
        }else{
            alert("Error, required data is incomplete so can not process it.");
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
<script>
    $(document).on('click', '.view-report', function () {
        const url = $(this).data('url');

        $('#reportContent').html('<div class="text-center text-muted">Loading...</div>');
        $('#reportModal').modal('show');

        $.get(url, function (response) {
            $('#reportContent').html(response);
        }).fail(function () {
            $('#reportContent').html('<div class="text-danger">Failed to load report.</div>');
        });
    });

    function printDiv(divId) {
        var printContents = document.getElementById(divId).innerHTML;
        var originalContents = document.body.innerHTML;

        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload(); // Optional: refresh to restore events
    }
</script>
<script>
    $(document).on('click', '.view-bsa-report', function () {
        const url = $(this).data('url');

        $('#reportbsaContent').html('<div class="text-center text-muted">Loading...</div>');
        $('#reportbsaModal').modal('show');

        $.get(url, function (response) {
            $('#reportbsaContent').html(response);
        }).fail(function () {
            $('#reportbsaContent').html('<div class="text-danger">Failed to load report.</div>');
        });
    });

    function printbsaDiv(divId) {
        var printbsaContents = document.getElementById(divId).innerHTML;
        var originalbsaContents = document.body.innerHTML;

        document.body.innerHTML = printbsaContents;
        window.print();
        document.body.innerHTML = originalbsaContents;
        location.reload(); // Optional: refresh to restore events
    }

    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('schedule_date');
        if (!dateInput) return;

        const today = new Date();
        const maxDate = new Date();
        maxDate.setDate(today.getDate() + 14); // 14 days from today

        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = ('0' + (date.getMonth() + 1)).slice(-2);
            const day = ('0' + date.getDate()).slice(-2);
            return `${year}-${month}-${day}`;
        };

        dateInput.min = formatDate(today);
        dateInput.max = formatDate(maxDate);
    });

    document.addEventListener('DOMContentLoaded', function() {

        // Raise Payment Request
        document.getElementById('raisePaymentBtn').addEventListener('click', function() {
            const subscription_id = document.getElementById('alt_sub_id').value;
            const payment_amount = document.querySelector('input[placeholder="Enter amount"]').value;
            const schedule_on = document.getElementById('schedule_date').value;
            const remarks = document.querySelector('textarea').value;

            if (!payment_amount || !schedule_on) {
                alert("Please fill required fields.");
                return;
            }

            fetch("{{ route('admin.leads.createenach') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    subscription_id,
                    payment_amount,
                    schedule_on,
                    remarks,
                })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status) {
                    location.reload();
                }
            })
            .catch(() => alert("Something went wrong."));
        });


        // Cancel Raised Payment Request
        document.getElementById('cancelPaymentBtn').addEventListener('click', function() {
            const subscription_id = document.getElementById('cancel_subscription_id').value;

            fetch("{{ route('admin.leads.cancelenach') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ subscription_id })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status) {
                    location.reload();
                }
            })
            .catch(() => alert("Something went wrong."));
        });

    });
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const statusSelect = document.getElementById("loanApprovalForm_status");
    const rejectDiv = document.getElementById("rejectReasonDiv");
    const rejectReason = document.getElementById("rejection_reason");

    function toggleRejectReason() {
        if (statusSelect.value === "2") { // Rejected
            rejectDiv.style.display = "block";
            rejectReason.setAttribute("required", "required");
        } else {
            rejectDiv.style.display = "none";
            rejectReason.removeAttribute("required");
            rejectReason.value = ""; // clear old selection
        }
    }

    // Initial check on load
    toggleRejectReason();

    // Change listener
    statusSelect.addEventListener("change", toggleRejectReason);
});
</script>
<script>
document.getElementById('editAddressBtn').addEventListener('click', function() {
    document.getElementById('addressView').style.display = 'none';
    document.getElementById('addressEdit').style.display = 'block';
});

document.getElementById('cancelEditBtn').addEventListener('click', function() {
    document.getElementById('addressView').style.display = 'block';
    document.getElementById('addressEdit').style.display = 'none';
});

document.getElementById('addressForm').addEventListener('submit', function(e) {
    e.preventDefault();

    let formData = new FormData(this);

    fetch("{{ route('admin.leads.updateAddress') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // Update table view values
            document.getElementById('v_relation').innerText = data.data.relation;
            document.getElementById('v_relative_name').innerText = data.data.relative_name;
            document.getElementById('v_contact_number').innerText = data.data.contact_number;

            // Switch back to view mode
            document.getElementById('addressEdit').style.display = 'none';
            document.getElementById('addressView').style.display = 'block';
        } else {
            alert(data.message || "Update failed!");
        }
    })
    .catch(err => console.error('Error:', err));
});

function validateApprovalAmount(input) {
    const errorSpan = input.nextElementSibling;
    if (input.value > 40000) {
        errorSpan.textContent = "Approval amount cannot exceed 40,000.";
        input.value = 0;
    } else {
        errorSpan.textContent = "";
    }
}

function validateProcessingFee(input){
    const errorSpan = input.nextElementSibling;
    if (input.value < 7 || input.value > 10) {
        errorSpan.textContent = "Processing fee must be between 7% and 10%.";
        input.value = 0;
    } else {
        errorSpan.textContent = '';
    }
}

function validateROI(input){
    const errorSpan = input.nextElementSibling;

    if (input.value < 0.75 || input.value > 1) {
        errorSpan.textContent = "ROI must be between 0.75% and 1%.";
    } else {
        errorSpan.textContent = '';
    }
}

function validateCIBIL(input){
    const errorSpan = input.nextElementSibling;

    if (input.value < 550) {
        errorSpan.textContent = "Cibil Score must be minimum 550.";
    } else {
        errorSpan.textContent = '';
    }
}

function validateMonthlyIncome(input){
    const errorSpan = input.nextElementSibling;

    if (input.value < 25000) {
        errorSpan.textContent = "Monthly Income must be minimum 25000.";
    } else {
        errorSpan.textContent = '';
    }
}
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const analyzeModal = new bootstrap.Modal(document.getElementById('analyzeModal'));
    const modalBody = document.getElementById('analyzeModalBody');

    document.querySelectorAll('.btn-check-status').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); // Stop default link click

            const leadId = this.dataset.leadId;
            const excelUrl = this.dataset.excelUrl;

            // Open Excel report in a new tab immediately
            window.open(excelUrl, '_blank');

            // Show modal loading
            // modalBody.innerHTML = `
            //     <div class="text-center">
            //         <div class="spinner-border text-primary" role="status"></div>
            //         <p class="mt-2">Checking Digitap Status for Lead #${leadId}...</p>
            //     </div>
            // `;
            // analyzeModal.show();

            // Make API call
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
                // if (data.status) {
                //     const result = data.data || {};
                //     let html = `
                //     <div class="alert alert-success d-flex align-items-center mb-3" style="font-weight:600;">
                //         <i class="bi bi-check-circle-fill me-2"></i> 
                //         ${data.message}
                //     </div>
                //     `;

                //     if (result.digitap) {
                //         html += `
                //         <div class="p-3 rounded mb-3" style="background-color:#e8f5e9;">
                //             <h6 class="fw-bold mb-2 text-success">Digitap Report</h6>
                //             <div class="row mb-2">
                //                 <div class="col-5 fw-bold text-secondary">Approval Amount :</div>
                //                 <div class="col-7 text-dark">${result.digitap.approved_amount ?? '-'}</div>
                //             </div>
                //             <div class="row mb-2">
                //                 <div class="col-5 fw-bold text-secondary">Tag :</div>
                //                 <div class="col-7 text-dark">${result.digitap.salary_or_business_tag ?? '-'}</div>
                //             </div>
                //             <div class="row">
                //                 <div class="col-5 fw-bold text-secondary">Reason :</div>
                //                 <div class="col-7 text-dark">${result.digitap.rejected_reason ?? '-'}</div>
                //             </div>
                //         </div>`;
                //     }

                //     if (result.monthly_salary_check) {
                //         html += `
                //         <div class="p-3 rounded" style="background-color:#fff3cd;">
                //             <h6 class="fw-bold mb-2 text-warning">Monthly Salary</h6>
                //             <div class="row mb-2">
                //                 <div class="col-5 fw-bold text-secondary">Bureau Score :</div>
                //                 <div class="col-7 text-dark">${result.monthly_salary_check.bureau_score ?? '-'}</div>
                //             </div>
                //             <div class="row mb-2">
                //                 <div class="col-5 fw-bold text-secondary">Approval Amount :</div>
                //                 <div class="col-7 text-dark">${result.monthly_salary_check.approved_amount ?? '-'}</div>
                //             </div>
                //             <div class="row mb-2">
                //                 <div class="col-5 fw-bold text-secondary">Tag :</div>
                //                 <div class="col-7 text-dark">${result.monthly_salary_check.salary_or_business_tag ?? '-'}</div>
                //             </div>
                //             <div class="row">
                //                 <div class="col-5 fw-bold text-secondary">Reason :</div>
                //                 <div class="col-7 text-dark">${result.monthly_salary_check.rejected_reason ?? '-'}</div>
                //             </div>
                //         </div>`;
                //     }

                //     if (result.final) {
                //         html += `
                //         <div class="p-3 rounded" style="background-color:#e8f0fe;">
                //             <h6 class="fw-bold mb-2 text-primary">Final Decision</h6>
                //             <div class="row mb-2">
                //                 <div class="col-5 fw-bold text-secondary">Final Approved Amount :</div>
                //                 <div class="col-7 text-dark">${result.final.final_approved_amount ?? '-'}</div>
                //             </div>
                //             <div class="row mb-2">
                //                 <div class="col-5 fw-bold text-secondary">Decision :</div>
                //                 <div class="col-7 text-dark">${result.final.decision ?? '-'}</div>
                //             </div>
                //             <div class="row mb-2">
                //                 <div class="col-5 fw-bold text-secondary">Reason :</div>
                //                 <div class="col-7 text-dark">${result.final.reason ?? '-'}</div>
                //             </div>
                //             <div class="row">
                //                 <div class="col-5 fw-bold text-secondary">Logic :</div>
                //                 <div class="col-7 text-dark">${result.final.logic ?? '-'}</div>
                //             </div>
                //         </div>`;
                //     }

                //     modalBody.innerHTML = html;
                // } else {
                //     modalBody.innerHTML = `
                //         <div class="alert alert-warning text-center">
                //             ⚠️ ${data.message}
                //         </div>`;
                // }
            })
            // .catch(err => {
            //     modalBody.innerHTML = `
            //         <div class="alert alert-danger text-center">
            //             ❌ Error: ${err.message}
            //         </div>`;
            // });
        });
    });
});
</script>

@endpush
