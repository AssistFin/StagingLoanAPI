@extends('admin.layouts.app') 
<style>
    body { font-family: sans-serif; font-size: 12px; }
    h2, h3 { text-align: center; margin-bottom: 10px; }
    .section { margin-bottom: 20px; }
    .label { font-weight: bold; width: 200px; display: inline-block; }
    table {
        border-collapse: collapse;
        width: 100%;
        font-size: 10px;
    }
    th, td {
        border: 1px solid #ccc;
        padding: 5px;
        text-align: left;
    }
    th {
        background-color: #eee;
    }
</style>
@section('panel')
<body>
    <h2>Customer Credit Report</h2>
    <!--table>
        <tr><th>Name : </th><td>{{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['First_Name'] ?? '' }} {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['Last_Name'] ?? 'N/A' }}</td></tr>
        <tr><th>Mobile No: </th><td>{{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['MobilePhoneNumber'] ?? 'N/A' }}</td></tr>
        <tr><th>DOB : </th><td>{{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['Date_Of_Birth_Applicant'] ?? 'N/A' }}</td></tr>
        <tr><th>PAN : </th><td>{{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['IncomeTaxPan'] ?? 'N/A' }}</td></tr>
        <tr><th>House No : </th><td>{{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Address_Details']['FlatNoPlotNoHouseNo'] ?? 'N/A' }}</td></tr>
        <tr><th>City : </th><td>{{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Address_Details']['City'] ?? 'N/A' }}</td></tr>
        <tr><th>Pincode : </th><td>{{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Address_Details']['PINCode'] ?? 'N/A' }}</td></tr>
        <tr><th>State : </th><td>{{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Address_Details']['State'] ?? 'N/A' }}</td></tr>
        <tr><th>Experian Credit Score : </th><td>{{ $data['SCORE']['BureauScore'] ?? 'N/A' }}</td></tr>
    </table-->
    <div class="section">
        <h3>Applicant Details</h3>
        <div><span class="label">Name:</span> {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['First_Name'] ?? '' }} {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['Last_Name'] ?? '' }}</div>
        <div><span class="label">Mobile:</span> {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['MobilePhoneNumber'] ?? '' }}</div>
        <div><span class="label">PAN:</span> {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['IncomeTaxPan'] ?? '' }}</div>
        <div><span class="label">DOB:</span> {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Details']['Date_Of_Birth_Applicant'] ?? '' }}</div>
        <div><span class="label">City:</span> {{ $data['Current_Application']['Current_Application_Details']['Current_Applicant_Address_Details']['City'] ?? '' }}</div>
    </div>

    <div class="section">
        <h3>Report Info</h3>
        <div><span class="label">Report Date:</span> {{ $data['CreditProfileHeader']['ReportDate'] ?? '' }}</div>
        <div><span class="label">Report Number:</span> {{ $data['CreditProfileHeader']['ReportNumber'] ?? '' }}</div>
        <div><span class="label">Subscriber:</span> {{ $data['CreditProfileHeader']['Subscriber_Name'] ?? '' }}</div>
    </div>

    <div class="section">
        <h3>Account Summary</h3>
        <div><span class="label">Total Accounts:</span> {{ $data['CAIS_Account']['CAIS_Summary']['Credit_Account']['CreditAccountTotal'] ?? '0' }}</div>
        <div><span class="label">Active Accounts:</span> {{ $data['CAIS_Account']['CAIS_Summary']['Credit_Account']['CreditAccountActive'] ?? '0' }}</div>
        <div><span class="label">Outstanding Balance:</span> ₹{{ number_format($data['CAIS_Account']['CAIS_Summary']['Total_Outstanding_Balance']['Outstanding_Balance_All'] ?? 0) }}</div>
    </div>

    <div class="section">
        <h3>CAPS Application Details</h3>
        @if(isset($data['CAPS']['CAPS_Application_Details']))
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subscriber Name</th>
                        <th>Date of Request</th>
                        <th>Report Number</th>
                        <th>Amount Financed</th>
                        <th>Duration</th>
                        <th>Enquiry Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $applications = $data['CAPS']['CAPS_Application_Details'];
                        if (!isset($applications[0])) {
                            $applications = [$applications];
                        }
                    @endphp
                    @foreach($applications as $index => $app)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $app['Subscriber_Name'] ?? '-' }}</td>
                            <td>{{ $app['Date_of_Request'] ?? '-' }}</td>
                            <td>{{ $app['ReportNumber'] ?? '-' }}</td>
                            <td>₹{{ number_format($app['Amount_Financed'] ?? 0) }}</td>
                            <td>{{ $app['Duration_Of_Agreement'] ?? '0' }} months</td>
                            <td>{{ $app['Enquiry_Reason'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No CAPS Applications found.</p>
        @endif
    </div>

</body>
