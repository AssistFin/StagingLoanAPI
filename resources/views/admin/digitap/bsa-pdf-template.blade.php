<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BSA Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-size: 14px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-top: 25px;
            margin-bottom: 15px;
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            display: inline-block;
            padding-bottom: 3px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f1f5f9;
        }
        .table-sm td, .table-sm th {
            padding: 6px 10px;
        }

        /* Allow table text to wrap */
        table.table td, table.table th {
            white-space: normal !important;
            word-wrap: break-word;
        }

        /* Optional: make table scroll horizontally if too wide */
        .table-responsive {
            overflow-x: auto;
        }

        /* Limit narration column width but wrap text inside */
        .narration-col {
            max-width: 400px; /* adjust as needed */
            white-space: normal !important;
            word-break: break-word;
        }
    </style>
</head>
<body>
<div class="container py-4">

    {{-- Header --}}
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">Bank Statement Analysis Report</h2>
        <p class="text-muted mb-0">Client Ref No: {{ $data['client_ref_num'] ?? '-' }} | Txn ID: {{ $data['txn_id'] ?? '-' }}</p>
        <small class="text-muted">Period: {{ $data['start_date'] ?? '' }} to {{ $data['end_date'] ?? '' }} ({{ $data['duration_in_month'] ?? '' }} Months)</small>
    </div>

    {{-- Customer Info --}}
    <div class="card p-3">
        <h5 class="section-title">Customer Information</h5>
        <table class="table table-sm table-bordered">
            <tr><th>Name</th><td>{{ $data['customer_info']['name'] ?? '-' }}</td></tr>
            <tr><th>Email</th><td>{{ $data['customer_info']['email'] ?? '-' }}</td></tr>
            <tr><th>Contact</th><td>{{ $data['customer_info']['contact_number'] ?? '-' }}</td></tr>
            <tr><th>Address</th><td>{{ $data['customer_info']['address'] ?? '-' }}</td></tr>
            <tr><th>PAN</th><td>{{ $data['customer_info']['pan'] ?? '-' }}</td></tr>
        </table>
    </div>

    {{-- Accounts --}}
    @foreach($data['accounts'] as $account)
        <div class="card p-3">
            <h5 class="section-title">Account Information</h5>
            <table class="table table-sm table-bordered">
                <tr><th>Bank</th><td>{{ $account['bank'] ?? '-' }}</td></tr>
                <tr><th>Branch</th><td>{{ $account['location'] ?? '-' }}</td></tr>
                <tr><th>IFSC</th><td>{{ $account['ifsc_code'] ?? '-' }}</td></tr>
                <tr><th>MICR</th><td>{{ $account['micr_code'] ?? '-' }}</td></tr>
                <tr><th>Account Number</th><td>{{ $account['account_number'] ?? '-' }}</td></tr>
                <tr><th>Account Type</th><td>{{ $account['account_type'] ?? '-' }}</td></tr>
                <tr><th>Opening Date</th><td>{{ $account['account_opening_date'] ?? '-' }}</td></tr>
                <tr><th>OD Limit</th><td>{{ $account['od_limit'] ?? '-' }}</td></tr>
                <tr><th>Drawing Power</th><td>{{ $account['drawing_power'] ?? '-' }}</td></tr>
                <tr>
                    <th>Transaction Period</th>
                    <td>{{ $account['transaction_start_date'] ?? '-' }} to {{ $account['transaction_end_date'] ?? '-' }}</td>
                </tr>
            </table>

            {{-- Transactions --}}
            <h6 class="section-title">Transactions</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr class="table-primary">
                            <th>Date</th>
                            <th class="narration-col">Narration</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Cheque #</th>
                            <th>Tamper Flag</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($account['transactions'] as $txn)
                        <tr>
                            <td>{{ $txn['date'] }}</td>
                            <td class="narration-col">{{ $txn['narration'] }}</td>
                            <td class="text-end">{{ $txn['amount'] }}</td>
                            <td class="text-end">{{ $txn['balance'] }}</td>
                            <td>{{ $txn['cheque_num'] ?: '-' }}</td>
                            <td>
                                @if($txn['tamper_flag'] == 'GREEN')
                                    <span class="badge bg-success">Green</span>
                                @elseif($txn['tamper_flag'] == 'RED')
                                    <span class="badge bg-danger">Red</span>
                                @else
                                    <span class="badge bg-secondary">{{ $txn['tamper_flag'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    @endforeach

    {{-- Tamper Detection --}}
    <div class="card p-3">
        <h5 class="section-title">Tamper Detection Details</h5>
        @foreach($data['tamper_detection_details'] as $tamper)
            <table class="table table-sm table-bordered mb-3">
                <tr><th>Statement ID</th><td>{{ $tamper['statement_id'] ?? '-' }}</td></tr>
                <tr><th>Filename</th><td>{{ $tamper['filename'] ?? '-' }}</td></tr>
                <tr><th>Original Confidence</th><td>{{ $tamper['original_pdf_confidence_score'] ?? '-' }}</td></tr>
                <tr><th>Secondary Confidence</th><td>{{ $tamper['secondary_pdf_confidence_score'] ?? '-' }}</td></tr>
                <tr><th>Txn Tampering</th><td>{{ $tamper['txn_amt_bal_tampering_detected'] ?? '-' }}</td></tr>
                <tr><th>Balance Difference</th><td>{{ $tamper['overall_balance_difference'] ?? '-' }}</td></tr>
            </table>
        @endforeach
    </div>

    {{-- Analysis Data --}}
    <div class="card p-3">
        @foreach($data['analysis_data'] as $key => $value)
            @if(is_array($value))
            <h5 class="section-title">Analysis Data - {{ ucwords(str_replace('_',' ', $key)) }} </h5>
            <table class="table table-sm table-bordered mb-3">
                @foreach($value as $k1 => $v1)
                    <tr><th>{{  $k1 }}</th>
                        <td>@if(is_array($v1))
                                {{-- If array is associative --}}
                                @foreach($v1 as $subKey => $subVal)
                                    <strong>{{ ucwords(str_replace('_',' ', $subKey)) }}</strong>: {{ is_array($subVal) ? json_encode($subVal) : $subVal }} <br>
                                @endforeach
                            @else
                                {{ $v1 }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
            @else
                {{ $value }}
            @endif
        @endforeach
    </div>

    {{-- Raw Data --}}
    <div class="card p-3">
        <h6 class="section-title">Raw Data</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr class="table-primary">
                        <th>Sr No</th>
                        <th>Date</th>
                        <th class="narration-col">Description</th>
                        <th>Category</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($data['raw_data'] as $raw)
                    <tr>
                        <td>{{ $raw['SN'] }}</td>
                        <td>{{ $raw['Date'] }}</td>
                        <td class="narration-col">{{ $raw['Description'] }}</td>
                        <td class="text-end">{{ $raw['Category'] }}</td>
                        <td class="text-end">{{ $raw['Debit'] }}</td>
                        <td>{{ $raw['Credit'] ?: '-' }}</td>
                        <td>{{ $raw['Balance'] ?: '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>

    {{-- Daily Open Close Balances --}}
    <div class="card p-3">
        @foreach($data['daily_open_close_balances'] as $key2 => $value2)
            <h5 class="section-title">Daily Open Close Balances - {{ ucwords(str_replace('_',' ', $value2['month'])) }} </h5>
            <table class="table table-sm table-bordered mb-3">
                <tbody>
                    @if(is_array($value2))
                        {{-- Loop each transaction --}}
                        @foreach($value2['dailyBalance'] as $subKey11 => $subVal11)
                            @if(is_array($subVal11))
                                @foreach($subVal11 as $k3 => $v3)
                                    <tr>
                                        <th><strong>{{ ucwords(str_replace('_',' ', $k3)) }}</strong></th> 
                                        <td>{{ $v3 }}</td>
                                    </tr>
                                    {{-- Add separator AFTER closing_balance --}}
                                    @if(strtolower($k3) === 'closing_balance')
                                        <tr>
                                            <td colspan="2" style="background:#f8f9fa; height:10px;"></td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    @else
                        <tr>
                            <td colspan="2">{{ $value2 }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endforeach
    </div>

    {{-- Recurrent Transactions --}}
    <div class="card p-3">
        <h5 class="section-title">Recurrent Transactions</h5>

        {{-- Recurrent CR --}}
        <h6>Recurrent Credit</h6>
        @if(!empty($data['recurrent_cr']['recurrent_narration']))
            <table class="table table-sm table-bordered mb-3">
                <thead>
                    <tr>
                        <th>Narration</th>
                        <th>Total CR Sum</th>
                        <th>Total CR Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['recurrent_cr']['recurrent_narration'] as $narration => $details)
                        <tr>
                            <td>{{ $narration }}</td>
                            <td>{{ $details['total_recurrent_cr_sum'] ?? 0 }}</td>
                            <td>{{ $details['total_month_recurrent_cr_count'] ?? 0 }}</td>
                        </tr>
                        {{-- Show individual months --}}
                        @if(!empty($details['individual_month']))
                            <tr>
                                <td colspan="3">
                                    <table class="table table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th>Monthly CR Sum</th>
                                                <th>Monthly CR Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($details['individual_month'] as $month => $monthData)
                                                <tr>
                                                    <td>{{ $month }}</td>
                                                    <td>{{ $monthData['individual_month_recurrent_cr_sum'] ?? 0 }}</td>
                                                    <td>{{ $monthData['individual_month_recurrent_cr_count'] ?? 0 }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted">No recurrent credit records.</p>
        @endif

        {{-- Recurrent DR --}}
        <h6 class="mt-4">Recurrent Debit</h6>
        @if(!empty($data['recurrent_dr']['recurrent_narration']))
            <table class="table table-sm table-bordered mb-3">
                <thead>
                    <tr>
                        <th>Narration</th>
                        <th>Total DR Sum</th>
                        <th>Total DR Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['recurrent_dr']['recurrent_narration'] as $narration => $details)
                        <tr>
                            <td>{{ $narration }}</td>
                            <td>{{ $details['total_recurrent_cr_sum'] ?? 0 }}</td>
                            <td>{{ $details['total_month_recurrent_cr_count'] ?? 0 }}</td>
                        </tr>
                        {{-- Show individual months --}}
                        @if(!empty($details['individual_month']))
                            <tr>
                                <td colspan="3">
                                    <table class="table table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th>Monthly DR Sum</th>
                                                <th>Monthly DR Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($details['individual_month'] as $month => $monthData)
                                                <tr>
                                                    <td>{{ $month }}</td>
                                                    <td>{{ $monthData['individual_month_recurrent_cr_sum'] ?? 0 }}</td>
                                                    <td>{{ $monthData['individual_month_recurrent_cr_count'] ?? 0 }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted">No recurrent debit records.</p>
        @endif
    </div>

    {{-- Loan Analysis --}}
    <div class="card p-3">
        <h5 class="section-title">Loan Analysis Information</h5>
        <table class="table table-sm table-bordered mb-3">
            <thead>
                <tr>
                    <th>Amount</th>
                    <th>Balance</th>
                    <th>Date</th>
                    <th>Narration</th>
                    <th>Category</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['loan_analysis'] as $txn)
                    <tr>
                        <td>{{ $txn['amount'] ?? '' }}</td>
                        <td>{{ $txn['balance'] ?? '' }}</td>
                        <td>{{ $txn['date'] ?? '' }}</td>
                        <td style="max-width:300px; word-wrap:break-word;">
                            {{ $txn['narration'] ?? '' }}
                        </td>
                        <td>{{ $txn['category'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Fraud Analysis --}}
    @foreach($data['fraud_analysis'] as $fraudAnalysis)
        <div class="card p-3">
            <h5 class="section-title">Fraud Analysis Information</h5>
            <table class="table table-sm table-bordered">
                <tr><th>Type</th><td>{{ $fraudAnalysis['type'] ?? '-' }}</td></tr>
                <tr><th>DGBDTIN Code</th><td>{{ ucwords(str_replace('_',' ', $fraudAnalysis['dg_bdtin_code'])) }}</td></tr>
                <tr><th>Result</th><td>{{ ucwords(str_replace('_',' ', $fraudAnalysis['result'])) }}</td></tr>
            </table>

            {{-- Transactions --}}
            @if(!empty($fraudAnalysis['transactions']))
            <h6 class="section-title">Transactions</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr class="table-primary">
                            <th>Transaction Date</th>
                            <th class="narration-col">Narration</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Cheque No</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($fraudAnalysis['transactions'] as $txn1)
                        <tr>
                            <td>{{ $txn1['transaction_date'] }}</td>
                            <td class="narration-col">{{ $txn1['narration'] }}</td>
                            <td class="text-end">{{ $txn1['amount'] }}</td>
                            <td class="text-end">{{ $txn1['balance'] }}</td>
                            <td class="text-end">{{ $txn1['cheque_num'] }}</td>
                            <td>{{ $txn1['category'] ?: '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    @endforeach



</div>
</body>
</html>
