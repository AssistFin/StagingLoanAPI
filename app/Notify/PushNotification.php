<?php

namespace App\Notify;

use App\Lib\CurlRequest;
use App\Models\UserNotification;
use App\Notify\Notifiable;
use App\Notify\NotifyProcess;

class PushNotification extends NotifyProcess implements Notifiable {

    public $deviceTokens;
    public $clickValue;
    /**
     * Assign value to properties
     *
     * @return void
     */
    public function __construct() {

        $this->statusField    = 'push_notification_status';
        $this->body           = 'push_notification_body';
        $this->globalTemplate = 'push_notification_body';
        $this->notifyConfig   = 'push_configuration';
    }

    /**
     * Send notification
     *
     * @return void|bool
     */
    public function redirectForApp($getTemplateName) {

        $screens = [
            'TRX_HISTORY'      => ['BAL_ADD', 'BAL_SUB'],
            'DEPOSIT_HISTORY'  => ['DEPOSIT_COMPLETE', 'DEPOSIT_APPROVE', 'DEPOSIT_REJECT', 'DEPOSIT_REQUEST'],
            'WITHDRAW_HISTORY' => ['WITHDRAW_APPROVE', 'WITHDRAW_REJECT', 'WITHDRAW_REQUEST'],
            'LOAN_LIST'        => ['LOAN_APPROVE', 'LOAN_REJECT', 'LOAN_PAID', 'LOAN_INSTALLMENT_DUE'],
            'HOME'             => ['KYC_REJECT', 'KYC_APPROVE'],
        ];

        foreach ($screens as $screen => $array) {
            if (in_array($getTemplateName, $array)) {
                return $screen;
            }
        }

        return 'HOME';
    }


    /**
     * Send notification
     *
     * @return void|bool
     */
    public function redirectForWeb($getTemplateName) {

        $templates = [
            'user.transactions'      => ['BAL_ADD', 'BAL_SUB'],
            'user.deposit.history'   => ['DEPOSIT_COMPLETE', 'DEPOSIT_APPROVE', 'DEPOSIT_REJECT', 'DEPOSIT_REQUEST'],
            'user.withdraw.history'  => ['WITHDRAW_APPROVE', 'WITHDRAW_REJECT', 'WITHDRAW_REQUEST'],
            'user.loan.list'         => ['LOAN_APPROVE', 'LOAN_REJECT', 'LOAN_PAID', 'LOAN_INSTALLMENT_DUE'],
        ];

        foreach ($templates as $template => $array) {
            if (in_array($getTemplateName, $array)) {
                return route($template);
            }
        }

        return route('user.home');
    }




    public function send() {
        // Get the message, subject, and template data
        $message    = $this->getMessage();
        $subject    = $this->subject;
        $remark     = $this->template->act;

        // Check if push notifications are enabled and if there's a message
        if ($this->setting->pn && $message) {

            try {

                if ($this->user) {
                    // Set notification priority and data for Android
                    $data['priority'] = 'high';

                    // Retrieve device tokens associated with the user
                    $data['registration_ids'] = $this->deviceTokens;

                    // Define the notification payload
                    $data['notification'] = [
                        'title' => $subject,
                        'body' => $message,
                        'icon' => getImage(getFilePath('logoIcon') . '/logo.png'),
                        'click_action' => $this->redirectForWeb($this->templateName),
                    ];

                    // Define custom data for the notification
                    $data['data'] = [
                        'for_app' => $this->redirectForApp($this->templateName),
                    ];

                    // Convert the data array to a JSON string
                    $dataString = json_encode($data);

                    // Set headers for the FCM request
                    $headers = [
                        'Authorization: key=' . $this->setting->push_configuration->serverKey,
                        'Content-Type: application/json',
                        'priority: high',
                    ];

                    // Send the FCM request using cURL
                    $result = CurlRequest::curlPostContent('https://fcm.googleapis.com/fcm/send', $dataString, $headers);

                    // Check for errors in the FCM response
                    if (@$result->results[0]->error) {
                        $this->createErrorLog('Push Notification Error: ' . $result->results[0]->error);
                        session()->flash('push_notification_error', $result->results[0]->error);
                    } else {
                        // Save the notification details to the database
                        $userNotification              = new UserNotification();
                        $userNotification->title       = $subject;
                        $userNotification->user_id     = $this->user->id;
                        $userNotification->remark      = $remark;
                        $userNotification->click_value = $this->redirectForWeb($this->templateName);
                        $userNotification->save();

                        $this->createLog('push_notification');
                    }
                }
            } catch (\Exception $e) {
                $this->createErrorLog('Push Notification Error: ' . $e->getMessage());
                session()->flash('push_notification_error', $e->getMessage());
            }
        }
    }

    /**
     * Configure some properties
     *
     * @return void
     */
    public function prevConfiguration() {
        if ($this->user) {
            $this->deviceTokens = $this->user->deviceTokens()->pluck('token')->toArray();
            $this->receiverName = $this->user->fullname;
        }
        $this->toAddress = $this->deviceTokens;
    }
}
