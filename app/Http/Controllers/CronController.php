<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Models\Transaction;
use App\Lib\CurlRequest;
use App\Models\CronJob;
use App\Models\CronJobLog;
use Carbon\Carbon;
use App\Models\Installment;

class CronController extends Controller
{
    public function cron()
    {
        $general            = gs();
        $general->last_cron = now();
        $general->save();

        $crons = CronJob::with('schedule');
        if (request()->alias) {
            $crons->where('alias', request()->alias);
        } else {
            $crons->where('next_run', '<', now())->where('is_running', 1);
        }
        $crons = $crons->get();

        foreach ($crons as $cron) {
            $cronLog              = new CronJobLog();
            $cronLog->cron_job_id = $cron->id;
            $cronLog->start_at    = now();
            if ($cron->is_default) {
                $controller = new $cron->action[0];
                try {
                    $method = $cron->action[1];
                    $controller->$method();
                } catch (\Exception $e) {
                    $cronLog->error = $e->getMessage();
                }
            } else {
                try {
                    CurlRequest::curlContent($cron->url);
                } catch (\Exception $e) {
                    $cronLog->error = $e->getMessage();
                }
            }
            $cron->last_run = now();
            $cron->next_run = now()->addSeconds($cron->schedule->interval);
            $cron->save();

            $cronLog->end_at = $cron->last_run;

            $startTime         = Carbon::parse($cronLog->start_at);
            $endTime           = Carbon::parse($cronLog->end_at);
            $diffInSeconds     = $startTime->diffInSeconds($endTime);
            $cronLog->duration = $diffInSeconds;
            $cronLog->save();
        }
        if (request()->alias) {
            $notify[] = ['success', keyToTitle(request()->alias) . ' executed successfully'];
            return back()->withNotify($notify);
        }
    }

    public function loan()
    {
        try {
            $installments  = Installment::whereDate('installment_date', '<=', today())
                ->whereNull('given_at')->with('loan')
                ->whereHas('loan', function ($q) {
                    return $q->where('status', Status::LOAN_RUNNING);
                })->get();

            foreach ($installments as $installment) {
                $loan   = $installment->loan;
                $amount = $loan->per_installment;
                $user   = $loan->user;

                $shortCodes = $loan->shortCodes();

                if ($user->balance < $amount) {
                    $lastNotification = $loan->due_notification_sent ?? now()->subHours(10);


                    if ($lastNotification->diffInHours(now()) >= 10) {
                        // Notify user after 10 hours from the previous one.
                        $shortCodes['installment_date'] = showDateTime($installment->installment_date, 'd M, Y');
                        notify($user, 'LOAN_INSTALLMENT_DUE', $shortCodes);
                        $loan->due_notification_sent = now();
                        $loan->save();
                    }
                } else {

                    $delayedDays = $installment->installment_date->diffInDays(today());
                    $charge      = 0;

                    if ($delayedDays >= $loan->delay_value) {
                        $charge = $loan->charge_per_installment * $delayedDays;
                        $loan->delay_charge += $charge;
                    }

                    $loan->given_installment += 1;

                    if ($loan->given_installment == $loan->total_installment) {
                        $loan->status = Status::LOAN_PAID;
                        notify($user, 'LOAN_PAID', $shortCodes);
                    }

                    $loan->save();

                    $amount += $charge;

                    $user->balance -= $amount;
                    $user->save();

                    $installment->given_at     = now();
                    $installment->delay_charge = $charge;
                    $installment->save();

                    $transaction               = new Transaction();
                    $transaction->user_id      = $user->id;
                    $transaction->amount       = $amount;
                    $transaction->post_balance = $user->balance;
                    $transaction->charge       = $charge;
                    $transaction->trx_type     = '-';
                    $transaction->details      = 'Loan installment paid';
                    $transaction->remark       = 'loan_installment';
                    $transaction->trx          = $loan->loan_number;
                    $transaction->save();
                }
            }
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }
}
