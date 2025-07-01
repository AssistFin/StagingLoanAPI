<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Models\LoanKYCDetails;
use App\Models\LoanApplication;
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
            'aadhar_number' => 'required|string',
            'loan_application_id' => 'required|exists:loan_applications,id',
        ]);

        $applicationId = $request->loan_application_id;
        Log::info("Starting Aadhaar OTP request for Application ID: {$applicationId}");

        try {
            $existingRecord = LoanKYCDetails::where([
                ['aadhar_number', $request->aadhar_number],
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

            $pandata = DB::table('pan_data')->where('user_id', auth()->id())->first();

            $response1 = $this->client->post('https://api.sandbox.co.in/kyc/pan-aadhaar/status', [
                'headers' => [
                    'Authorization' => $this->accessToken,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'x-api-key' => $this->apiKey,
                    'x-api-version' => '1.0',
                ],
                'json' => [
                    '@entity'=> 'in.co.sandbox.kyc.pan_aadhaar.status',
                    'pan'=> $pandata->pan,
                    'aadhaar_number'=> $request->aadhar_number,
                    'consent'=> 'Y',
                    'reason'=> 'For KYC of User'
                ]
            ]);

            $data1 = json_decode($response1->getBody(), true);  

            if (isset($data1["data"]['aadhaar_seeding_status']) && $data1["data"]['aadhaar_seeding_status'] == "y") { 
                    Log::info("Sending OTP request to external API for Application ID: {$applicationId}");

                $response = $this->client->post('https://api.sandbox.co.in/kyc/aadhaar/okyc/otp', [
                    'headers' => [
                        'Authorization' => $this->accessToken,
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                        'x-api-key' => $this->apiKey,
                        'x-api-version' => '1.0',
                    ],
                    'json' => [
                        '@entity' => 'in.co.sandbox.kyc.aadhaar.okyc.otp.request',
                        'consent' => 'y',
                        'reason' => 'for kyc',
                        'aadhaar_number' => $request->aadhar_number
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

                $loan = LoanApplication::where('user_id', auth()->id())->first();
                if ($loan) {
                    $loan->current_step = 'aadharverification';
                    $loan->next_step = 'verifyotp';
                    $loan->save();

                    Log::info("Loan step updated for Application ID: {$applicationId}");
                }

                $referenceId = $data['data']['reference_id'] ?? null;

                if (!$referenceId) {
                    throw new \Exception('Reference ID is missing in the API response');
                }

                return response()->json([
                    'status' => 'success',
                    'message' => ['success' => ['Aadhaar OTP Sent']],
                    'data' => [
                        'reference_id' => $referenceId
                    ]
                ]);
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => ['error' => [$data1["data"]['message']]],
                    'data' => [
                        'reference_id' => null
                    ]
                ]);
            }
            //EOC For check linked status
        } catch (\Exception $e) {
            Log::error("Aadhaar OTP request error for Application ID {$applicationId}: " . $e->getMessage());
            return response()->json(['status' => $e->getMessage(), 'message' => ['error' => ['Failed to request Aadhaar OTP']]]);
        }
    }

    public function verifyAadharOtp(Request $request)
    {
        $this->validate($request, [
            'loan_application_id' => 'required|exists:loan_applications,id',
            'otp' => 'required|string',
            'reference_id' => 'required|string'
        ]);

        $applicationId = $request->loan_application_id;

        Log::info("[$applicationId] Aadhaar OTP verification initiated.");

        try {
            $response = $this->client->post('https://api.sandbox.co.in/kyc/aadhaar/okyc/otp/verify', [
                'headers' => [
                    'Authorization' => $this->accessToken,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'x-api-key' => $this->apiKey,
                    'x-api-version' => '1.0',
                ],
                'json' => [
                    '@entity' => 'in.co.sandbox.kyc.aadhaar.okyc.request',
                    'otp' => $request->otp,
                    'reference_id' => $request->reference_id
                ]
            ]);

            Log::info("[$applicationId] OTP verification API response received.");

            $data = json_decode($response->getBody(), true);

            $loanApplicationId = $request->loan_application_id;
            DB::table('user_kyc_verifications')->updateOrInsert(
                ['loan_application_id' => $loanApplicationId],  
                [
                    'verifyAadharOtpData' => json_encode($data),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if (isset($data["data"]['status']) && $data["data"]['status'] == "VALID") {
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

                $aadhaarData = $data['data'];
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
                        'kyc_data' => $data['data'] ?? null
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


            if(isset($data['data'])) {
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
            }

            $loan = LoanApplication::where('user_id', auth()->id())->first();
            if ($loan) {
                $loan->current_step = 'completekyc';
                $loan->next_step = 'aadharverification';
                $loan->save();
            }

            return response()->json([
                'status' => 'success',
                'pan_data' => $data['data'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('PAN verification error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => ['error' => [$e->getMessage()]]], );
        }
    }
}
