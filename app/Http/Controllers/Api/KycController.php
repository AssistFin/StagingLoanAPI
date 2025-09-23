<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Models\LoanKYCDetails;
use App\Models\LoanApplication;
use App\Models\VendorSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class KycController extends Controller
{
    private $client;
    private $apiKey;
    private $apiSecret;
    private $accessToken;

    /**
     * Create a new controller instance.
     *
     * Instantiates a Guzzle HTTP client and sets the API key, secret, and access token
     * for the current instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.sandbox.api_key');
        $this->apiSecret = config('services.sandbox.api_secret');
        $this->accessToken = $this->authenticate();
    }

/**
 * Authenticates with the KYC API using the stored API key and secret.
 *
 * Sends a POST request to the authentication endpoint with appropriate headers.
 * Returns the access token if authentication is successful.
 *
 * @return string|null The access token, or null if authentication fails.
 * @throws \Exception If an error occurs during the authentication process.
 */

    private function authenticate()
    {
        try {
            $response = $this->client->post('https://api.sandbox.co.in/authenticate', [
                'headers' => [
                    'accept' => 'application/json',
                    'x-api-key' => $this->apiKey,
                    'x-api-secret' => $this->apiSecret,
                    'x-api-version' => '1.0'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('Authentication error: ' . $e->getMessage());
            throw new \Exception('Failed to authenticate with the KYC API');
        }
    }

    public function requestAadharOtp(Request $request)
    {
        $this->validate($request, [
            'loan_application_id' => 'required|exists:loan_applications,id',
        ]);

        $applicationId = $request->loan_application_id;
        Log::info("Starting Aadhaar OTP request for Application ID: {$applicationId}");

        try {
            $existingRecord = LoanKYCDetails::where([
                ['aadhar_number', 'ANAptyuio'],
                ['aadhar_otp_verified', 1]
            ])->first();

            if (!empty($existingRecord)) {
                Log::warning("Duplicate Aadhaar number found for Application ID: {$applicationId}");
                return response()->json([
                    'status' => 'error',
                    'message' => ['error' => ['This Aadhaar number is already registered.']]
                ], 400);
            }

            //BOC For check linked status
            Log::info("Sending Aadhar and PAN request to external API for Application ID: {$applicationId}");

            //$pandata = DB::table('pan_data')->where('user_id', auth()->id())->first();

            $vendorData = VendorSetting::where('kyc','1')->first();

            if($vendorData['vendor'] == 'Sandbox'){
                $response = $this->client->post('https://api.sandbox.co.in/kyc/digilocker/sessions/init', [
                    'headers' => [
                        'Authorization' => $this->accessToken,
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                        'x-api-key' => $this->apiKey,
                        'x-api-version' => '1.0',
                    ],
                    'json' => [
                        '@entity' => 'in.co.sandbox.kyc.digilocker.session.request',
                        'flow' => 'signin',
                        'doc_types' => [
                            "aadhaar"
                        ],
                        'redirect_url' => config('services.cashfree.app_url')."verifyotp"
                    ]
                ]);

                $data = json_decode($response->getBody(), true);

                DB::table('user_kyc_verifications')->updateOrInsert(
                    ['loan_application_id' => $applicationId],  
                    [
                        'requestAadharOtpData' => json_encode($data),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
                Log::info("OTP request response for Application ID {$applicationId}: ", $data);

                if (isset($data['data']['reference_id'])) {
                    LoanKYCDetails::updateOrCreate(
                        ['loan_application_id' => $applicationId],
                        ['aadhar_number' => $request->aadhar_number]
                    );
                    Log::info("Loan KYC updated with Aadhaar number for Application ID: {$applicationId}");
                }

                $loan = LoanApplication::where('id', $request->loan_application_id)->first();
                if ($loan) {
                    $loan->current_step = 'aadharverification';
                    $loan->next_step = 'verifyotp';
                    $loan->save();

                    Log::info("Loan step updated for Application ID: {$applicationId}");
                }

                $session_id = $data['data']['session_id'] ?? null;

                if (!$session_id) {
                    throw new \Exception('Session ID is missing in the API response');
                }

                return response()->json([
                    'status' => 'success',
                    'message' => ['success' => ['Digilocaker Session Created']],
                    'data' => [
                        'session_id' => $session_id,
                        'authorization_url' => $data['data']['authorization_url']
                    ]
                ]);
            }

            if($vendorData['vendor'] == 'Digitap'){
                $payload = [
                    "serviceId"                 => '4',
                    "uid"                       => (string)$request->loan_application_id.'-'.rand(),
                    "isSendOtp"                 => true,
                    "isHideExplanationScreen"   => false,
                    "redirectionUrl"            => 'http://localhost:3000/verifyotp',
                ];

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://svcdemo.digitap.work/ent/v1/kyc/generate-url',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS =>json_encode($payload),
                    CURLOPT_HTTPHEADER => array(
                        'content-type: application/json',
                        'Authorization: Basic ' . base64_encode(config('services.digitap.client_id') . ':' . config('services.digitap.client_secret')),
                    ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);
                $data = json_decode($response, true);

                DB::table('user_kyc_verifications')->updateOrInsert(
                    ['loan_application_id' => $applicationId],  
                    [
                        'requestAadharOtpData' => json_encode($data),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
                Log::info("OTP request response for Application ID {$applicationId}: ", $data);

                if (isset($data['model']['transactionId'])) {
                    LoanKYCDetails::updateOrCreate(
                        ['loan_application_id' => $applicationId],
                        ['aadhar_number' => $request->aadhar_number]
                    );
                    Log::info("Loan KYC updated with Aadhaar number for Application ID: {$applicationId}");
                }

                $loan = LoanApplication::where('id', $request->loan_application_id)->first();
                if ($loan) {
                    $loan->current_step = 'aadharverification';
                    $loan->next_step = 'verifyotp';
                    $loan->save();

                    Log::info("Loan step updated for Application ID: {$applicationId}");
                }


                $session_id = $data['model']['transactionId'] ?? null;

                if (!$session_id) {
                    throw new \Exception('Session ID is missing in the API response');
                }

                return response()->json([
                    'status' => 'success',
                    'message' => ['success' => ['Digilocaker Session Created']],
                    'data' => [
                        'session_id' => $session_id,
                        'authorization_url' => $data['model']['kycUrl']
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Aadhaar OTP request error for Application ID {$applicationId}: " . $e->getMessage());
            return response()->json(['status' => $e->getMessage(), 'message' => ['error' => ['Failed to request Aadhaar OTP']]]);
        }
    }

    public function verifyAadharOtp(Request $request)
    {
        $this->validate($request, [
            'loan_application_id' => 'required|exists:loan_applications,id',
            'reference_id' => 'required|string'
        ]);

        $applicationId = $request->loan_application_id;

        Log::info("[$applicationId] Aadhaar OTP verification initiated.");

        try {

            $vendorData = VendorSetting::where('kyc','1')->first();

            if($vendorData['vendor'] == 'Sandbox'){
                $method = "POST";
                $path   = "/kyc/digilocker/sessions/{$request->reference_id}/status";
                $path2  = "/kyc/digilocker/sessions/{$request->reference_id}/documents/aadhaar";
                $host   = "api.sandbox.co.in";
                $timestamp = gmdate('Y-m-d\TH:i:s\Z');
                //echo $this->accessToken;
                // 4. Call API
                $response = $this->client->get("https://{$host}{$path}", [
                    'headers' => [
                        'authorization' => $this->accessToken,
                        'accept'        => 'application/json',
                        'x-api-key'     => $this->apiKey,
                    ],
                ]);

                $data = json_decode($response->getBody(), true);
                Log::info("API 1 Data:", $data);
                $loanApplicationId = $request->loan_application_id;
                DB::table('user_kyc_verifications')->updateOrInsert(
                    ['loan_application_id' => $loanApplicationId],  
                    [
                        'verifyAadharOtpData' => json_encode($data),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                if (isset($data["data"]['status']) && $data["data"]['status'] == "succeeded") {

                    $response2 = $this->client->get("https://{$host}{$path2}", [
                        'headers' => [
                            'authorization' => $this->accessToken,
                            'accept'        => 'application/json',
                            'x-api-key'     => $this->apiKey,
                        ],
                    ]);

                    $data2 = json_decode($response2->getBody(), true);
                    Log::info("API 2 Data:", $data2);
                    LoanKYCDetails::where('loan_application_id', $applicationId)->update(['aadhar_otp_verified' => 1]);

                    $loan = LoanApplication::where('user_id', auth()->id())->first();
                    if ($loan) {
                        $loan->current_step = 'verifyotp';
                        $loan->next_step = 'submitselfie';
                        $loan->save();

                        Log::info("[$applicationId] Loan application steps updated to submitselfie.");
                    }

                    Log::info("[$applicationId] Aadhaar OTP marked as verified in DB.");

                    $loanDocument1 = LoanKYCDetails::where('loan_application_id', $applicationId)->first();

                    $aadhaarData = $data2['data'];
                    $addressData = $aadhaarData['address'];

                    $exists = DB::table('aadhaar_data')->where('user_id', auth()->id())->exists();

                    if (!$exists) {
                        DB::table('aadhaar_data')->insert([
                            'user_id' => auth()->id(),
                            'aadhaar_number' => $loanDocument1->aadhar_number,
                            'reference_id' => $aadhaarData['reference_id'],
                            'status' => $aadhaarData['status'],
                            'message' => $aadhaarData['message'] ?? null,
                            'care_of' => $aadhaarData['care_of'] ?? null,
                            'full_address' => $aadhaarData['full_address'] ?? null,
                        'date_of_birth' => isset($aadhaarData['date_of_birth']) && !empty($aadhaarData['date_of_birth'])
                                                ? Carbon::createFromFormat('d-m-Y', $aadhaarData['date_of_birth'])->format('Y-m-d')
                                                : null,
                            'email_hash' => $aadhaarData['email_hash'] ?? null,
                            'gender' => $aadhaarData['gender'] ?? null,
                            'name' => $aadhaarData['name'] ?? null,
                            'country' => $addressData['country'] ?? null,
                            'district' => $addressData['district'] ?? null,
                            'house' => $addressData['house'] ?? null,
                            'landmark' => $addressData['landmark'] ?? null,
                            'pincode' => $addressData['pincode'] ?? null,
                            'post_office' => $addressData['post_office'] ?? null,
                            'state' => $addressData['state'] ?? null,
                            'street' => $addressData['street'] ?? null,
                            'subdistrict' => $addressData['subdistrict'] ?? null,
                            'vtc' => $addressData['vtc'] ?? null,
                            'year_of_birth' => $aadhaarData['year_of_birth'] ?? null,
                            'mobile_hash' => $aadhaarData['mobile_hash'] ?? null,
                            'photo' => $aadhaarData['photo'] ?? null,
                            'share_code' => $aadhaarData['share_code'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        Log::info("[$applicationId] Aadhaar data inserted into DB.");
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => ['success' => ['Aadhaar OTP Verified']],
                        'data' => [
                            'kyc_data' => $data2['data'] ?? null
                        ]
                    ]);
                } else {
                    Log::warning("[$applicationId] OTP verification failed or returned INVALID status.");
                }

                return response()->json([
                    'status' => 'error',
                    'message' => ['error' => ['Invalid Aadhaar OTP']],
                    'data' => [
                        'kyc_data' => $data['data'] ?? null
                    ]
                ]);
            }

            if($vendorData['vendor'] == 'Digitap'){
                $payload = [
                    "transactionId" => (string)$request->reference_id
                ];

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://svcdemo.digitap.work/ent/v1/kyc/get-digilocker-details',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS =>json_encode($payload),
                    CURLOPT_HTTPHEADER => array(
                        'content-type: application/json',
                        'ent_authorization:' . base64_encode(config('services.digitap.client_id') . ':' . config('services.digitap.client_secret')),
                    ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);
                $data = json_decode($response, true);

                $loanApplicationId = $request->loan_application_id;
                DB::table('user_kyc_verifications')->updateOrInsert(
                    ['loan_application_id' => $loanApplicationId],  
                    [
                        'verifyAadharOtpData' => json_encode($data),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                if (isset($data["code"]) && $data["code"] == "200") {

                    LoanKYCDetails::where('loan_application_id', $applicationId)->update(['aadhar_otp_verified' => 1]);

                    $loan = LoanApplication::where('id', $loanApplicationId)->first();
                    if ($loan) {
                        $loan->current_step = 'verifyotp';
                        $loan->next_step = 'submitselfie';
                        $loan->save();

                        Log::info("[$applicationId] Loan application steps updated to submitselfie.");
                    }

                    Log::info("[$applicationId] Aadhaar OTP marked as verified in DB.");

                    $loanDocument1 = LoanKYCDetails::where('loan_application_id', $applicationId)->first();

                    $aadhaarData = $data['model'];
                    $addressData = $aadhaarData['address'];

                    $exists = DB::table('aadhaar_data')->where('user_id', auth()->id())->exists();

                    if (!$exists) {
                        DB::table('aadhaar_data')->insert([
                            'user_id' => auth()->id(),
                            'aadhaar_number' => $loanDocument1->aadhar_number,
                            'reference_id' => $request->reference_id,
                            'status' => $data['code'],
                            'message' => $addressData['status'] ?? null,
                            'care_of' => $addressData['careOf'] ?? null,
                            'full_address' => $addressData['full_address'] ?? null,
                        'date_of_birth' => isset($addressData['dob']) && !empty($addressData['dob'])
                                                ? Carbon::createFromFormat('d-m-Y', $addressData['dob'])->format('Y-m-d')
                                                : null,
                            'email_hash' => $aadhaarData['maskedAdharNumber'] ?? null,
                            'gender' => $aadhaarData['gender'] ?? null,
                            'name' => $aadhaarData['name'] ?? null,
                            'country' => $addressData['country'] ?? null,
                            'district' => $addressData['district'] ?? null,
                            'house' => $addressData['house'] ?? null,
                            'landmark' => $addressData['landmark'] ?? null,
                            'pincode' => $addressData['pc'] ?? null,
                            'post_office' => $addressData['po'] ?? null,
                            'state' => $addressData['state'] ?? null,
                            'street' => $addressData['street'] ?? null,
                            'subdistrict' => $addressData['subdist'] ?? null,
                            'vtc' => $addressData['vtc'] ?? null,
                            'year_of_birth' => $aadhaarData['year_of_birth'] ?? null,
                            'mobile_hash' => $aadhaarData['mobile_hash'] ?? null,
                            'photo' => $aadhaarData['image'] ?? null,
                            'share_code' => $aadhaarData['share_code'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        Log::info("[$applicationId] Aadhaar data inserted into DB.");
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => ['success' => ['Aadhaar OTP Verified']],
                        'data' => [
                            'kyc_data' => $data['model'] ?? null
                        ]
                    ]);
                } else {
                    Log::warning("[$applicationId] OTP verification failed or returned INVALID status.");
                }

                return response()->json([
                    'status' => 'error',
                    'message' => ['error' => ['Invalid Aadhaar OTP']],
                    'data' => [
                        'kyc_data' => $data['model'] ?? null
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Log::error("[$applicationId] Aadhaar OTP verification error: " . $e->getMessage());
            return response()->json([
                'status' => $e->getMessage(),
                'message' => ['error' => ['Failed to verify Aadhaar OTP']]
            ]);
        }
    }


    public function verifyPan(Request $request)
    {
        $this->validate($request, [
            'loan_application_id' => 'required|exists:loan_applications,id',
            'pan_number' => 'required|string',
            'name' => 'required|string',
            'dob' => 'required|string'
        ]);

        try {
            $existingRecord = LoanKYCDetails::where('pan_number', $request->pan_number)->first();

            if ($existingRecord) {
                return response()->json([
                    'status' => 'error',
                    'message' => ['error' => ['This PAN number is already registered.']]
                ], 400);
            }

            $vendorData = VendorSetting::where('kyc','1')->first();

            if($vendorData['vendor'] == 'Sandbox'){

                $response = $this->client->post('https://api.sandbox.co.in/kyc/pan/verify', [
                    'headers' => [
                        'Authorization' => $this->accessToken,
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                        'x-api-key' => $this->apiKey,
                        'x-api-version' => '1.0',
                    ],
                    'json' => [
                        '@entity' => 'in.co.sandbox.kyc.pan_verification.request',
                        'pan' => $request->pan_number,
                        'consent' => 'Y',
                        'reason' => 'For KYC of User',
                        'name_as_per_pan' => $request->name,
                        'date_of_birth' => $request->dob
                    ]
                ]);

                $data = json_decode($response->getBody(), true);
                $loanApplicationId = $request->loan_application_id;
                DB::table('user_kyc_verifications')->updateOrInsert(
                    ['loan_application_id' => $loanApplicationId], 
                    [
                        'verifyPanData' => json_encode($data),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );


                if(isset($data['data']) && ($data['data']['status'] == 'valid') && ($data['data']['name_as_per_pan_match'] == true) && ($data['data']['date_of_birth_match'] == true)){
                    $loanDocument = LoanKYCDetails::updateOrCreate(
                        ['loan_application_id' => $request->loan_application_id],
                        ['pan_number' => $request->pan_number]
                    );

                    $nameParts = explode(" ", $request->name);
                    $userUpdate = User::where('id', auth()->id())->update([
                        'firstname' => $nameParts[0],
                        'lastname' => implode(" ", array_slice($nameParts, 1)),
                        'username' => strtolower(str_replace(" ", "", $request->name)),
                    ]);


                    $exists = DB::table('pan_data')->where('user_id', auth()->id())->exists();

                    if (!$exists) {
                        DB::table('pan_data')->insert([
                            'user_id' => auth()->id(),
                            'pan' => $data['data']['pan'],
                            'full_name' => $request->name,
                            'date_of_birth' => Carbon::createFromFormat('d/m/Y', $request->dob)->format('Y-m-d'),
                            'status' => $data['data']['status'],
                            'remarks' => $data['data']['remarks'],
                            'name_as_per_pan_match' => $data['data']['name_as_per_pan_match'],
                            'date_of_birth_match' => $data['data']['date_of_birth_match'],
                            'category' => $data['data']['category'],
                            'aadhaar_seeding_status' => $data['data']['aadhaar_seeding_status'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }else{
                    Log::error('PAN verification response error : ' . $data['data']);
                    return response()->json(['status' => 'error', 'message' => ['error' => [$data['data']]]], );
                }

                $loan = LoanApplication::where('id', $request->loan_application_id)->first();
                if ($loan) {
                    $loan->current_step = 'completekyc';
                    $loan->next_step = 'aadharverification';
                    $loan->save();
                }

                return response()->json([
                    'status' => 'success',
                    'pan_data' => $data['data'] ?? null
                ]);
            }

            if($vendorData['vendor'] == 'Digitap'){

                $payload = [
                    "client_ref_num" => (string)$request->loan_application_id,
                    "pan"            => (string)$request->pan_number,
                    "name"           => (string)$request->name,
                    "dob"            => (string)$request->dob,
                ];

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://svcdemo.digitap.work/validation/kyc/v2/pan_basic',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS =>json_encode($payload),
                    CURLOPT_HTTPHEADER => array(
                        'content-type: application/json',
                        'Authorization: Basic ' . base64_encode(config('services.digitap.client_id') . ':' . config('services.digitap.client_secret')),
                    ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);
                $data = json_decode($response, true);

                $loanApplicationId = $request->loan_application_id;
                DB::table('user_kyc_verifications')->updateOrInsert(
                    ['loan_application_id' => $loanApplicationId], 
                    [
                        'verifyPanData' => json_encode($data),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );


                if(isset($data['result']) && ($data['http_response_code'] == 200) && ($data['result']['name'] == "Y") && ($data['result']['dob'] == "Y")){
                    $loanDocument = LoanKYCDetails::updateOrCreate(
                        ['loan_application_id' => $request->loan_application_id],
                        ['pan_number' => $request->pan_number]
                    );

                    $nameParts = explode(" ", $request->name);
                    $userUpdate = User::where('id', auth()->id())->update([
                        'firstname' => $nameParts[0],
                        'lastname' => implode(" ", array_slice($nameParts, 1)),
                        'username' => strtolower(str_replace(" ", "", $request->name)),
                    ]);


                    $exists = DB::table('pan_data')->where('user_id', auth()->id())->exists();

                    if (!$exists) {
                        DB::table('pan_data')->insert([
                            'user_id' => auth()->id(),
                            'pan' => $data['result']['pan'],
                            'full_name' => $request->name,
                            'date_of_birth' => Carbon::createFromFormat('d/m/Y', $request->dob)->format('Y-m-d'),
                            'status' => $data['result']['status'],
                            'remarks' => $data['http_response_code'],
                            'name_as_per_pan_match' => true,
                            'date_of_birth_match' => true,
                            'category' => $data['request_id'],
                            'aadhaar_seeding_status' => $data['result']['seeding_status'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }else{
                    Log::error('PAN verification response error : ' . json_encode($data['result']));
                    return response()->json(['status' => 'error', 'message' => ['error' => 'Name Not Matched']], );
                }

                $loan = LoanApplication::where('id', $request->loan_application_id)->first();
                if ($loan) {
                    $loan->current_step = 'completekyc';
                    $loan->next_step = 'aadharverification';
                    $loan->save();
                }

                return response()->json([
                    'status' => 'success',
                    'pan_data' => $data['result'] ?? null
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('PAN verification error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => ['error' => [$e->getMessage()]]], );
        }
    }
}
