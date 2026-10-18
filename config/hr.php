<?php

return [
    /*
    | Company timezone used for business-day logic (attendance date, lateness
    | against shift times, day closing). Storage stays in the app timezone (UTC).
    */
    'timezone' => env('HR_TIMEZONE', 'UTC'),
];
