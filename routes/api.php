<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/add-subscriber', function (Request $request) {
    $params = [
        '--email' => $request->input('email'),
        '--marketing-consent' => $request->input('marketingConsent'),
    ];
    if ($request->input('firstName')) $params['--first-name'] = $request->input('firstName');
    if ($request->input('lastName')) $params['--last-name'] = $request->input('lastName');
    if ($request->input('dob')) $params['--dob'] = $request->input('dob');
    if ($request->input('lists')) $params['--lists'] = $request->input('lists');

    ob_start();
    Artisan::call('crm:add-subscriber', $params);
    $output = ob_get_clean();
    return ['message' => $output];
});

Route::post('/send-enquiry', function (Request $request) {
    $params = [
        '--subscriber-id' => $request->input('subscriberId'),
        '--message' => $request->input('message'),
    ];

    ob_start();
    Artisan::call('crm:send-enquiry', $params);
    $output = ob_get_clean();
    return ['message' => $output];
});
