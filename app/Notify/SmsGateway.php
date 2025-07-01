<?php

namespace App\Notify;

use App\Lib\CurlRequest;
use MessageBird\Client as MessageBirdClient;
use MessageBird\Objects\Message;
use Textmagic\Services\TextmagicRestClient;
use Twilio\Rest\Client;
use Vonage\Client as NexmoClient;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

class SmsGateway{

    /**
     * the number where the sms will send
     *
     * @var string
     */
    public $to;

    /**
     * the name where from the sms will send
     *
     * @var string
     */
    public $from;


    /**
     * the message which will be send
     *
     * @var string
     */
    public $message;


    /**
     * the configuration of sms gateway
     *
     * @var object
     */
    public $config;

	public function clickatell()
	{
		$message = urlencode($this->message);
		$api_key = $this->config->clickatell->api_key;
		@file_get_contents("https://platform.clickatell.com/messages/http/send?apiKey=$api_key&to=$this->to&content=$message");
	}

	public function infobip(){
		$message = urlencode($this->message);
		@file_get_contents("https://api.infobip.com/api/v3/sendsms/plain?user=".$this->config->infobip->username."&password=".$this->config->infobip->password."&sender=$this->from&SMSText=$message&GSM=$this->to&type=longSMS");
	}

	public function messageBird(){
		$MessageBird = new MessageBirdClient($this->config->message_bird->api_key);
	  	$Message = new Message();
	  	$Message->originator = $this->from;
	  	$Message->recipients = array($this->to);
	  	$Message->body = $this->message;
	  	$MessageBird->messages->create($Message);
	}

	public function nexmo(){
		$basic  = new Basic($this->config->nexmo->api_key, $this->config->nexmo->api_secret);
		$client = new NexmoClient($basic);
		$response = $client->sms()->send(
		    new SMS($this->to, $this->from, $this->message)
		);
		 $response->current();
	}

	public function smsBroadcast(){
		$message = urlencode($this->message);
		@file_get_contents("https://api.smsbroadcast.com.au/api-adv.php?username=".$this->config->sms_broadcast->username."&password=".$this->config->sms_broadcast->password."&to=$this->to&from=$this->fromName&message=$message&ref=112233&maxsplit=5&delay=15");
	}

	public function twilio(){
		$account_sid = $this->config->twilio->account_sid;
		$auth_token = $this->config->twilio->auth_token;
		$twilio_number = $this->config->twilio->from;

		$client = new Client($account_sid, $auth_token);
		$client->messages->create(
		    '+'.$this->to,
		    array(
		        'from' => $twilio_number,
		        'body' => $this->message
		    )
		);
	}

	public function textMagic(){
        $client = new TextmagicRestClient($this->config->text_magic->username, $this->config->text_magic->apiv2_key);
        $client->messages->create(
            array(
                'text' => $this->message,
                'phones' => $this->to
            )
        );
	}

	// public function custom() {
	// 	\Log::info('Custom SMS function started.');

	// 	$credential = $this->config->custom;
	// 	$method = $credential->method;

	// 	// Extract the OTP code from the message
	// 	preg_match('/\b\d{4,6}\b/', $this->message, $matches);

	// 	$otp = $matches[0] ?? ''; // This will be '3213' in your example

	// 	// Remove the country code from the phone number if it starts with '91'
	// 	$phoneNumber = preg_replace('/^91/', '', $this->to);

	// 	\Log::info("OTP: {$otp}");
	// 	\Log::info("Phone Number: {$phoneNumber}");

	// 	// Shortcodes with actual OTP and modified phone number
	// 	$shortCodes = [
	// 		'{{message}}' => $otp,
	// 		'{{number}}'  => $phoneNumber,
	// 	];

	// 	// Replace the placeholders in the URL with actual values
	// 	$url = $credential->url;
	// 	foreach ($shortCodes as $placeholder => $actualValue) {
	// 		$url = str_replace($placeholder, urlencode($actualValue), $url);
	// 	}

	// 	\Log::info("Final GET URL: {$url}");

	// 	$header = array_combine($credential->headers->name, $credential->headers->value);

	// 	\Log::info("Headers: ", $header);

	// 	// Execute the GET request
	// 	$response = CurlRequest::curlContent($url, $header);
	// 	\Log::info("Response from GET request: ", (array)$response);

	// 	\Log::info('Custom SMS function completed.');


	// }
	public function custom() {
		\Log::info('Custom SMS function started.');
		$client = new GuzzleClient();

		preg_match('/\b\d{4,6}\b/', $this->message, $matches);
		$otp = $matches[0] ?? '';
		$phoneNumber = ltrim($this->to, '+');

		$data = [
			"template_id" => env('MSG91_TEMPLATE_ID'),
			"short_url" => "0",
			"recipients" => [
				[
					"mobiles" => $phoneNumber,
					"var1" => $otp,
				],
			],
		];

		$url = env('MSG91_BASE_URL');
		$authKey = env('MSG91_AUTH_KEY');

		try {
			$response = $client->post($url, [
				'headers' => [
					'accept' => 'application/json',
					'authkey' => $authKey,
					'content-type' => 'application/json'
				],
				'body' => json_encode($data)
			]);

			$response = json_decode($response->getBody(), true);
			\Log::info("Response from POST request: ", (array)$response);
			return $response;
		} catch (GuzzleException $e) {
			\Log::error("Error during POST request: " . $e->getMessage());
			return null;
		}
	}







}
