<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>No Objection Certificate (NOC)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #000;
            margin: 5px;
        }
        .content {
            margin-top: 0px;
        }
        .watermark {
            position: absolute;
            left: 0px; /* Adjust as needed */
            opacity: 0.6; /* Make watermark semi-transparent */
            width: 200px; /* Adjust to match desired size */
        }
        .date {
            text-align: left;
            margin-top: -20px;
        }
        .ref {
            text-align: right;
            margin-top: -20px;
        }
        .separator {
            border-bottom: 2px solid #000;
            margin: 10px 0 15px 0;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            margin-top: -10px;
        }
        .header h1{
            font-size : 32px;
        }
        .signatory {
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>ALTURA FINANCIAL SERVICES LIMITED</h1>
        CIN: U65100DL2013PLC259294, NBFC Code- DEL12195 and COR-N-14.03308<br>
        Registered Office: Ground Floor Plot No-121, Block-B, Pocket-4, Sector-23, Dwarka, South West Delhi, New Delhi, 110077, Email: afs@alturafinancials.com
    </div>
    <div class="separator"></div>
    <h2 style="text-align:center;">NO OBJECTION CERTIFICATE (NOC)</h2>
    <div class="date">
        <b>Date:</b> {{ $date }}
    </div>
    <div class="ref">
        <b>Ref. No :</b> {{ $ref_no }}
    </div>

    <div class="content">
        <p style="text-align:center"><b>To Whom It May Concern</b></p>
        <p>This is to certify that Mr./Ms. <strong>{{ $borrower_name }}</strong>, resident of <strong>{{ $address }}</strong>, bearing PAN No <strong>{{ $pan_number }}</strong> had availed a loan from Altura Financial Services Ltd., under Loan Application Number <strong>{{ $loan_app_no }}</strong>, dated <strong>{{ $loan_disbursement_date }}</strong>.</p>
        <p>We hereby confirm that the said loan account has been fully repaid and closed on <strong>{{ $loan_closure_date }}</strong>, and there are <b>no outstanding dues</b> or liabilities towards the said loan as of this date.</p>
        <p>Accordingly, we have <b>no objection</b> to the closure of the said loan and removal of any lien, if applicable, from the borrower’s bank account or credit profile related to this loan.</p>
    </div>

    <div class="signatory">
        <strong>For Altura Financial Services Ltd.</strong><br>
        (RBI Registered NBFC)<br><br>
        <img src="{{ public_path('assets/admin/images/sign.png') }}" class="watermark" alt="Watermark">
        Authorised Signatory
    </div>
</body>
</html>
