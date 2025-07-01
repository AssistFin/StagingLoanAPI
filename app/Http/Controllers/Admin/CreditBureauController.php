<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoanApproval;
use App\Models\LoanBankDetails;
use App\Models\LoanDisbursal;
use App\Models\UtrCollection;
use App\Models\LoanApplication;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Models\CreditBureau;
use DB;

class CreditBureauController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Experian Credit Bureau Report';
        
        $query = LoanApplication::with(['user:id,firstname,lastname,mobile','personalDetails','employmentDetails', 'kycDetails', 'loanDocument', 'addressDetails', 'bankDetails'])
        ->leftJoin('experian_credit_reports', 'experian_credit_reports.lead_id', '=', 'loan_applications.id')
        ->join('pan_data', 'pan_data.user_id', '=', 'loan_applications.user_id')
        ->orderByDesc('loan_applications.user_id');
        $userRecords = $query->paginate(25);

        return view('admin.creditbureau.credit-bureau', compact('pageTitle', 'userRecords'));
    }

    public function checkReportByExperian(Request $request)
    {

        $firstname = $request->get('firstname');
        $lastname = $request->get('lastname');
        $mobile = $request->get('mobile');
        $dob = $request->get('dob');
        $house_no = $request->get('house_no');
        $pan = $request->get('pan');
        $city = $request->get('city');
        $pincode = $request->get('pincode');
        $loan_no = $request->get('loan_no');
        $user_id = $request->get('user_id');

        $xmlRequestBody = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:cbv2">
                <soapenv:Header/>
                <soapenv:Body>
                    <urn:process>
                        <urn:in>
                            <INProfileRequest>
                            <Identification>
                            <XMLUser>'.config('services.experiancreditbureau.ecb_user').'</XMLUser>
                                <XMLPassword>'.config('services.experiancreditbureau.ecb_password').'</XMLPassword>
                            </Identification>
                            <Application>
                                <EnquiryReason>6</EnquiryReason>
                                <AmountFinanced>0</AmountFinanced>
                                <DurationOfAgreement>0</DurationOfAgreement>
                                <ScoreFlag>3</ScoreFlag>
                                <PSVFlag>0</PSVFlag>
                            </Application>
                            <Applicant>
                                <Surname>Sen</Surname>
                                <FirstName>Tilak</FirstName>
                                <MiddleName/>
                                <GenderCode>2</GenderCode>
                                <IncomeTaxPAN>TFPPS4289C</IncomeTaxPAN>
                                <PassportNumber/>
                                <VoterIdentityCard/>
                                <Driver_License_Number/>
                                <Ration_Card_Number/>
                                <Universal_ID_Number/>
                                <DateOfBirth>19760817</DateOfBirth>
                                <MobilePhone>9295390875</MobilePhone>
                                <EMailId></EMailId>
                            </Applicant>
                            <Address>
                                <FlatNoPlotNoHouseNo>Chinar5 CHS</FlatNoPlotNoHouseNo>
                                <BldgNoSocietyName/>
                                <City>Mumbai</City>
                                <State>27</State>
                                <PinCode>400005</PinCode>
                            </Address>
                            </INProfileRequest>
                        </urn:in>
                    </urn:process>
                </soapenv:Body>
                </soapenv:Envelope>';

        $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
            ])->withBody($xmlRequestBody, 'text/xml')
            ->post(config('services.experiancreditbureau.ecb_url'));

        try {
            $soapXml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
            $body = $soapXml->children('http://schemas.xmlsoap.org/soap/envelope/')->Body;
            $rawOut = (string) $body->children('urn:cbv2')->processResponse->out ?? null;

            if (!$rawOut || !str_contains($rawOut, '<?xml')) {
                throw new \Exception('Invalid inner XML in <out> tag');
            }

            $parsed = simplexml_load_string($rawOut, 'SimpleXMLElement', LIBXML_NOCDATA);
            $jsonData = json_decode(json_encode($parsed), true);

        } catch (\Exception $e) {
            \Log::error('XML Parsing Failed: ' . $e->getMessage());
            dd('Failed to parse XML', $response->body());
        }


        // 4. Store raw request & response
        $randomString = Str::random(10);
        $timestamp = now()->format('Ymd_His');
        Storage::put("experian/Tilak_{$timestamp}_{$randomString}_request.xml", $xmlRequestBody);
        Storage::put("experian/Tilak_{$timestamp}_{$randomString}_response.xml", $rawOut);

        // 5. Generate PDF with key data
        $pdf = Pdf::loadView('admin.creditbureau.pdf-template', [ 'data' => $jsonData ]);
        $filename = "Tilak_{$timestamp}_{$randomString}.pdf";
        Storage::disk('public')->put("experian/credit_reports/$filename", $pdf->output());
        $pdfPath = asset("storage/experian/credit_reports/$filename");

        DB::table('experian_credit_reports')->insert([
            'user_id' => $request->get('user_id'),
            'lead_id' => Str::afterLast($request->get('loan_no'), '-'),
            'request_data' => $xmlRequestBody,
            'response_data' => json_encode($jsonData),
            'pdf_url' => $pdfPath,
            'created_at' => now(),
        ]);

        $query = LoanApplication::with(['user:id,firstname,lastname,mobile','personalDetails','employmentDetails', 'kycDetails', 'loanDocument', 'addressDetails', 'bankDetails'])
        ->leftJoin('experian_credit_reports', 'experian_credit_reports.lead_id', '=', 'loan_applications.id')
        ->join('pan_data', 'pan_data.user_id', '=', 'loan_applications.user_id')
        ->orderByDesc('loan_applications.user_id');
        $userRecords = $query->paginate(25);

        $data = $jsonData;
        $pdfurl = Storage::url($pdfPath);
        
        if(empty($request->get('verify'))){
            return view('admin.creditbureau.credit-bureau', compact( 'userRecords', 'data', 'pdfurl'));
        }
        
    }

    public function checkReportByExperian22(Request $request)
    {
        //require 'vendor/autoload.php';
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.office365.com'; // e.g., smtp.gmail.com
            $mail->SMTPAuth   = true;
            $mail->Username   = 'care@loanone.in';
            $mail->Password   = 'LoanOne2025#@$';
            $mail->SMTPSecure = 'tls'; // or 'ssl'
            $mail->Port       = 587;   // or 465

            $mail->setFrom('care@loanone.in', 'Test Mail');
            $mail->addAddress('tech.assistfin@gmail.com');

            $mail->Subject = 'SMTP Test';
            $mail->Body    = 'This is a test email sent via SMTP.';
            $mail->SMTPDebug = 3;
            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    public function checkBankAccNoByApproval(Request $request){
        $bank_acc_no = $request->get('bank_acc_no');
        $user_id = $request->get('user_id');
        $id = $request->get('lead_id');
        $bank_status = false;
        
        $account_exists = LoanDisbursal::where('account_no', 'like', '%' . $bank_acc_no . '%')->first();

        if(!empty($account_exists->loan_application_id)){
            if($account_exists->user_id == $user_id){
                $bank_status = true;
            }else{
                $bank_status = false;
            }
        }else{
            $bank_status = true;
        }

        if ($request->ajax()) {
            return response()->json(['bank_status' => $bank_status, 'user_id' => !empty($account_exists->user_id) ? $account_exists->user_id : '']);
        }

        $data = $loans = $paymentLink = [];

        $lead = LoanApplication::with([
            'user',
            'personalDetails', 
            'employmentDetails', 
            'kycDetails', 
            'loanDocument',
            'addressDetails', 
            'bankDetails'
        ])->where('id', $id)->first();

        $aadharData = DB::table('aadhaar_data')->where('user_id', $lead->user->id)->first();

        $panData = DB::table('pan_data')->where('user_id', $lead->user->id)->first();

        $loanApproval = LoanApproval::where('loan_application_id', $id)->first();

        $loanDisbursal = LoanDisbursal::where('loan_application_id', $id)->first();
        
        $experianCreditBureau = CreditBureau::where('lead_id', $id)->first();

        $hasPreviousClosedLoan = LoanApplication::where('user_id', $lead->user->id)
        ->where('id', '!=', $lead->id) // Exclude current loan
        ->where('admin_approval_status', 'approved')
        ->exists();

        $loanUtrCollections = UtrCollection::select(
            'utr_collections.*',
            'loan_applications.loan_no',
            'loan_disbursals.loan_disbursal_number',
            DB::raw("CONCAT(users.firstname, ' ', users.lastname) as user_name")
        )
        ->join('loan_applications', 'loan_applications.id', '=', 'utr_collections.loan_application_id')
        ->join('loan_disbursals', 'loan_disbursals.loan_application_id', '=', 'utr_collections.loan_application_id')
        ->join('users', 'users.id', '=', 'utr_collections.user_id')
        ->where('users.id', $lead->user->id)
        ->where('utr_collections.loan_application_id', $id)
        ->orWhere('utr_collections.user_id', $lead->user->id)
        ->orderByRaw('utr_collections.created_at DESC')
        ->get();

        return view('admin.leads.leads-verify', compact('lead', 'loanApproval', 'loanDisbursal', 'loanUtrCollections', 'aadharData', 'panData', 'hasPreviousClosedLoan', 'loans', 'paymentLink', 'experianCreditBureau', 'bank_status'));
    }
}
