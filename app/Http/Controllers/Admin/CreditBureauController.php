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
        $gender = $request->get('gender');
        $city = $request->get('city');
        $state = $request->get('state');
        $pincode = $request->get('pincode');
        $loan_no = $request->get('loan_no');
        $user_id = $request->get('user_id');
        $stateCode = '99';
        if(ucwords($state) == 'JAMMU & KASHMIR' || ucwords($state) == 'J & K' || ucwords($state) == 'JAMMU AND KASHMIR'){ $stateCode = '01';}
        if(ucwords($state) == 'HIMACHAL PRADESH'){ $stateCode = '02';}
        if(ucwords($state) == 'PUNJAB'){ $stateCode = '03';}
        if(ucwords($state) == 'CHANDIGARH'){ $stateCode = '04';}
        if(ucwords($state) == 'UTTRANCHAL' || ucwords($state) == 'UTTRAKHAND'){ $stateCode = '05';}
        if(ucwords($state) == 'HARAYANA'){ $stateCode = '06';}
        if(ucwords($state) == 'DELHI'){ $stateCode = '07';}
        if(ucwords($state) == 'RAJASTHAN'){ $stateCode = '08';}
        if(ucwords($state) == 'UTTAR PRADESH'){ $stateCode = '09';}
        if(ucwords($state) == 'BIHAR'){ $stateCode = '10';}
        if(ucwords($state) == 'SIKKIM'){ $stateCode = '11';}
        if(ucwords($state) == 'ARUNACHAL PRADESH'){ $stateCode = '12';}
        if(ucwords($state) == 'NAGALAND'){ $stateCode = '13';}
        if(ucwords($state) == 'MANIPUR'){ $stateCode = '14';}
        if(ucwords($state) == 'MIZORAM'){ $stateCode = '15';}
        if(ucwords($state) == 'TRIPURA'){ $stateCode = '16';}
        if(ucwords($state) == 'MEGHALAYA'){ $stateCode = '17';}
        if(ucwords($state) == 'ASSAM'){ $stateCode = '18';}
        if(ucwords($state) == 'WEST BENGAL'){ $stateCode = '19';}
        if(ucwords($state) == 'JHARKHAND'){ $stateCode = '20';}
        if(ucwords($state) == 'ORRISA'){ $stateCode = '21';}
        if(ucwords($state) == 'CHHATTISGARH'){ $stateCode = '22';}
        if(ucwords($state) == 'MADHYA PRADESH'){ $stateCode = '23';}
        if(ucwords($state) == 'GUJRAT'){ $stateCode = '24';}
        if(ucwords($state) == 'DAMAN and DIU'){ $stateCode = '25';}
        if(ucwords($state) == 'DADARA and NAGAR HAVELI'){ $stateCode = '26';}
        if(ucwords($state) == 'MAHARASHTRA'){ $stateCode = '27';}
        if(ucwords($state) == 'ANDHRA PRADESH'){ $stateCode = '28';}
        if(ucwords($state) == 'KARNATAKA' || $state == 'Karnataka'){ $stateCode = '29';}
        if(ucwords($state) == 'GOA'){ $stateCode = '30';}
        if(ucwords($state) == 'LAKSHADWEEP'){ $stateCode = '31';}
        if(ucwords($state) == 'KERALA'){ $stateCode = '32';}
        if(ucwords($state) == 'TAMILNADU'){ $stateCode = '33';}
        if(ucwords($state) == 'PONDICHERRY'){ $stateCode = '34';}
        if(ucwords($state) == 'ANDAMAN and NICOBAR ISLANDS'){ $stateCode = '35';}
        if(ucwords($state) == 'TELANGANA'){ $stateCode = '36';}

        // echo '<pre>';
        // echo '-'.$firstname;
        // echo '-'.$lastname;
        // echo '-'.$gender;
        // echo '-'.$pan;
        // echo '-'.$dobformattedDate;
        // echo '-'.$mobile;
        // echo '-'.$house_no;
        // echo '-'.$city;
        // echo '-'.$state;
        // echo '-'.$stateCode;
        // echo '-'.$pincode;

        //die('called');

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
                                <Surname>'.$lastname.'</Surname>
                                <FirstName>'.$firstname.'</FirstName>
                                <MiddleName/>
                                <GenderCode>'.$gender.'</GenderCode>
                                <IncomeTaxPAN>'.$pan.'</IncomeTaxPAN>
                                <PassportNumber/>
                                <VoterIdentityCard/>
                                <Driver_License_Number/>
                                <Ration_Card_Number/>
                                <Universal_ID_Number/>
                                <DateOfBirth>'.$dob.'</DateOfBirth>
                                <MobilePhone>'.substr($mobile, -10).'</MobilePhone>
                                <EMailId></EMailId>
                            </Applicant>
                            <Address>
                                <FlatNoPlotNoHouseNo>'.$house_no.'</FlatNoPlotNoHouseNo>
                                <BldgNoSocietyName/>
                                <City>'.$city.'</City>
                                <State>'.$stateCode.'</State>
                                <PinCode>'.$pincode.'</PinCode>
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
        //Storage::put("experian/Tilak_{$timestamp}_{$randomString}_request.xml", $xmlRequestBody);
        //Storage::put("experian/Tilak_{$timestamp}_{$randomString}_response.xml", $rawOut);

        ini_set('memory_limit', '512M');
        // 5. Generate PDF with key data
        //$pdf = Pdf::loadView('admin.creditbureau.pdf-template', [ 'data' => $jsonData ]);
        //$filename = "Tilak_{$timestamp}_{$randomString}.pdf";
        //Storage::disk('public')->put("experian/credit_reports/$filename", $pdf->output());
        //$pdfPath = asset("storage/experian/credit_reports/$filename");
        $pdfPath = '';
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
        //$pdfurl = Storage::url($pdfPath);
        $pdfurl = null;
        
        if(empty($request->get('verify'))){
            return view('admin.creditbureau.credit-bureau', compact( 'userRecords', 'data', 'pdfurl'));
        }
        
    }

    public function show($id)
    {
        $record = DB::table('experian_credit_reports')->where('lead_id', $id)->first();

        if (!$record) {
            return response('Record not found.', 404);
        }

        $data = json_decode($record->response_data, true);

        return view('admin.creditbureau.pdf-template', compact('data'));
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
