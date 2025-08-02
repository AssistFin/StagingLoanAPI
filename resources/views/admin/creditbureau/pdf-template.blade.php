
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Experian Credit Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.4;
        }
        h1, h2 {
            text-align: center;
            margin: 5px 0;
        }
        h1 {
            font-size: 20px;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        h2 {
            font-size: 16px;
            background: #f0f0f0;
            padding: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            font-size: 10px;
            border: 1px solid #666;
            padding: 5px;
            text-align: left;
        }
        th {
            font-size: 10px;
            background: #e0e0e0;
        }
        .label {
            width: 200px;
            font-weight: bold;
        }
        .section {
            margin-bottom: 20px;
        }
        .two-column {
            display: flex;
            flex-wrap: wrap;
        }
        .col {
            width: 50%;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
        .page-break { page-break-before: always; }
    </style>
</head>

<body>

    <h1>Experian Credit Information Report</h1>
    @php
        $reportdateString = (string)($data['CreditProfileHeader']['ReportDate']);
        $carbonDates = \Carbon\Carbon::createFromFormat('Ymd', $reportdateString);
        $reportedDate = $carbonDates->format('d/m/Y');
    @endphp
    <!-- MATCH DETAILS -->
    <div class="section">
        <h2>MATCH DETAILS</h2>
        <div class="two-column">
            <div class="col"><strong>Match Score:</strong> {{ $data['Match_result']['Exact_match'] ?? '' }}</div>
            <div class="col"><strong>Report Number:</strong> {{ $data['CreditProfileHeader']['ReportNumber'] ?? '' }}</div>
            <div class="col"><strong>Report Date:</strong> {{ $reportedDate ?? '' }}</div>
            <div class="col"><strong>Subscriber:</strong> {{ $data['CreditProfileHeader']['Subscriber_Name'] ?? '' }}</div>
        </div>
    </div>

    <!-- CURRENT APPLICATION INFORMATION -->
    <div class="section">
        <h2>CURRENT APPLICATION INFORMATION</h2>
        @php
            $dobdateString = (string)($data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['Date_Of_Birth_Applicant']);
            $carbonDOBDates = \Carbon\Carbon::createFromFormat('Ymd', $dobdateString);
            $dobDate = $carbonDOBDates->format('d/m/Y');
        @endphp
        <div class="two-column">
            <div class="col"><strong>Name:</strong> {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['First_Name'] ?? '' }} {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['Last_Name'] ?? '' }}</div>
            <div class="col"><strong>PAN:</strong> {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['IncomeTaxPan'] ?? '' }}</div>
            <div class="col"><strong>Date of Birth:</strong> {{ $dobDate ?? '' }}</div>
            <div class="col"><strong>Mobile:</strong> {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['MobilePhoneNumber'] ?? '' }}</div>
            <div class="col"><strong>Address:</strong> {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Address_Details']['FlatNoPlotNoHouseNo'] ?? '' }}, {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Address_Details']['City'] ?? '' }}</div>
        </div>
    </div>

    <!-- BUREAU PROFILE -->
    <div class="section">
        <h2>REPORT SUMMARY</h2>
        <div class="two-column">
            <div class="col"><strong>Experian Credit Score:</strong> <strong>{{ $data['SCORE']['BureauScore'] ?? 'N/A' }}</strong></div>
        </div>
    </div>

    <!-- CREDIT ACCOUNT SUMMARY -->
    <div class="section">
        <h2>CREDIT ACCOUNT SUMMARY</h2>
        @if(isset($data['CAIS_Account']))
            <table>
                <tr>
                    <th>Total Credit Accounts</th>
                    <th>Active Credit Accounts</th>
                    <th>Defaulted Credit Accounts</th>
                    <th>Closed Credit Accounts</th>
                    <th>Current Balance on Suit Filed Accounts</th>
                </tr>
                <tr>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Credit_Account']['CreditAccountTotal'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Credit_Account']['CreditAccountActive'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Credit_Account']['CreditAccountDefault'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Credit_Account']['CreditAccountClosed'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Credit_Account']['CADSuitFiledCurrentBalance'] ?? '-' }}</td>
                </tr>
            </table>
        @else
            <p>No details found.</p>
        @endif
    </div>

    <!-- Total Outstanding Balance -->
    <div class="section">
        <h2>TOTAL OUTSTANDING BALANCE</h2>
        @if(isset($data['CAIS_Account']))
            <table>
                <tr>
                    <th>Secured Outstanding Balance</th>
                    <th>Percentage of Secured Outstanding Balance</th>
                    <th>Unsecured Outstanding Balance</th>
                    <th>Percentage of Unsecured Outstanding Balance</th>
                    <th>Total Outstanding Balance (All)</th>
                </tr>
                <tr>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Total_Outstanding_Balance']['Outstanding_Balance_Secured'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Total_Outstanding_Balance']['Outstanding_Balance_Secured_Percentage'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Total_Outstanding_Balance']['Outstanding_Balance_UnSecured'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Total_Outstanding_Balance']['Outstanding_Balance_UnSecured_Percentage'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Total_Outstanding_Balance']['Outstanding_Balance_All'] ?? '-' }}</td>
                </tr>
            </table>
        @else
            <p>No details found.</p>
        @endif
    </div>

    <!-- ACCOUNTS SUMMARY -->
    <div class="section">
        <h2>CREDIT ACCOUNT INFORMATION</h2>
        @if(isset($data['CAIS_Account']['CAIS_Summary']))
            <table>
                <tr>
                    <th>Total Accounts</th>
                    <th>Active Accounts</th>
                    <th>Closed Accounts</th>
                    <th>Written Off</th>
                    <th>Outstanding Balance</th>
                </tr>
                <tr>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Credit_Account']['CreditAccountTotal'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Credit_Account']['CreditAccountActive'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Credit_Account']['CreditAccountClosed'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Credit_Account']['CreditAccountWrittenOff'] ?? '-' }}</td>
                    <td>{{ $data['CAIS_Account']['CAIS_Summary']['Total_Outstanding_Balance']['Outstanding_Balance_All']['Amount'] ?? 0 }}</td>
                </tr>
            </table>
        @else
            <p>No credit account summary available.</p>
        @endif
    </div>
    <!-- Summary Data -->
    <div class="section">
        <h2>CREDIT ACCOUNT INFORMATION SUMMARY</h2>
        @if(!empty($data['CAIS_Account']['CAIS_Account_DETAILS']))
            <table>
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Credit Provider</th>
                        <th>Account Type</th>
                        <th>Account No</th>
                        <th>Date Reported</th>
                        <th>Date Closed</th>
                        <th>Account Status</th>
                        <th>Date Opened</th>
                        <th>Last Payment Date</th>
                        <th>Sanction Amt / Highest Credit</th>
                        <th>Current Balance</th>
                        <th>Amount Overdue</th>
                        
                    </tr>
                </thead>
                <tbody>
                    @php
                        $accountDetails = $data['CAIS_Account']['CAIS_Account_DETAILS'] ?? [];

                        usort($accountDetails, function($a, $b) {
                            $dateA = isset($a['Open_Date']) ? (int)$a['Open_Date'] : 0;
                            $dateB = isset($b['Open_Date']) ? (int)$b['Open_Date'] : 0;
                            return $dateB <=> $dateA; // Descending order
                        });
                    @endphp
                    @foreach($accountDetails as $j => $summary)
                        @php
                            
                            $accountType = '';
                            if((string)($summary['Account_Type']) == '1'){ $accountType = 'AUTO LOAN';}
                            if((string)($summary['Account_Type']) == '2'){ $accountType = 'HOUSING LOAN';}
                            if((string)($summary['Account_Type']) == '3'){ $accountType = 'PROPERTY LOAN';}
                            if((string)($summary['Account_Type']) == '4'){ $accountType = 'LOAN AGAINST SHARES/SECURITIES';}
                            if((string)($summary['Account_Type']) == '5'){ $accountType = 'PERSONAL LOAN';}
                            if((string)($summary['Account_Type']) == '6'){ $accountType = 'CONSUMER LOAN';}
                            if((string)($summary['Account_Type']) == '7'){ $accountType = 'GOLD LOAN';}
                            if((string)($summary['Account_Type']) == '8'){ $accountType = 'EDUCATIONAL LOAN';}
                            if((string)($summary['Account_Type']) == '9'){ $accountType = 'LOAN TO PROFESSIONAL';}
                            if((string)($summary['Account_Type']) == '10'){ $accountType = 'CREDIT CARD';}
                            if((string)($summary['Account_Type']) == '11'){ $accountType = 'LEASING';}
                            if((string)($summary['Account_Type']) == '12'){ $accountType = 'OVERDRAFT';}
                            if((string)($summary['Account_Type']) == '13'){ $accountType = 'TWO-WHEELER LOAN';}
                            if((string)($summary['Account_Type']) == '14'){ $accountType = 'NON-FUNDED CREDIT FACILITY';}
                            if((string)($summary['Account_Type']) == '15'){ $accountType = 'LOAN AGAINST BANK DEPOSITS';}
                            if((string)($summary['Account_Type']) == '16'){ $accountType = 'FLEET CARD';}
                            if((string)($summary['Account_Type']) == '17'){ $accountType = 'Commercial Vehicle Loan';}
                            if((string)($summary['Account_Type']) == '18'){ $accountType = 'Telco - Wireless';}
                            if((string)($summary['Account_Type']) == '19'){ $accountType = 'Telco - Broadband';}
                            if((string)($summary['Account_Type']) == '20'){ $accountType = 'Telco - Landline';}
                            if((string)($summary['Account_Type']) == '23'){ $accountType = 'GECL Secured';}
                            if((string)($summary['Account_Type']) == '24'){ $accountType = 'GECL Unsecured';}
                            if((string)($summary['Account_Type']) == '31'){ $accountType = 'Secured Credit Card';}
                            if((string)($summary['Account_Type']) == '32'){ $accountType = 'Used Car Loan';}
                            if((string)($summary['Account_Type']) == '33'){ $accountType = 'Construction Equipment Loan';}
                            if((string)($summary['Account_Type']) == '34'){ $accountType = 'Tractor Loan';}
                            if((string)($summary['Account_Type']) == '35'){ $accountType = 'Corporate Credit Card';}
                            if((string)($summary['Account_Type']) == '36'){ $accountType = 'Kisan Credit Card';}
                            if((string)($summary['Account_Type']) == '37'){ $accountType = 'Loan on Credit Card';}
                            if((string)($summary['Account_Type']) == '38'){ $accountType = 'Prime Minister Jaan Dhan Yojana - Overdraft';}
                            if((string)($summary['Account_Type']) == '39'){ $accountType = 'Mudra Loans - Shishu / Kishor / Tarun';}
                            if((string)($summary['Account_Type']) == '40'){ $accountType = 'Microfinance - Business Loan';}
                            if((string)($summary['Account_Type']) == '41'){ $accountType = 'Microfinance - Personal Loan';}
                            if((string)($summary['Account_Type']) == '42'){ $accountType = 'Microfinance - Housing Loan';}
                            if((string)($summary['Account_Type']) == '43'){ $accountType = 'Microfinance - Others';}
                            if((string)($summary['Account_Type']) == '44'){ $accountType = 'Pradhan Mantri Awas Yojana - Credit Link Subsidy Scheme MAY CLSS';}
                            if((string)($summary['Account_Type']) == '45'){ $accountType = 'P2P Personal Loan';}
                            if((string)($summary['Account_Type']) == '46'){ $accountType = 'P2P Auto Loan';}
                            if((string)($summary['Account_Type']) == '47'){ $accountType = 'P2P Education Loan';}
                            if((string)($summary['Account_Type']) == '51'){ $accountType = 'BUSINESS LOAN - GENERAL';}
                            if((string)($summary['Account_Type']) == '52'){ $accountType = 'BUSINESS LOAN -PRIORITY SECTOR - SMALL BUSINESS';}
                            if((string)($summary['Account_Type']) == '53'){ $accountType = 'BUSINESS LOAN -PRIORITY SECTOR - AGRICULTURE';}
                            if((string)($summary['Account_Type']) == '54'){ $accountType = 'BUSINESS LOAN -PRIORITY SECTOR - OTHERS';}
                            if((string)($summary['Account_Type']) == '55'){ $accountType = 'BUSINESS NON-FUNDED CREDIT FACILITY - GENERAL';}
                            if((string)($summary['Account_Type']) == '56'){ $accountType = 'BUSINESS NON-FUNDED CREDIT FACILITY - PRIORITY SECTOR - SMALL BUSINESS';}
                            if((string)($summary['Account_Type']) == '57'){ $accountType = 'BUSINESS NON-FUNDED CREDIT FACILITY - PRIORITY SECTOR - AGRICULTURE';}
                            if((string)($summary['Account_Type']) == '58'){ $accountType = 'BUSINESS NON-FUNDED CREDIT FACILITY - PRIORITY SECTOR - OTHERS';}
                            if((string)($summary['Account_Type']) == '59'){ $accountType = 'BUSINESS LOANS AGAINST BANK DEPOSITS';}
                            if((string)($summary['Account_Type']) == '60'){ $accountType = 'Staff Loan';}
                            if((string)($summary['Account_Type']) == '61'){ $accountType = 'Business Loan - Unsecured';}
                            if((string)($summary['Account_Type']) == '00'){ $accountType = 'Others';}
                            if((string)($summary['Account_Type']) == '50'){ $accountType = 'Business Loan - Secured';}
                            if((string)($summary['Account_Type']) == '69'){ $accountType = 'Short Term Personal Loan [Unsecured]';}
                            if((string)($summary['Account_Type']) == '70'){ $accountType = 'Priority Sector Gold Loan [Secured]';}
                            if((string)($summary['Account_Type']) == '71'){ $accountType = 'Temporary Overdraft [Unsecured]';} 

                            $reportdateString = (string)($summary['Date_Reported']);
                            $carbonDates = \Carbon\Carbon::createFromFormat('Ymd', $reportdateString);
                            $reportedDate = $carbonDates->format('d/m/Y');

                            if(!is_array($summary['Date_Closed'])){
                                $closedateString = (string)($summary['Date_Closed']);
                                $carbonDates2 = \Carbon\Carbon::createFromFormat('Ymd', $closedateString);
                                $closedDate = $carbonDates2->format('d/m/Y');
                            }else{
                                $closedDate = '-';
                            }

                            $accountStatus = '';
                            if((string)($summary['Account_Status']) == '11' || (string)($summary['Account_Status']) == '71' || (string)($summary['Account_Status']) == '78' || (string)($summary['Account_Status']) == '80' || (string)($summary['Account_Status']) == '82' || (string)($summary['Account_Status']) == '83' || (string)($summary['Account_Status']) == '84' || (string)($summary['Account_Status']) == '21' || (string)($summary['Account_Status']) == '22' || (string)($summary['Account_Status']) == '23' || (string)($summary['Account_Status']) == '24' || (string)($summary['Account_Status']) == '25'){ $accountStatus = 'ACTIVE';}
                            if((string)($summary['Account_Status']) == '12' || (string)($summary['Account_Status']) == '13' || (string)($summary['Account_Status']) == '14' || (string)($summary['Account_Status']) == '15' || (string)($summary['Account_Status']) == '16' || (string)($summary['Account_Status']) == '17'){ $accountStatus = 'CLOSED';}
                            if((string)($summary['Account_Status']) == '00'){ $accountStatus = ' No Suit Filed';}
                            if((string)($summary['Account_Status']) == '89'){ $accountStatus = 'Wilful default';}
                            if((string)($summary['Account_Status']) == '93'){ $accountStatus = 'Suit Filed(Wilful default)';} 
                            if((string)($summary['Account_Status']) == '97'){ $accountStatus = 'Suit Filed(Wilful Default) and Written-off';} 
                            if((string)($summary['Account_Status']) == '30'){ $accountStatus = 'Restructured';} 
                            if((string)($summary['Account_Status']) == '32'){ $accountStatus = 'Settled';} 
                            if((string)($summary['Account_Status']) == '31'){ $accountStatus = 'Restructured Loan (Govt. Mandated)';} 
                            if((string)($summary['Account_Status']) == '33'){ $accountStatus = 'Post (WO) Settled';} 
                            if((string)($summary['Account_Status']) == '34'){ $accountStatus = 'Account Sold';} 
                            if((string)($summary['Account_Status']) == '35'){ $accountStatus = 'Written Off and Account Sold';} 
                            if((string)($summary['Account_Status']) == '36'){ $accountStatus = 'Account Purchased';} 
                            if((string)($summary['Account_Status']) == '37'){ $accountStatus = 'Account Purchased and Written Off';} 
                            if((string)($summary['Account_Status']) == '38'){ $accountStatus = 'Account Purchased and Settled';} 
                            if((string)($summary['Account_Status']) == '39'){ $accountStatus = 'Account Purchased and Restructured';} 
                            if((string)($summary['Account_Status']) == '40'){ $accountStatus = 'Status Cleared';} 
                            if((string)($summary['Account_Status']) == '41'){ $accountStatus = 'Restructured Loan';} 
                            if((string)($summary['Account_Status']) == '42'){ $accountStatus = 'Restructured Loan (Govt. Mandated)';} 
                            if((string)($summary['Account_Status']) == '43'){ $accountStatus = 'Written-off';} 
                            if((string)($summary['Account_Status']) == '44'){ $accountStatus = 'Settled';} 
                            if((string)($summary['Account_Status']) == '45'){ $accountStatus = 'Post (WO) Settled';} 
                            if((string)($summary['Account_Status']) == '46'){ $accountStatus = 'Account Sold';} 
                            if((string)($summary['Account_Status']) == '47'){ $accountStatus = 'Written Off and Account Sold';} 
                            if((string)($summary['Account_Status']) == '48'){ $accountStatus = 'Account Purchased';} 
                            if((string)($summary['Account_Status']) == '49'){ $accountStatus = 'Account Purchased and Written Off';} 
                            if((string)($summary['Account_Status']) == '50'){ $accountStatus = 'Account Purchased and Settled';} 
                            if((string)($summary['Account_Status']) == '51'){ $accountStatus = 'Account Purchased and Restructured';} 
                            if((string)($summary['Account_Status']) == '52'){ $accountStatus = 'Status Cleared';} 
                            if((string)($summary['Account_Status']) == '53'){ $accountStatus = 'Suit Filed';} 
                            if((string)($summary['Account_Status']) == '54'){ $accountStatus = 'Suit Filed and Written-off';} 
                            if((string)($summary['Account_Status']) == '55'){ $accountStatus = 'Suit Filed and Settled';} 
                            if((string)($summary['Account_Status']) == '56'){ $accountStatus = 'Suit Filed and Post (WO) Settled';} 
                            if((string)($summary['Account_Status']) == '57'){ $accountStatus = 'Suit Filed and Account Sold';} 
                            if((string)($summary['Account_Status']) == '58'){ $accountStatus = 'Suit Filed and Written Off and Account Sold';} 
                            if((string)($summary['Account_Status']) == '59'){ $accountStatus = 'Suit Filed and Account Purchased';}
                            if((string)($summary['Account_Status']) == '60'){ $accountStatus = 'Suit Filed and Account Purchased and Written Off';}
                            if((string)($summary['Account_Status']) == '61'){ $accountStatus = 'Suit Filed and Account Purchased and Settled';}
                            if((string)($summary['Account_Status']) == '62'){ $accountStatus = 'Suit Filed and Account Purchased and Restructured';}
                            if((string)($summary['Account_Status']) == '63'){ $accountStatus = 'Suit Filed and Status Cleared';}
                            if((string)($summary['Account_Status']) == '64'){ $accountStatus = 'Wilful Default and Restructured Loan';}
                            if((string)($summary['Account_Status']) == '65'){ $accountStatus = 'Wilful Default and Restructured Loan (Govt. Mandated)';}
                            if((string)($summary['Account_Status']) == '66'){ $accountStatus = 'Wilful Default and Settled';}
                            if((string)($summary['Account_Status']) == '67'){ $accountStatus = 'Wilful Default and Post (WO) Settled';}
                            if((string)($summary['Account_Status']) == '68'){ $accountStatus = 'Wilful Default and Account Sold';}
                            if((string)($summary['Account_Status']) == '69'){ $accountStatus = 'Wilful Default and Written Off and Account Sold';}
                            if((string)($summary['Account_Status']) == '70'){ $accountStatus = 'Wilful Default and Account Purchased';}
                            if((string)($summary['Account_Status']) == '72'){ $accountStatus = 'Wilful Default and Account Purchased and Written Off';}
                            if((string)($summary['Account_Status']) == '73'){ $accountStatus = 'Wilful Default and Account Purchased and Settled';}
                            if((string)($summary['Account_Status']) == '74'){ $accountStatus = 'Wilful Default and Account Purchased and Restructured';}
                            if((string)($summary['Account_Status']) == '75'){ $accountStatus = 'Wilful Default and Status Cleared';}
                            if((string)($summary['Account_Status']) == '76'){ $accountStatus = 'Suit filed (Wilful default) and Restructured';}
                            if((string)($summary['Account_Status']) == '77'){ $accountStatus = 'Suit filed (Wilful default) and Restructured Loan (Govt. Mandated)';}
                            if((string)($summary['Account_Status']) == '79'){ $accountStatus = 'Suit filed (Wilful default) and Settled';}
                            if((string)($summary['Account_Status']) == '81'){ $accountStatus = 'Suit filed (Wilful default) and Post (WO) Settled';}
                            if((string)($summary['Account_Status']) == '85'){ $accountStatus = 'Suit filed (Wilful default) and Account Sold';}
                            if((string)($summary['Account_Status']) == '86'){ $accountStatus = 'Suit filed (Wilful default) and Written Off and Account Sold';}
                            if((string)($summary['Account_Status']) == '87'){ $accountStatus = 'Suit filed (Wilful default) and Account Purchased';}
                            if((string)($summary['Account_Status']) == '88'){ $accountStatus = 'Suit filed (Wilful default) and Account Purchased and Written Off';}
                            if((string)($summary['Account_Status']) == '90'){ $accountStatus = 'Suit filed (Wilful default) and Account Purchased and Restructured';}
                            if((string)($summary['Account_Status']) == '91'){ $accountStatus = 'Suit filed (Wilful default) and Status Cleared';}
                            if((string)($summary['Account_Status']) == '94'){ $accountStatus = 'Suit filed (Wilful default) and Account Purchased and Settled';}
                            if((string)($summary['Account_Status']) == '130'){ $accountStatus = 'Restructured due to COVID-19';}
                            if((string)($summary['Account_Status']) == '131'){ $accountStatus = 'Restructured due to natural calamity';}
                            if((string)($summary['Account_Status']) == '132'){ $accountStatus = 'Post Write Off Closed';}
                            if((string)($summary['Account_Status']) == '133'){ $accountStatus = 'Restructured & Closed';}
                            if((string)($summary['Account_Status']) == '134'){ $accountStatus = 'Auctioned & Settled';}
                            if((string)($summary['Account_Status']) == '135'){ $accountStatus = 'Repossessed & Settled';}
                            if((string)($summary['Account_Status']) == '136'){ $accountStatus = 'Guarantee Invoked';}
                            if((string)($summary['Account_Status']) == '137'){ $accountStatus = 'Entity ceased while account was open';}
                            if((string)($summary['Account_Status']) == '138'){ $accountStatus = 'Entity ceased while account was closed';}
                            
                            $opendateString = (string)($summary['Open_Date']);
                            $carbonDates1 = \Carbon\Carbon::createFromFormat('Ymd', $opendateString);
                            $openedDate = $carbonDates1->format('d/m/Y');

                            if(!is_array($summary['Date_of_Last_Payment'])){
                                $lastpaydateString = (string)($summary['Date_of_Last_Payment']);
                                $carbonDates3 = \Carbon\Carbon::createFromFormat('Ymd', $lastpaydateString);
                                $lastpaydate = $carbonDates3->format('d/m/Y');
                            }else{
                                $lastpaydate = '-';
                            }

                            $idNumber = (string)($summary['Identification_Number']);
                            $last4idno = substr($idNumber, 0, 4);

                            $accountNumber = (string)($summary['Account_Number']);
                            $last6accno = substr($accountNumber, -6);

                        @endphp
                        <tr>
                            <td>{{ $j + 1 }}</td>
                            <td>{{ $last4idno ?? '-' }}</td>
                            <td>{{ $accountType }}</td>
                            <td>{{ $last6accno ?? '-' }}</td>
                            <td>{{ $reportedDate ?? '-' }}</td>
                            <td>{{ $closedDate ?? '-' }}</td>
                            <td>{{ $accountStatus }}</td>
                            <td>{{ $openedDate ?? '-' }}</td>
                            <td>{{ $lastpaydate ?? '-' }}</td>
                            <td>{{ (is_array($summary['Highest_Credit_or_Original_Loan_Amount'])) ? 0 : (string)($summary['Highest_Credit_or_Original_Loan_Amount']) }}</td>
                            <td>{{ (is_array($summary['Current_Balance'])) ? 0 : (string)($summary['Current_Balance']) }}</td>
                            <td>{{ (is_array($summary['Amount_Past_Due'])) ? 0 : (string)($summary['Amount_Past_Due']) }}</td>
                        </tr>
                        @php
                            $accountHistory = $summary['CAIS_Account_History'] ?? [];

                            if (!is_array($accountHistory)) {
                                $accountHistory = [$accountHistory];
                            }
                        @endphp
                        @if(!is_array($summary['Amount_Past_Due']) && (int)($summary['Amount_Past_Due']) > 0 && !empty($accountHistory))
                        <tr>
                            <td colspan="12" style="padding: 10px 0; border-right: 1px solid #000;">
                                @foreach($accountHistory as $dpd)
                                    @php
                                        $month = $dpd['Month'] ?? '01';
                                        $year = $dpd['Year'] ?? '2000';
                                        $monthName = \Carbon\Carbon::createFromDate($year, $month)->format('M');
                                        $dpdValue = is_array($dpd['Days_Past_Due']) ? 0 : (int) $dpd['Days_Past_Due'];
                                        $color = $dpdValue == 0 ? '#28a745' : '#f0ad4e'; // green or orange
                                        $dpdText = str_pad($dpdValue, 3, '0', STR_PAD_LEFT);
                                    @endphp
                                    <div style="display: inline-block; text-align: center; margin-right: 8px;">
                                        <div style="font-size: 12px; color: #007bff; margin-bottom: 3px;">{{ $monthName }}/{{ $year }}</div>
                                        <div style="
                                            width: 34px;
                                            height: 34px;
                                            line-height: 34px;
                                            border-radius: 50%;
                                            background-color: {{ $color }};
                                            color: white;
                                            font-weight: bold;
                                            font-size: 12px;
                                        ">
                                            {{ $dpdText }}
                                        </div>
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No enquiries found.</p>
        @endif
    </div>

    <!-- ACCOUNT DETAILS -->
    <div class="section">
        <h2>CREDIT APPLICATION PROCESSING SYSTEM DETAILS</h2>
        @if(isset($data['TotalCAPS_Summary']))
            <table>
                <tr>
                    <th>Total CAPS In Last 7 Days</th>
                    <th>Total CAPS In Last 30 Days</th>
                    <th>Total CAPS In Last 90 Days</th>
                    <th>Total CAPS In Last 180 Days</th>
                </tr>
                <tr>
                    <td>{{ $data['TotalCAPS_Summary']['TotalCAPSLast7Days'] ?? '-' }}</td>
                    <td>{{ $data['TotalCAPS_Summary']['TotalCAPSLast30Days'] ?? '-' }}</td>
                    <td>{{ $data['TotalCAPS_Summary']['TotalCAPSLast90Days'] ?? '-' }}</td>
                    <td>{{ $data['TotalCAPS_Summary']['TotalCAPSLast180Days'] ?? '-' }}</td>
                </tr>
            </table>
        @else
            <p>No details found.</p>
        @endif
    </div>

    <!-- ACCOUNT DETAILS -->
    <div class="section">
        <h2>CAPS ENQUIRY DETAILS</h2>
        @if(isset($data['CAPS']))
            <table>
                <tr>
                    <th>CAPS In Last 7 Days</th>
                    <th>CAPS In Last 30 Days</th>
                    <th>CAPS In Last 90 Days</th>
                    <th>CAPS In Last 180 Days</th>
                </tr>
                <tr>
                    <td>{{ $data['CAPS']['CAPS_Summary']['CAPSLast7Days'] ?? '-' }}</td>
                    <td>{{ $data['CAPS']['CAPS_Summary']['CAPSLast30Days'] ?? '-' }}</td>
                    <td>{{ $data['CAPS']['CAPS_Summary']['CAPSLast90Days'] ?? '-' }}</td>
                    <td>{{ $data['CAPS']['CAPS_Summary']['CAPSLast180Days'] ?? '-' }}</td>
                </tr>
            </table>
        @else
            <p>No details found.</p>
        @endif
    </div>

    <!-- Non CAPS ENQUIRY DETAILS -->
    <div class="section">
        <h2>NON CAPS ENQUIRY DETAILS</h2>
        @if(isset($data['NonCreditCAPS']))
            <table>
                <tr>
                    <th>NON CAPS In Last 7 Days</th>
                    <th>NON CAPS In Last 30 Days</th>
                    <th>NON CAPS In Last 90 Days</th>
                    <th>NON CAPS In Last 180 Days</th>
                </tr>
                <tr>
                    <td>{{ $data['NonCreditCAPS']['NonCreditCAPS_Summary']['NonCreditCAPSLast7Days'] ?? '-' }}</td>
                    <td>{{ $data['NonCreditCAPS']['NonCreditCAPS_Summary']['NonCreditCAPSLast30Days'] ?? '-' }}</td>
                    <td>{{ $data['NonCreditCAPS']['NonCreditCAPS_Summary']['NonCreditCAPSLast90Days'] ?? '-' }}</td>
                    <td>{{ $data['NonCreditCAPS']['NonCreditCAPS_Summary']['NonCreditCAPSLast180Days'] ?? '-' }}</td>
                </tr>
            </table>
        @else
            <p>No details found.</p>
        @endif
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <p>© {{ date('Y') }} Experian Credit Bureau — Confidential Report</p>
    </div>

</body>
</html>
