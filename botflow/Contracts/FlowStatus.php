<?php

namespace Botflow\Contracts;

use Botflow\Enums\EnumToArray;

enum FlowStatus: string
{
    use EnumToArray;

    case QUEUED = 'queued';

    case ACTIVE = 'active';

    case OK = 'ok';

    case CANCELLED = 'cancelled';

    case INTERRUPTED = 'interrupted';
}
