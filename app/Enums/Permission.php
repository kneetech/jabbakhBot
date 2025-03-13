<?php

namespace App\Enums;

enum Permission: string
{
    case Cabinet = '/cabinet';

    case RequestChecks = '/requestChecks';

    case EmployeesRegistry = '/employees';

    case RegistrationsRegistry = '/registrations';

    case Feedback = '/feedback';
}
