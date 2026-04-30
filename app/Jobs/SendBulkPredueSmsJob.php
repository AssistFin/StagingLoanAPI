<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBulkPredueSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $users;

    /**
     * Create a new job instance.
     */
    public function __construct($users)
    {
        $this->users = $users;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        foreach ($this->users as $user) {

            $mobile = $user['mobile'];
            $dues = $user['dues'];

            if (empty($mobile)) continue;

            // Equence credentials
            $username = config('services.equence.send_sms_user');
            $password = config('services.equence.send_sms_pass');
            $senderId = config('services.equence.send_sms_from');
            $mobileWithCountryCode = $mobile;
            $loginUrl = 'https://loanone.in/';
            $text = "Dear User, Repayment of your loan emi of Rs. {$dues} with LoanOne is due for payment. Please pay before the due date to avoid penalties and maintain a good credit score. Pay now: {$loginUrl} Regards, LoanOne";

            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post(config('services.equence.send_sms_url'), [
                    'username' => $username,
                    'password' => $password,
                    'to' => $mobileWithCountryCode,
                    'from' => $senderId,
                    'text' => $text,
                ]);

                // ✅ RESPONSE BODY
                $responseBody = $response->body();

                // ✅ SUCCESS LOG
                \Log::channel('worker')->info(
                    "SMS API RESPONSE",
                    [
                        'mobile' => $mobile,
                        'status_code' => $response->status(),
                        'response' => $responseBody
                    ]
                );

                // ✅ FAILED RESPONSE
                if (!$response->successful()) {

                    \Log::channel('worker')->error(
                        "SMS sending failed",
                        [
                            'mobile' => $mobile,
                            'response' => $responseBody
                        ]
                    );
                }

            } catch (\Exception $e) {
                \Log::channel('worker')->error(
                    "SMS Exception",
                    [
                        'mobile' => $mobile ?? '',
                        'error' => $e->getMessage()
                    ]
                );
            }
        }
    }
}