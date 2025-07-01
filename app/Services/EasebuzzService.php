<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class EasebuzzService
{
    protected $client;
    protected $apiKey;
    protected $apiUrl;
    protected $salt;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('EASEBUZZ_API_KEY'); // Store keys in the .env file for security
        $this->apiUrl = 'https://wire.easebuzz.in/api/v1/';
        $this->salt = env('EASEBUZZ_API_SALT');
    }

    public function createContact($name, $email, $phone)
{
    try {
        // Remove '+91' if it exists at the start of the phone number
        if (strpos($phone, '+91') === 0) {
            $phone = substr($phone, 3);  // Remove the first three characters
        }

        $authorization = $this->generateHash([$this->apiKey, $name, $email, $phone]);

        // Log the parameters being sent to the API after modification
        Log::info('Sending to Easebuzz API - createContact', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ]);

        $response = $this->client->post($this->apiUrl . 'contacts/', [
            'headers' => [
                'WIRE-API-KEY' => $this->apiKey,
                'Authorization' => $authorization,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'key' => $this->apiKey,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
            ],
        ]);

        // Log the full response for debugging purposes
        $responseBody = json_decode($response->getBody(), true);
        Log::info('Easebuzz createContact response: ', $responseBody);

        return $responseBody;
    } catch (\Exception $e) {
        // Log the error message
        Log::error('Error creating contact: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Contact creation failed'];
    }
}


public function addBeneficiary($contactId, $beneficiaryName, $accountNumber, $ifsc)
{
    try {
        $authorization = $this->generateHash([$this->apiKey, $contactId, $beneficiaryName, $accountNumber, $ifsc, '']);

        // Log the data being sent to Easebuzz before making the request
        Log::info('Sending to Easebuzz API - addBeneficiary', [
            'contact_id' => $contactId,
            'beneficiary_name' => $beneficiaryName,
            'account_number' => $accountNumber,
            'ifsc' => $ifsc,

        ]);

        $response = $this->client->post($this->apiUrl . 'beneficiaries/', [
            'headers' => [
                'WIRE-API-KEY' => $this->apiKey,
                'Authorization' => $authorization,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'key' => $this->apiKey,
                'contact_id' => $contactId,
                'beneficiary_type' => 'bank_account',
                'beneficiary_name' => $beneficiaryName,
                'account_number' => $accountNumber,
                'ifsc' => $ifsc,
            ],
        ]);

        $responseBody = json_decode($response->getBody(), true);

        // Log the response from Easebuzz
        Log::info('Easebuzz addBeneficiary response: ', $responseBody);

        return $responseBody;
    } catch (\Exception $e) {
        // Log detailed error message
        Log::error('Error adding beneficiary: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Beneficiary addition failed'];
    }
}


// Initiate Transfer
public function initiateTransfer($email, $phone, $uniqueRequestNumber, $beneficiaryName, $accountNumber, $ifsc, $upiHandle, $amount, $paymentMode, $extraFields = [])
{
    try {

        // Ensure uniqueRequestNumber is a string
        $uniqueRequestNumberString = (string) $uniqueRequestNumber;


        $authorization = $this->generateHash([
            $this->apiKey, $accountNumber, $ifsc, $upiHandle, $uniqueRequestNumber, $amount
        ]);

        // Add extra fields for detailed logging
        $transferData = array_merge([
            'key' => $this->apiKey,
            'beneficiary_type' => $paymentMode === 'UPI' ? 'upi' : 'bank_account',
            'beneficiary_name' => $beneficiaryName,
            'account_number' => $accountNumber,
            'ifsc' => $ifsc,
            'upi_handle' => $upiHandle ?: '', // Default to empty string if null
            'unique_request_number' => $uniqueRequestNumber,
            'payment_mode' => $paymentMode,
            'amount' => $amount,
        ], $extraFields);  // Merging extra fields

        // Log all fields being sent for debugging
        Log::info('Initiating transfer with the following data:', $transferData);

        $response = $this->client->post($this->apiUrl . 'quick_transfers/initiate/', [
            'headers' => [
                'WIRE-API-KEY' => $this->apiKey,
                'Authorization' => $authorization,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => $transferData,  // Sending all transfer data including extra fields
        ]);

        $responseBody = json_decode($response->getBody(), true);
        Log::info('Easebuzz initiateTransfer response:', $responseBody);

        return $responseBody;
    } catch (\Exception $e) {
        Log::error('Error initiating transfer: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Transfer initiation failed'];
    }
}



    private function generateHash(array $params)
    {
        $hashString = implode('|', $params) . '|' . $this->salt;
        return hash('sha512', $hashString);
    }
}
