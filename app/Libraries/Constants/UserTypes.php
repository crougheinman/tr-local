<?php namespace App\Libraries\Constants;

class UserTypes
{
    const USER_TYPE_NAMES = [
        1 => 'Agent',
        2 => 'Organizations / Groups / Companies',
        3 => 'Passenger'
    ];
    const USER_TYPES = [
        'AGENT' => 1,
        'GROUPS' => 2,
        'COMPANIES' => 2,
        'ORGANIZATIONS' => 2,
        'ORGANIZATION' => 2,
        'COMPANY' => 2,
        'GROUP' => 2,
        'PASSENGER' => 3
    ];
}