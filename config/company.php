<?php

return [

    'legal_name' => env('COMPANY_LEGAL_NAME', 'Paryavaran Mitra'),

    'address' => env('COMPANY_ADDRESS'),

    'phone' => env('COMPANY_PHONE'),

    'email' => env('COMPANY_EMAIL'),

    /*
    | A GSTIN is printed only when the office sets one here.
    | The application does not supply a sample tax number.
    */
    'gstin' => env('COMPANY_GSTIN'),

];
