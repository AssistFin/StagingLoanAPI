<?php

namespace App\Services;

use App\Models\Lead;
use Carbon\Carbon;

class LeadService
{
    public function getLoanApplicationStatus($id)
    {
        $lead = Lead::findOrFail($id);

        return [
            'application_received' => [
                'status' => $lead->created_at ? true : false,
                'message' => 'Your documents have been received & will be picked up for verification in about 24 hrs.',
                'timestamp' => $this->formatTimestamp($lead->created_at),
            ],
            'application_under_verification' => [
                'status' => $this->isUnderVerification($lead),
                'message' => $lead->status === 'pending' ? 'Your application is under verification.' : 'Verification is complete.',
                'timestamp' => $this->formatTimestamp($lead->updated_at),
            ],
            'application_status' => [
                'status' => $this->isApplicationFinalized($lead),
                'message' => $this->getApplicationStatusMessage($lead),
                'timestamp' => $this->formatTimestamp($lead->updated_at),
            ],
            'auto_debit_registration' => [
                'status' => $lead->status === 'approved',
                'message' => $lead->status === 'approved' ? 'Auto debit registration and e-sign completed.' : 'Pending registration and e-sign.',
                'timestamp' => $this->formatTimestamp($lead->updated_at),
            ],
            'loan_transfer' => [
                'status' => $lead->status === 'disbursed',
                'message' => $lead->status === 'disbursed' ? 'Loan amount has been transferred.' : 'Awaiting loan transfer.',
                'timestamp' => $this->formatTimestamp($lead->updated_at),
            ],
        ];
    }

    private function formatTimestamp($timestamp)
    {
        return $timestamp ? Carbon::parse($timestamp)->format('d M | h:i A') : null;
    }

    private function isUnderVerification(Lead $lead)
    {
        return $lead->status === 'pending' || $lead->status === 'locked';
    }

    private function isApplicationFinalized(Lead $lead)
    {
        return in_array($lead->status, ['approved', 'rejected']);
    }

    private function getApplicationStatusMessage(Lead $lead)
    {
        return $lead->status === 'approved' ? 'Your application has been approved.' : 'Your application has been rejected.';
    }
}
