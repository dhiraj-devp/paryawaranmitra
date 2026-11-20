<?php

return [

    /*
    | When this flag is on, administrator sign-in is refused. Enrollment is not
    | part of this phase, so enabling the flag must not look like two-factor
    | authentication is protecting the account.
    */
    'admin_two_factor' => filter_var(env('ADMIN_TWO_FACTOR', false), FILTER_VALIDATE_BOOL),

    'login_max_attempts' => 5,

    'login_ip_max_attempts' => 30,

    'login_decay_seconds' => 60,

    'documents_disk' => 'documents',

];
