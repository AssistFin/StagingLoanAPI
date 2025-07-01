<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sanction Letter - Altura Financial</title>
    <style>
        body {
          font-family: "Times New Roman", serif;
          margin: 2cm;
          color: #000;
          font-size: 16px;
          line-height: 1.6;
        }
  
        .letterhead {
          text-align: center;
          font-size: 22px;
          font-weight: bold;
          margin-bottom: 20px;
          text-transform: uppercase;
        }
  
        .subheading {
          text-align: center;
          font-size: 18px;
          text-decoration: underline;
        }
  
        .section {
          margin-bottom: 25px;
        }
  
        .date {
          float: right;
        }
  
        .table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 10px;
          font-size: 14px;
          table-layout: fixed; /* Ensure table fits within page */
        }
  
        .table th,
        .table td {
          border: 1px solid #000;
          padding: 8px;
          text-align: center;
          vertical-align: middle;
          word-wrap: break-word; /* Prevent text overflow */
        }
  
        .table th {
          background-color: #f0f0f0;
        }
  
        .signature {
          margin-top: 50px;
          display: flex;
          justify-content: space-between;
          font-size: 14px;
          gap: 20px;
        }
  
        .accepted {
          text-align: right;
        }
  
        h3 {
          margin-top: 40px;
        }
  
        .agreement-section {
          margin-bottom: 20px;
        }
  
        .agreement-section h4 {
          font-size: 16px;
          text-transform: uppercase;
          margin-bottom: 10px;
        }
  
        .numbered-list {
          margin-left: 20px;
        }
  
        .numbered-list p {
          margin: 5px 0;
        }
  
        .sub-numbered-list {
          margin-left: 40px;
        }
  
        /* Print Styles for PDF */
        @media print {
          body {
            margin: 1cm;
            font-size: 12pt; /* Standardize font size for print */
          }
  
          .no-print {
            display: none;
          }
  
          /* Remove page break for the first section to avoid blank page */
          .sanction-letter {
            page-break-before: auto;
          }
  
          /* Ensure KFS and Loan Agreement start on new pages */
          .kfs-section,
          .loan-agreement {
            page-break-before: always;
            page-break-after: auto;
          }
  
          /* Prevent awkward breaks for specific sections, but allow Terms and Conditions to break */
          .section:not(.terms-and-conditions),
          .agreement-section,
          .signature {
            page-break-inside: avoid;
          }
  
          /* Allow Terms and Conditions section to break across pages */
          .terms-and-conditions {
            page-break-inside: auto;
          }
  
          .table {
            page-break-inside: auto; /* Allow table to break across pages */
          }
  
          .table tr {
            page-break-inside: avoid; /* Prevent breaking within a row */
            page-break-after: auto;
          }
  
          /* Ensure tables fit within page */
          .table {
            width: 100%;
            font-size: 10pt;
          }
  
          .table th,
          .table td {
            padding: 5px;
            font-size: 10pt;
          }
  
          /* Adjust margins for print */
          .letterhead,
          .subheading,
          h3 {
            margin-top: 0;
          }
  
          .signature {
            margin-top: 30px;
          }
        }
  
        /* Responsive Styles (for viewing, not PDF) */
        @media (max-width: 768px) {
          body {
            margin: 15px;
            font-size: 14px;
          }
  
          .letterhead {
            font-size: 18px;
          }
  
          .subheading {
            font-size: 16px;
            text-decoration: underline;
          }
  
          .date {
            float: none;
            text-align: left;
            margin-top: 10px;
          }
  
          .table {
            font-size: 12px;
            display: table;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            white-space: normal;
          }
  
          .table th,
          .table td {
            padding: 6px;
            min-width: 100px;
          }
  
          .signature {
            /* flex-direction: column; */
            align-items: flex-start;
            margin-top: 30px;
          }
  
          .accepted {
            text-align: left;
            margin-top: 20px;
          }
  
          .agreement-section h4 {
            font-size: 14px;
          }
  
          .numbered-list {
            margin-left: 15px;
          }
  
          .sub-numbered-list {
            margin-left: 30px;
          }
        }
  
        @media (max-width: 480px) {
          body {
            margin: 10px;
            font-size: 13px;
          }
  
          .letterhead {
            font-size: 16px;
          }
  
          .subheading {
            font-size: 14px;
            margin-bottom: 10px;
            text-decoration: underline;
          }
  
          .table {
            font-size: 11px;
            display: table;
          }
  
          .table th,
          .table td {
            padding: 5px;
            min-width: 80px;
          }
  
          h3 {
            margin-top: 25px;
            font-size: 16px;
          }
  
          .section {
            margin-bottom: 15px;
          }
  
          .agreement-section h4 {
            font-size: 13px;
          }
  
          .numbered-list {
            margin-left: 10px;
          }
  
          .sub-numbered-list {
            margin-left: 20px;
          }
        }
  
        @media (max-width: 360px) {
          body {
            font-size: 12px;
          }
  
          .letterhead {
            font-size: 14px;
          }

          .table {
            display: table;
          }
  
          .table th,
          .table td {
            padding: 4px;
            min-width: 70px;
            font-size: 10px;
          }
  
          .signature {
            font-size: 12px;
          }
  
          .agreement-section h4 {
            font-size: 12px;
          }
  
          .numbered-list {
            margin-left: 8px;
          }
  
          .sub-numbered-list {
            margin-left: 15px;
          }
        }
  
        .table-wrapper {
          overflow-x: auto;
          margin: 10px 0;
        }
      </style>
  </head>
  <body>
    <!-- Sanction Letter Section -->
    <div class="sanction-letter">
      <div class="letterhead">Altura Financial Services Ltd.</div>
      <div class="subheading"><strong>Sanction Letter</strong></div>
      <div class="section">
        Name: {{$borrower_name}}<br />
        Address: {{$borrower_address}}<br />
        <div class="date">Date: {{$sanction_date}}</div>
      </div>

      <div class="section">
        Dear {{$borrower_name}},<br /><br />
        Thank you for your loan application made through LoanOne. We are pleased
        to sanction you a loan as per the enclosed terms and KFS.
      </div>

      <div class="section">
        This Loan Sanction Letter is made in reference to your Loan Application
        Number <strong>{{$loan_application_no}}</strong> dated
        <strong>{{$sanction_date}}</strong>. Based on the information provided by you,
        your loan application has been approved with the following terms and
        conditions:
      </div>

      <div class="table-wrapper">
        <table class="table">
            <tr>
              <th>Sl. No.</th>
              <th>Particulars</th>
              <th>Terms Details</th>
            </tr>
            <tr>
              <td>1</td>
              <td>Loan Sanctioned Amount</td>
              <td>{{number_format($loan_amount, 2)}}</td>
            </tr>
            <tr>
              <td>2</td>
              <td>Rate of Interest (ROI) per day</td>
              <td>{{number_format($rate_of_interest, 2)}}%</td>
            </tr>
            <tr>
              <td>3</td>
              <td>Loan Tenure (days)</td>
              <td>{{$loan_tenure}}</td>
            </tr>
            <tr>
              <td>4</td>
              <td>Processing Fee + GST (%)</td>
              <td>{{number_format($processingFeeAmount + $ECSGST, 2)}}</td>
            </tr>
            <tr>
              <td>5</td>
              <td>Disbursal Amount (1-4)</td>
              <td>{{number_format($disbursal_amount, 2)}}</td>
            </tr>
            <tr>
              <td>6</td>
              <td>Total Repayment Amount</td>
              <td>{{number_format($total_repayment_amount, 2)}}</td>
            </tr>
            <tr>
              <td>7</td>
              <td>ECS/ENACH/Cheque bounce charges + GST</td>
              <td>{{number_format(0.0, 2)}}</td>
            </tr>
            <tr>
              <td>8</td>
              <td>Penal Charges (per day)</td>
              <td>{{number_format(0.25, 2)}}</td>
            </tr>
          </table>          
      </div>

      <div class="section terms-and-conditions">
        <h3>Terms and Conditions</h3>
        Please note that this sanction letter is issued in response to your loan
        application and based on the information you have provided to us. The
        processing fees as charged are mentioned in the schedule, and the same
        will be deducted from your loan amount upfront.<br /><br />
        You are under no obligation to accept the sanctioned loan. If you do not
        accept the sanction letter and the enclosed agreement, your loan
        application will be considered withdrawn, and you will not be liable to
        pay any processing fees/charges.<br /><br />
        If you wish to accept the sanction letter, please go through the enclosed
        Key Fact Statement (KFS) and loan agreement and confirm your acceptance.
        The loan will be disbursed to your bank account after confirmation and
        acceptance of this sanction letter.
      </div>

      <div class="signature">
        <div>
          Warm regards,<br /><br />
          <strong>Ravi Shankar Kumar</strong><br />
          Authorised Signatory<br />
          Altura Financial Services Ltd.
        </div>
        <div class="accepted">
          Accepted<br /><br />
          {{$borrower_name}} {Signature}<br />
          Borrower
        </div>
      </div>
    </div>

    <!-- KFS Section -->
    <div class="kfs-section">
      <div class="subheading">
        <h3>KFS - Key Fact Statement</h3>
      </div>

      <div class="section">
        Date: 08/08/2024<br />
        Applicant Name: Akshay Awasthi
      </div>

      <div class="table-wrapper">
        <table class="table">
            <tr>
                <th>S.No</th>
                <th>Parameters</th>
                <th>Details</th>
            </tr>
            <tr>
                <td>i</td>
                <td>Loan amount</td>
                <td>{{number_format($loan_amount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>ii</td>
                <td>Total interest charge</td>
                <td>{{number_format(($totalInterest ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>iii</td>
                <td>Other Charges</td>
                <td>0.0</td>
            </tr>
            <tr>
                <td>a</td>
                <td>Processing fees</td>
                <td>{{number_format($processingFeeAmount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>b</td>
                <td>Broken Period Interest</td>
                <td>0.0</td>
            </tr>
            <tr>
                <td>c</td>
                <td>GST</td>
                <td>{{number_format($ECSGST ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>iv</td>
                <td>Net disbursed amount</td>
                <td>{{number_format($disbursal_amount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>v</td>
                <td>Total repayment</td>
                <td>{{number_format($total_repayment_amount ?? 0) }}</td>
            </tr>
            <tr>
                <td>vi</td>
                <td>APR</td>
                <td>{{ $apr ?? 'N/A' }}%</td>
            </tr>
            <tr>
                <td>vii</td>
                <td>Loan Tenure (Days)</td>
                <td>{{ $loan_tenure ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>viii</td>
                <td>Repayment frequency</td>
                <td>Bullet</td>
            </tr>
            <tr>
                <td>ix</td>
                <td>No. of instalments</td>
                <td>1</td>
            </tr>
            <tr>
                <td>x</td>
                <td>Instalment amount</td>
                <td>{{number_format(($total_repayment_amount ?? 0)) }}</td>
            </tr>
            <tr>
                <td>xi</td>
                <td>Penal interest rate</td>
                <td>0.25</td>
            </tr>
            <tr>
                <td>xii</td>
                <td>Other penal interest</td>
                <td>0.0</td>
            </tr>
            <tr>
                <td>xiii</td>
                <td>Cooling-off period</td>
                <td>3 Days</td>
            </tr>
            <tr>
                <td>xiv</td>
                <td>LSP/Recovery agent</td>
                <td>Altura Financial Services Limited</td>
            </tr>
            <tr>
                <td>xv</td>
                <td>Grievance Redressal Officer</td>
                <td>GRO - [Contact Info]</td>
            </tr>
        </table>        
      </div>

      <div class="signature">
        <div>
          Warm regards,<br /><br />
          <strong>Ravi Shankar Kumar</strong><br />
          Authorised Signatory<br />
          Altura Financial Services Ltd.
        </div>
        <div class="accepted">
          Accepted<br /><br />
          {Signature}<br />
          Borrower
        </div>
      </div>
    </div>

    <!-- Loan Agreement Section -->
    <div class="loan-agreement">
      <div class="subheading">
        <h3>Loan Agreement</h3>
      </div>

      <div class="section">
        This Loan Agreement is made and executed at Delhi on the date and year mentioned in Sanction Letter by M/s Altura Financial Services Limited (Altura), a company incorporated under the provisions of the Companies Act, 2013 bearing CIN- U65100DL2013PLC259294, having its registered office at Ground Floor Plot No-121, Block-B, Pocket-4, Sector-23, Dwarka, South West Delhi, New Delhi, 110077 (hereinafter referred to as the "Lender"); and between the Borrower/applicant to this loan agreement. The borrower details are described in Sanction Letter which is part of this loan agreement.
      </div>

      <div class="agreement-section">
        <h4>WHEREAS:</h4>
        <div class="numbered-list">
          <p>1. The Lender is a registered Non-Banking Financial Company (NBFC) duly licensed by the Reserve Bank of India (RBI) to carry out lending activities and has agreed to provide financial assistance to the Borrower upon the terms and conditions set forth herein.</p>
          <p>2. The Borrower has approached the Lender for availing a loan facility to meet financial requirements for personal, business, or other permissible purposes as agreed under this agreement.</p>
          <p>3. The Lender has evaluated the financial standing, creditworthiness, and repayment capacity of the Borrower and has agreed to extend a loan to the Borrower based on the terms and conditions detailed in this agreement, as well as those outlined in the Sanction Letter and KFS.</p>
          <p>NOW, THEREFORE, in consideration of the mutual covenants set forth herein and for other good and valuable consideration, the Parties hereto agree as follows:</p>
        </div>
      </div>

      <div class="agreement-section">
        <h4>1. LOAN AMOUNT AND DISBURSEMENT:</h4>
        <div class="numbered-list">
          <p>1.1 The Lender agrees to provide the Borrower with a loan amount as specified in the Sanction Letter and KFS.</p>
          <p>1.2 The loan shall be disbursed in a single tranche or multiple installments, as per the discretion of the Lender, directly to the Borrower’s bank account or towards payments specified by the Borrower in accordance with the terms of the agreement.</p>
        </div>
      </div>

      <div class="agreement-section">
        <h4>2. INTEREST RATE AND REPAYMENT</h4>
        <div class="numbered-list">
          <p>2.1 The rate of interest applicable to the loan shall be as specified in the Sanction Letter and KFS. Interest shall be charged from the date of disbursal until the payment date. The calculation is based on the daily outstanding principal amount.</p>
          <p>2.2 The Borrower shall repay the loan amount along with the accrued interest on or before the due date as agreed in the repayment schedule provided in the Sanction Letter and KFS.</p>
          <p>2.3 The Borrower acknowledges that any delay or default in repayment shall attract penal charges as specified in the Sanction Letter and KFS.</p>
        </div>
      </div>

      <div class="agreement-section">
        <h4>3. PREPAYMENT AND FORECLOSURE</h4>
        <div class="numbered-list">
          <p>3.1 The Borrower shall have the right to prepay the loan amount before the due date without incurring any prepayment charges.</p>
          <p>3.2 In the event of full foreclosure of the loan before the agreed tenure, there is no foreclosure charges. The Borrower shall pay the interest till the date of payment.</p>
        </div>
      </div>

      <div class="agreement-section">
        <h4>4. BORROWER'S OBLIGATIONS</h4>
        <div class="numbered-list">
          <p>4.1 The Borrower shall utilize the loan amount strictly for the purpose mentioned in the Sanction Letter and KFS.</p>
          <p>4.2 The Borrower shall not default in the repayment of the loan or any interest thereon.</p>
          <p>4.3 The Borrower shall promptly notify the Lender of any change in financial status or inability to meet repayment obligations.</p>
        </div>
      </div>

      <div class="agreement-section">
        <h4>5. EVENTS OF DEFAULT</h4>
        <div class="numbered-list">
          <p>5.1 Non-payment of any principal or interest on the due date.</p>
          <p>5.2 Any misrepresentation or suppression of material facts by the Borrower.</p>
          <p>5.3 The Borrower becoming insolvent or facing legal proceedings affecting financial obligations.</p>
          <p>5.4 If the Borrower defaults, the Lender shall have the right to recall the entire loan amount and initiate legal proceedings for recovery.</p>
          <p>5.5 If the Borrower defaults, the Lender shall have the right to approach borrower for recovery of loan or appoint Loan Service Provider (LSP) for the recovery of the outstanding due in terms of RBI DLG Guidelines.</p>
          <p>5.6 If the Borrower defaults, the Lender shall have the right to take legal action against the borrower for the recovery of its dues.</p>
        </div>
      </div>

      <div class="agreement-section">
        <h4>6. GOVERNING LAW AND JURISDICTION</h4>
        <div class="numbered-list">
          <p>6.1 This Agreement shall be governed by and construed in accordance with the laws of India.</p>
          <p>6.2 Any disputes arising out of or in connection with this agreement shall be subject to the exclusive jurisdiction of the courts in Delhi.</p>
        </div>
      </div>

      <div class="agreement-section">
        <h4>7. AMENDMENTS AND WAIVERS</h4>
        <div class="numbered-list">
          <p>7.1 Any amendments to this agreement shall be made in writing and signed by both parties.</p>
          <p>7.2 The failure of the Lender to enforce any provision shall not constitute a waiver of its rights.</p>
        </div>
      </div>

      <div class="agreement-section">
        <h4>8. MISCELLANEOUS</h4>
        <div class="numbered-list">
          <p>8.1 The Borrower acknowledges that the terms of this agreement, including those in the Sanction Letter and KFS, are binding and enforceable.</p>
          <p>8.2 Any notices required under this agreement shall be sent to the registered addresses of the respective parties.</p>
        </div>
      </div>

      <div class="section">
        IN WITNESS WHEREOF, the parties have executed this agreement as of the date first written above.
      </div>

      <div class="signature">
        <div>
          Warm regards,<br /><br />
          <strong>Ravi Shankar Kumar</strong><br />
          Authorised Signatory<br />
          Altura Financial Services Ltd.
        </div>
        <div class="accepted">
          Accepted<br /><br />
          {Signature}<br />
          Borrower
        </div>
      </div>
    </div>
  </body>
</html>