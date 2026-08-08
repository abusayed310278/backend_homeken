<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$acc = Botble\RealEstate\Models\Account::first();
echo json_encode($acc->avatar_url);
