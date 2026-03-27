<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\DigitapBankRequest;
use App\Services\DigitapBankStatementService;

class ProcessDigitapReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public $requestId) {}

    public $tries = 3;
    public $timeout = 180;

    public function handle()
    {
        try {
            \Log::info("Digitap Job Started", ['requestId' => $this->requestId]);

            $request = DigitapBankRequest::where('request_id', $this->requestId)->first();

            if (!$request) {
                \Log::error("Digitap Request not found", ['requestId' => $this->requestId]);
                return;
            }

            if (in_array($request->status, ['xlsx_report_saved', 'json_report_saved'])) {
                \Log::info("Already processed", ['requestId' => $this->requestId]);
                return;
            }

            $digitap = new \App\Services\DigitapBankStatementService();
            $digitap->retrieveReport($request);

        } catch (\Exception $e) {
            \Log::error("Digitap Job Failed", [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            throw $e;
        }
    }
}
