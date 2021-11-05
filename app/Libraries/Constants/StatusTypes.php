<?php namespace App\Libraries\Constants;

class StatusTypes
{
    const STATUS_TYPE_NAMES = [
        0 => 'NotActivated',
        1 => 'Activated',
        2 => 'Banned',
        3 => 'UnderDocumentProcessing',
        4 => 'UnderPaymentProcessing'
    ];
    const STATUS_TYPES = [
        'NOT_ACTIVATED' => 0,
        'ACTIVATED' => 1,
        'BANNED' => 2,
        'UNDER_DOCUMENT_PROCESSING' => 3,
        'UNDER_PAYMENT_PROCESSING' => 4
    ];
}