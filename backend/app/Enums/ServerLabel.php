<?php

declare(strict_types=1);

namespace App\Enums;

enum ServerLabel: string
{
    case NONE = 'NONE';
    case DEVELOPMENT = 'DEVELOPMENT';
    case TEST = 'TEST';
    case PRODUCTION = 'PRODUCTION';
}
