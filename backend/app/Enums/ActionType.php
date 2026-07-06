<?php

declare(strict_types=1);

namespace App\Enums;

enum ActionType: string
{
    case START = 'START';
    case STOP = 'STOP';
}
