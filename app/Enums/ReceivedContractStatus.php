<?php

namespace App\Enums;

enum ReceivedContractStatus: string
{
    case Pending = 'pending';
    case Finish = 'finish';
}
