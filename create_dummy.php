<?php
$user = \Botble\RealEstate\Models\Account::find(16);
$property = \Botble\RealEstate\Models\Property::first();

if ($user && $property) {
    $payment = \Botble\Payment\Models\Payment::create([
        'amount' => 1500,
        'currency' => 'USD',
        'payment_channel' => 'stripe',
        'status' => 'completed',
        'charge_id' => 'dummy_charge',
        'payment_type' => 'confirm',
        'customer_id' => 'cus_dummy',
    ]);

    $invoice = \Botble\RealEstate\Models\Invoice::create([
        'account_id' => $user->id,
        'payment_id' => $payment->id,
        'reference_id' => $property->id,
        'reference_type' => 'Botble\RealEstate\Models\Property',
        'code' => 'INV-TEST-123',
        'amount' => 1500,
        'sub_total' => 1500,
        'status' => 'completed',
    ]);

    \Botble\RealEstate\Models\Consult::create([
        'name' => $user->name ?? 'User',
        'email' => $user->email ?? 'test@test.com',
        'property_id' => $property->id,
        'status' => 'read',
        'content' => 'Booked via Stripe Payment',
    ]);

    echo "Dummy invoice and consult created successfully!";
} else {
    echo "User or Property not found.";
}
