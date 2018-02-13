<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    echo '<h1> Voucher Generator <small>by Juliano Barros</small> </h1><br>';
    echo 'This micro-service app it is to generate vouchers for specific recipients and offers <br>';
    echo 'You can read all documentation on <a href="https://github.com/juliano-barros/vouchergenerator">Github documentation </a> <br><br>';
    return $router->app->version();
});

$router->post('/voucher/generate', 'VoucherController@voucherGenerate');

$router->patch('/voucher/use', 'VoucherController@voucherUse');

$router->get('/voucher/{email}', 'VoucherController@voucherRecipient');
