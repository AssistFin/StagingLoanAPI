<?php

namespace App\Constants;

class Status
{

    const ENABLE  = 1;
    const DISABLE = 0;

    const YES = 1;
    const NO  = 0;

    const VERIFIED   = 1;
    const UNVERIFIED = 0;

    const PAYMENT_INITIATE = 0;
    const PAYMENT_SUCCESS  = 1;
    const PAYMENT_PENDING  = 2;
    const PAYMENT_REJECT   = 3;

    const TICKET_OPEN   = 0;
    const TICKET_ANSWER = 1;
    const TICKET_REPLY  = 2;
    const TICKET_CLOSE  = 3;

    const PRIORITY_LOW    = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH   = 3;

    const USER_ACTIVE = 1;
    const USER_BAN    = 0;

    const KYC_UNVERIFIED = 0;
    const KYC_PENDING    = 2;
    const KYC_VERIFIED   = 1;

    const LOAN_PENDING  = 0;
    const LOAN_RUNNING  = 1;
    const LOAN_PAID     = 2;
    const LOAN_REJECTED = 3;

    const DEPOSIT_SUCCESS = 1;
    const DEPOSIT_PENDING = 2;
    const DEPOSIT_CANCEL  = 3;

    const WITHDRAW_SUCCESS = 1;
    const WITHDRAW_PENDING = 2;
    const WITHDRAW_CANCEL  = 3;
}
