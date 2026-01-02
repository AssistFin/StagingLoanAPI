<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'cashfree' => [
        'base_url' => env('CASHFREE_BASE_URL', 'https://sandbox.cashfree.com'),
        'app_id' => env('CASHFREE_APP_ID'),
        'secret_key' => env('CASHFREE_SECRET_KEY'),
        'api_version' => env('CASHFREE_API_VERSION', '2022-01-01'),
        'webhook_secret' => env('CASHFREE_WEBHOOK_SECRET'),
        'app_url' => env('LOANONE_URL'),
        'return_url' => env('CASHFREE_RETURN_URL'),
    ],

    'sandbox' => [
        'api_key' => env('SANDBOX_API_KEY'),
        'api_secret' => env('SANDBOX_API_SECRET'),
    ],

    'experiancreditbureau' => [
        'ecb_url' => env('EXPERIAN_CREDIT_BUREAU_URL'),
        'ecb_user' => env('EXPERIAN_CREDIT_BUREAU_USER'),
        'ecb_password' => env('EXPERIAN_CREDIT_BUREAU_PASSWORD'),
    ],

    'scoremebsa' => [
        'smbsa_cid' => env('SCOREME_CLIENT_ID'),
        'smbsa_csec' => env('SCOREME_CLIENT_SECRET'),
        'smbsa_upload_doc_url' => env('SCOREME_UPLOAD_DOC_URL'),
        'smbsa_get_report_url' => env('SCOREME_GET_REPORT_URL'),
    ],

    'smtp' => [
        'host' => env('MAIL_HOST', 'smtp.office365.com'),
        'port' => env('MAIL_PORT', 587),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'address' => env('MAIL_FROM_ADDRESS', 'care@loanone.in'),
        'name' => env('MAIL_FROM_NAME', 'LoanOne'),
    ],

    'docs' => [
        'upload_kfs_doc' => env('UPLOAD_FILE'),
        'app_url' => env('APP_URL'),
    ],
    'equence' => [
        'send_sms_url' => env('EQUENCE_URL'),
        'send_sms_user' => env('EQUENCE_USERNAME'),
        'send_sms_pass' => env('EQUENCE_PASSWORD'),
        'send_sms_from' => env('EQUENCE_FROM'),
    ],
    'digitap' => [
        'base_url'      => env('DIGITAP_BASE_URL', 'https://digitap-uat-url.com'), // change to PROD when needed
        'client_id'     => env('DIGITAP_CLIENT_ID'),
        'client_secret' => env('DIGITAP_CLIENT_SECRET'),
        'dbsa_url' => env('DIGITAP_BSA_UPLOAD_DOC'),
        'db_url' => env('DIGITAP_URL'),
    ],


];
