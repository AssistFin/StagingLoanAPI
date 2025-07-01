<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sanction Letter / Loan Agreement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 40px;

        }

        /* Header Styles */
        .header {
            text-align: center;
            font-size: 14px;
            margin-bottom: 20px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }

        .header-title {
            font-size: 40px;
        }

        h2 {
            font-size: 20px;
            text-align: center;
            text-decoration: underline;
            margin-bottom: 15px;
        }

        h3 {
            font-size: 16px;
            margin-top: 20px;
            text-align: left;
        }

        h4 {
            font-size: 14px;
            margin-top: 15px;
            text-align: left;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            page-break-inside: auto;
        }

        table,
        th,
        td {
            border: 1px solid #333;
        }

        th,
        td {
            padding: 8px;
            text-align: center;
        }

        .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .signature img{
            width: 170px;
        }

        .mark {
            background-color: yellow;
        }

        .terms-list {
            margin-left: 20px;
        }

        .section {
            margin-bottom: 25px;
        }

        .date {
            float: right;
        }

        ul {
            list-style-type: disc;
            margin: 0 0 10px 20px;
            padding: 0;
        }
        .kfs-section{
            margin-top: 20px;
        }
        .loan-agreement{
            margin-top: 20px;
        }

        /* Print Styles for Header Repetition */
        @media print {
            body {
                margin: 1cm;
                /* Standard PDF margin */
            }

            .sanction-letter,
            .kfs-section,
            .loan-agreement {
                page-break-before: auto;
            }

            .kfs-section,
            .loan-agreement {
                page-break-before: always;
            }

            .header {
                position: relative;
                top: 0;
                display: block;
                /* Ensure header is visible */
                -webkit-print-color-adjust: exact;
                /* Preserve colors in print */
            }

            .section,
            .signature {
                page-break-inside: avoid;
            }

            table {
                page-break-inside: auto;
            }
        }

        /* Responsive Styles */
        @media (max-width: 708px){
            .header-title{
                font-size: 32px;
            }
        }
        @media (max-width: 600px) {
            table {
                font-size: 12px;
            }

            .header {
                font-size: 12px;
            }
            .header {
                font-size: 12px;
            }
            .header-title {
                font-size: 25px;
            }

            .signature {
                flex-direction: column;
                text-align: center;
            }
        }
        @media (max-width: 480px){
            .header-title{
                font-size: 18px;
            }
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    <div class="sanction-letter">
        <div class="header">
            <strong class="header-title">Altura Financial Services Limited</strong><br>
            <strong>Add:</strong> Ground Floor Plot No-121, Block-B, Pocket-4, Sector-23, Dwarka, New Delhi- 110077<br>
            <strong>CIN No.:</strong> U65100DL2013PLC259294, <strong>RBI Reg No.:</strong> N-14.03308,
            <strong>Email:</strong> <a href="afs@alturafinancials.com">afs@alturafinancials.com</a>
        </div>
        <h2>Sanction Letter</h2>
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
            <strong>{{$sanction_date}}</strong>. This Sanction Letter is based on the information provided by you in your
            loan request/application. We are pleased to inform you that your loan application has been approved with the
            following terms and conditions:-
        </div>
        <table>
            <tr>
                <th>Sl. No.</th>
                <th>Particulars</th>
                <th>Terms Details</th>
            </tr>
            <tr>
                <td>1.</td>
                <td>Loan Sanctioned Amount</td>
                <td><span class="mark">{{number_format($loan_amount, 2)}}</span></td>
            </tr>
            <tr>
                <td>2.</td>
                <td>Rate of Interest (ROI) per day</td>
                <td><span class="mark">{{number_format($rate_of_interest, 2)}}%</span></td>
            </tr>
            <tr>
                <td>3.</td>
                <td>Loan Tenure (days)</td>
                <td><span class="mark">{{$loan_tenure}}</span></td>
            </tr>
            <tr>
                <td>4.</td>
                <td>Processing Fee + GST (%)</td>
                <td><span class="mark">{{number_format($processingFeeAmount + $ECSGST, 2)}}%</span></td>
            </tr>
            <tr>
                <td>5.</td>
                <td>Disbursal Amount (1-4)</td>
                <td><span class="mark">{{number_format($disbursal_amount, 2)}}</span></td>
            </tr>
            <tr>
                <td>6.</td>
                <td>Total Repayment Amount</td>
                <td><span class="mark">{{number_format($total_repayment_amount, 2)}}</span></td>
            </tr>
            <tr>
                <td>7.</td>
                <td>Penal Charges (per day)</td>
                <td><span class="mark">0.25%</span></td>
            </tr>
            <tr>
                <td>8.</td>
                <td>ECS/ENACH/Cheque bounce charges (Rs) + GST</td>
                <td><span class="mark">0.0</span></td>
            </tr>
        </table>

        <div class="section">
            <ul>
                <li>Please note that this sanction letter is issued in response to your loan application and based on
                    the information you have provided to us.</li>
                <li>If you wish to accept this sanction letter, please go through the enclosed <strong>Key Fact
                        Statement (KFS) and Loan Agreement</strong> and confirm your acceptance of the agreement and the
                    Terms & Conditions.</li>
            </ul>
            <p>We genuinely appreciate your trust in Altura Financial Services Limited as your financial partner. Thank
                you for choosing us for your financial needs.</p>
        </div>

        <div class="signature">
            <div>
                Warm regards,<br />
                <img src="{{ public_path('assets/admin/images/sign.png') }}" />
                <br />
                <strong>Ravi Shankar Kumar</strong><br />
                Authorised Signatory<br />
                Altura Financial Services Ltd.<br />
                <br />
                <br />
                <strong>Accepted by Borrower</strong>
            </div>
        </div>
    </div>
    <div class="page-break"></div>

    <div class="kfs-section">
        <div class="header">
            <strong class="header-title">Altura Financial Services Limited</strong><br>
            <strong>Add:</strong> Ground Floor Plot No-121, Block-B, Pocket-4, Sector-23, Dwarka, New Delhi- 110077<br>
            <strong>CIN No.:</strong> U65100DL2013PLC259294, <strong>RBI Reg No.:</strong> N-14.03308,
            <strong>Email:</strong> <a href="afs@alturafinancials.com">afs@alturafinancials.com</a>
        </div>
        <h2>Key Fact Statement (KFS)</h2>
        <table>
            <tr>
                <th>S.No</th>
                <th>Parameters</th>
                <th>Details</th>
            </tr>
            <tr>
                <td>i</td>
                <td>Loan amount</td>
                <td><span class="mark" style="font-family: DejaVu Sans;">₹ {{number_format($loan_amount, 2) }}</span></td>
            </tr>
            <tr>
                <td>ii</td>
                <td>Total interest charge during the entire tenor of the loan</td>
                <td><span class="mark" style="font-family: DejaVu Sans;">₹ {{number_format(($totalInterest), 2) }}</span></td>
            </tr>
            <tr>
                <td>iii</td>
                <td>Other up-front Charges</td>
                <td><span class="mark" style="font-family: DejaVu Sans;">₹ 0.0</span></td>
            </tr>
            <tr>
                <td>a</td>
                <td>Processing fees, if any</td>
                <td><span class="mark" style="font-family: DejaVu Sans;">₹ {{number_format($processingFeeAmount, 2) }}</span></td>
            </tr>
            <tr>
                <td>b</td>
                <td>Broken-Period Interest (BPI)</td>
                <td><span class="mark" style="font-family: DejaVu Sans;">₹ 0.0</span></td>
            </tr>
            <tr>
                <td>c</td>
                <td>Insurance charges, if any</td>
                <td></td>
            </tr>
            <tr>
                <td>d</td>
                <td>Other Charges (GST)</td>
                <td><span class="mark" style="font-family: DejaVu Sans;">₹ {{number_format($ECSGST, 2) }}</span></td>
            </tr>
            <tr>
                <td>iv</td>
                <td>Net disbursed amount ((i)-(iii))</td>
                <td><span class="mark" style="font-family: DejaVu Sans;">₹ {{number_format($disbursal_amount, 2) }}</span></td>
            </tr>
            <td>v</td>
            <td>Total amount to be paid by the borrower</td>
            <td><span class="mark" style="font-family: DejaVu Sans;">₹ {{number_format($total_repayment_amount, 2) }}</span></td>
            </tr>
            <tr>
                <td>vi</td>
                <td>Annual percentage rate (Annual) -- Effective annualised interest rate (in percentage)</td>
                <td><span class="mark">{{ $apr }}%</span></td>
            </tr>
            <tr>
                <td>vii</td>
                <td>Tenor of the Loan (In days)</td>
                <td><span class="mark">{{ $loan_tenure }}</span></td>
            </tr>
            <tr>
                <td>viii</td>
                <td>Repayment frequency by the borrower</td>
                <td>Bullet</td>
            </tr>
            <tr>
                <td>ix</td> 
                <td>Number of instalments of repayment</td>
                <td>1</td>
            </tr>
            <tr>
                <td>x</td>
                <td>Amount of each instalment of repayment</td>
                <td><span class="mark" style="font-family: DejaVu Sans;">₹ {{number_format($total_repayment_amount, 2) }}</span></td>
            </tr>
            <tr>
                <td>xi</td>
                <td>Rate of annualised penal interest in case of delayed payments (in percentage)</td>
                <td>0.25%</td>
            </tr>
            <tr>
                <td>xii</td>
                <td>Rate of annualised other penal interest</td>
                <td>0.0</td>
            </tr>
            <tr>
                <td></td>
                <td><strong>Other Disclosures</strong></td>
                <td></td>
            </tr>
            <tr>
                <td>xiii</td>
                <td>Cooling off/look-up period during which borrower shall not be charged any penalty on prepayment of
                    loan</td>
                <td>3 Days</td>
            </tr>
            <tr>
                <td>xiv</td>
                <td>Details of LSP acting as recovery agent and authorised to approach the borrower</td>
                <td>AssistFin Technologies Private Limited (LoanOne)</td>
            </tr>
            <tr>
                <td>xv</td>
                <td>Name, designation, address and phone number of nodal grievance redressal officer designated
                    specifically to deal with FinTech/ digital lending related complaints/ issues</td>
                <td>Grievance Officer</td>
            </tr>
        </table>

        <h3>Terms and Condition:</h3>
        <ol class="terms-list">
            <li>Please note that this sanction letter is issued in response to your loan application and based on the
                information you have provided to us. You acknowledge that you have given your KYC details and given
                consent to access your credit report from Bureau.</li>
            <li>The processing fees as charged are mentioned in the schedule, and the same will be deducted from your
                loan amount upfront.</li>
            <li>You are under no obligation to accept the sanctioned loan. If you do not accept the sanction letter and
                the enclosed agreement, your loan application will be considered withdrawn, and you will not be liable
                to pay any processing fees/charges.</li>
            <li>Loan disbursement will be made to bank account provided in the loan agreement.</li>
            <li>Processing fees (including GST) will be deducted from Loan Amount before disbursal.</li>
            <li>This sanction can be revoked and / or cancelled on the sole discretion of the Company.</li>
            <li>Your repayment schedule shall be depending upon the actual date of disbursement of Loan.</li>
            <li>Your Installment will be deducted from your bank account in which mandate will be registered.</li>
            <li>You can also pay the installment from any other mode provided by the lender.</li>
            <li>Penal Charges @0.25% per day will be charged, in case of repayment overdue on the principal due amount
                from the date of default.</li>
            <li>You can make the pre-payment or foreclose the loan without any penalty/foreclosure charges anytime.</li>
            <li>You shall be required to bear and pay applicable stamp duty. Such charges shall be non-refundable.</li>
            <li>Lender offers different interest rate based on loan amount, tenor, down payment, payment history, credit
                score provided by credit information companies, borrower's age, income, type of documents provided by
                the applicant and any other information as may be required for the purpose of credit evaluation.</li>
            <li>You understand and acknowledge that this loan has been granted with clear understanding that you hereby
                waive all rights including without limitation, immunity in respect of any repayment of loan byway of
                injunction or moratorium available to you in the capacity of borrower.</li>
            <li>You understand and acknowledge that the language of this Sanction letter is known to you and that you
                have read and understood in vernacular language, the features of the loan product and the terms and
                conditions mentioned herein and contained in any other loan documents and shall abide by them including
                any amendment thereto, with free will and volition.</li>
            <li>You understand and acknowledge the cost and charges associated with the loan and that once the
                disbursement is made in your bank account your loan cannot be withdrawn and reversed, apart from the
                closing the loan as per colling period guidelines.</li>
            <li>Please refer "Loan Application Terms & Conditions" and "Loan Agreement" for obtaining loans from M/s
                Altura Financial Services Limited for terms and conditions applicable to this loan, a copy of which has
                been provided to you and is also available at website of Altura, which is <a
                    href="https://www.alturafinancials.com/">https://www.alturafinancials.com/</a></li>
        </ol>

        <div class="signature">
            <div>
                Warm regards,<br />
                <img src="{{ public_path('assets/admin/images/sign.png') }}" />
                <br />
                <strong>Ravi Shankar Kumar</strong><br />
                Authorised Signatory<br />
                Altura Financial Services Ltd.<br />
                <br />
                <br />
                <strong>Accepted by Borrower</strong>
            </div>
        </div>
    </div>
    <div class="page-break"></div>

    <div class="loan-agreement">
        <div class="header">
            <strong class="header-title">Altura Financial Services Limited</strong><br>
            <strong>Add:</strong> Ground Floor Plot No-121, Block-B, Pocket-4, Sector-23, Dwarka, New Delhi- 110077<br>
            <strong>CIN No.:</strong> U65100DL2013PLC259294, <strong>RBI Reg No.:</strong> N-14.03308,
            <strong>Email:</strong> <a href="afs@alturafinancials.com">afs@alturafinancials.com</a>
        </div>
        <h2>LOAN AGREEMENT</h2>
        <p>This Loan Agreement is made and executed at Delhi on the date and year mentioned in Sanction Letter by M/s
            Altura Financial Services Limited (Altura), a company incorporated under the provisions of the Companies
            Act, 2013 bearing CIN- U65100DL2013PLC259294, having its registered office at Ground Floor Plot No-121,
            Block-B, Pocket-4, Sector-23, Dwarka, South West Delhi, New Delhi, 110077 (hereinafter referred to as the
            "Lender"); and between the Borrower/applicant to this loan agreement. The borrower details are described in
            Sanction Letter which is part of this loan agreement.</p>

        <h3>WHEREAS:</h3>
        <ol>
            <li>The Lender is a registered Non-Banking Financial Company (NBFC) duly licensed by the Reserve Bank of
                India (RBI) to carry out lending activities and has agreed to provide financial assistance to the
                Borrower upon the terms and conditions set forth herein.</li>
            <li>The Borrower has approached the Lender for availing a loan facility to meet financial requirements for
                personal, business, or other permissible purposes as agreed under this agreement.</li>
            <li>The Lender has evaluated the financial standing, creditworthiness, and repayment capacity of the
                Borrower and has agreed to extend a loan to the Borrower based on the terms and conditions detailed in
                this agreement, as well as those outlined in the Sanction Letter and KFS.</li>
        </ol>

        <h3>NOW, THEREFORE, in consideration of the mutual covenants set forth herein and for other good and valuable
            consideration, the Parties hereto agree as follows:</h3>

        <h4>1. LOAN AMOUNT AND DISBURSEMENT:</h4>
        <ol>
            <p>1.1 The Lender agrees to provide the Borrower with a loan amount as specified in the Sanction Letter and
                KFS.</p>
            <p>1.2 The loan shall be disbursed in a single tranche or multiple installments, as per the discretion of the
                Lender, directly to the Borrower's bank account or towards payments specified by the Borrower in
                accordance with the terms of the agreement.</p>
        </ol>

        <h4>2. INTEREST RATE AND REPAYMENT</h4>
        <ol>
            <p>2.1 The rate of interest apppcable to the loan shall be as specified in the Sanction Letter and KFS.
                Interest shall be charged from the date of disbursal until the payment date. The calculation is based on
                the daily outstanding principal amount.</p>
            <p>2.2 The Borrower shall repay the loan amount along with the accrued interest on or before the due date as
                agreed in the repayment schedule provided in the Sanction Letter and KFS.</p>
            <p>2.3 The Borrower acknowledges that any delay or default in repayment shall attract penal charges as
                specified in the Sanction Letter and KFS.</p>
        </ol>

        <h4>3. PREPAYMENT AND FORECLOSURE</h4>
        <ol>
            <p>3.1 The Borrower shall have the right to prepay the loan amount before the due date without incurring any
                prepayment charges.</p>
            <p>3.2 In the event of full foreclosure of the loan before the agreed tenure, there is no foreclosure charges.
                The Borrower shall pay the interest till the date of payment.</p>
        </ol>

        <h4>4. BORROWER'S OBpGATIONS</h4>
        <ol>
            <p>4.1 The Borrower shall utipze the loan amount strictly for the purpose mentioned in the Sanction Letter and
                KFS.</p>
            <p>4.2 The Borrower shall not default in the repayment of the loan or any interest thereon.</p>
            <p>4.3 The Borrower shall promptly notify the Lender of any change in financial status or inabipty to meet
                repayment obpgations.</p>
        </ol>

        <h4>5. EVENTS OF DEFAULT</h4>
        <ol>
            <p>5.1 Non-payment of any principal or interest on the due date.</p>
            <p>5.2 Any misrepresentation or suppression of material facts by the Borrower.</p>
            <p>5.3 The Borrower becoming insolvent or facing legal proceedings affecting financial obpgations.</p>
            <p>5.4 If the Borrower defaults, the Lender shall have the right to recall the entire loan amount and initiate
                legal proceedings for recovery.</p>
            <p>5.5 If the Borrower defaults, the Lender shall have the right to approach borrower for recovery of loan or
                appoint Loan Service Provider (LSP) for the recovery of the outstanding due in terms of RBI DLG
                Guidepnes.</p>
            <p>5.6 If the Borrower defaults, the Lender shall have the right to take legal action against the borrower for
                the recovery of its dues.</p>
        </ol>

        <h4>6. GOVERNING LAW AND JURISDICTION</h4>
        <ol>
            <p>6.1 This Agreement shall be governed by and construed in accordance with the laws of India.</p>
            <p>6.2 Any disputes arising out of or in connection with this agreement shall be subject to the exclusive
                jurisdiction of the courts in Delhi.</p>
        </ol>

        <h4>7. AMENDMENTS AND WAIVERS</h4>
        <ol>
            <p>7.1 Any amendments to this agreement shall be made in writing and signed by both parties.</p>
            <p>7.2 The failure of the Lender to enforce any provision shall not constitute a waiver of its rights.</p>
        </ol>

        <h4>8. MISCELLANEOUS</h4>
        <ol>
            <p>8.1 The Borrower acknowledges that the terms of this agreement, including those in the Sanction Letter and
                KFS, are binding and enforceable.</p>
            <p>8.2 Any notices required under this agreement shall be sent to the registered addresses of the respective
                parties.</p>
        </ol>

        <div class="signature-section">
            <p>IN WITNESS WHEREOF, the parties have executed this agreement as of the date first written above.</p>

            <div class="signature">
                <div>
                    <br />
                    <br />
                    <strong>Borrower</strong>
                </div>
                <div>
                    <img src="{{ public_path('assets/admin/images/sign.png') }}" /><br />
                    Ravi Shankar Kumar<br>
                    Authorised Signatory<br>
                    Altura Financial Services Ltd.
                </div>
            </div>
        </div>
    </div>
</body>

</html>