<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Botble\RealEstate\Models\Package;
use Botble\RealEstate\Models\Transaction;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;
use Stripe\EphemeralKey;
use Botble\RealEstate\Models\Invoice;
use Botble\RealEstate\Models\Consult;
use Botble\Payment\Models\Payment;

class StripePaymentController extends Controller
{
    protected StripeClient $stripe;

    public function __construct()
    {
        // Use env if setting is empty
        $secretKey = setting('payment_stripe_secret') ?: env('STRIPE_SECRET');
        if ($secretKey) {
            $this->stripe = new StripeClient($secretKey);
        }
    }

    public function createPaymentIntent(Request $request)
    {
        if (!isset($this->stripe)) {
            return response()->json([
                'error' => true,
                'message' => 'Stripe is not configured on the server.',
            ], 500);
        }

        $user = $request->user();
        
        // Handle property booking
        if ($request->has('property_id') && $request->has('amount')) {
            $amount = (float)$request->input('amount');
            $amountInCents = intval(round($amount * 100));

            try {
                // Generate an ephemeral key (requires a customer id)
                $customer = $this->stripe->customers->create([
                    'email' => $user ? $user->email : 'guest@example.com',
                ]);

                $ephemeralKey = $this->stripe->ephemeralKeys->create(
                    ['customer' => $customer->id],
                    ['stripe_version' => '2022-11-15']
                );

                $paymentIntent = $this->stripe->paymentIntents->create([
                    'amount' => $amountInCents,
                    'currency' => 'usd',
                    'payment_method_types' => ['card'],
                    'customer' => $customer->id,
                    'metadata' => [
                        'property_id' => $request->input('property_id'),
                        'user_id' => $user ? $user->id : null,
                        'type' => 'property_booking'
                    ],
                    'receipt_email' => $user ? $user->email : null,
                ]);

                return response()->json([
                    'error' => false,
                    'data' => [
                        'client_secret' => $paymentIntent->client_secret,
                        'ephemeral_key' => $ephemeralKey->secret,
                        'customer_id' => $customer->id,
                        'payment_intent_id' => $paymentIntent->id,
                        'amount' => $amount,
                        'currency' => 'USD',
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('Stripe Booking Intent Error: ' . $e->getMessage());
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to create payment intent. ' . $e->getMessage(),
                ], 500);
            }
        }

        // Handle package purchase (original logic)
        $request->validate([
            'package_id' => 'required|exists:re_packages,id',
        ]);

        $package = Package::findOrFail($request->input('package_id'));

        // Check account limit
        if ($package->account_limit && $user->packages()->where('package_id', $package->id)->count() >= $package->account_limit) {
            return response()->json([
                'error' => true,
                'message' => 'You have reached the limit of purchasing this package.',
            ], 403);
        }

        $amount = $package->price;
        $amountInCents = intval(round($amount * 100));

        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amountInCents,
                'currency' => 'usd',
                    'payment_method_types' => ['card'],
                'metadata' => [
                    'package_id' => $package->id,
                    'user_id' => $user->id,
                    'type' => 'package_purchase'
                ],
                'receipt_email' => $user->email,
            ]);

            return response()->json([
                'error' => false,
                'data' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'publishable_key' => setting('payment_stripe_client_id') ?: env('STRIPE_KEY'),
                    'payment_intent_id' => $paymentIntent->id,
                    'amount' => $amount,
                    'currency' => 'USD',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Package Intent Error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Failed to create payment intent. ' . $e->getMessage(),
            ], 500);
        }
    }

    public function confirmPayment(Request $request)
    {
        // ... Original logic remains identical or we can just leave it since the app uses presentPaymentSheet which confirms internally ...
        // I will keep the original confirm logic for packages.
        
        if (!isset($this->stripe)) {
            return response()->json([
                'error' => true,
                'message' => 'Stripe is not configured on the server.',
            ], 500);
        }

        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        $user = $request->user();

        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($request->input('payment_intent_id'));

            if ($paymentIntent->status !== 'succeeded') {
                return response()->json([
                    'error' => true,
                    'message' => 'Payment has not succeeded yet. Status: ' . $paymentIntent->status,
                ], 400);
            }

            // Booking confirmation
            if (isset($paymentIntent->metadata->type) && $paymentIntent->metadata->type === 'property_booking') {
                $propertyId = $paymentIntent->metadata->property_id ?? null;
                
                // Create Payment
                $payment = \Botble\Payment\Models\Payment::create([
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => strtoupper($paymentIntent->currency),
                    'payment_channel' => 'stripe',
                    'status' => 'completed',
                    'charge_id' => $paymentIntent->id,
                    'payment_type' => 'confirm',
                    'customer_id' => $paymentIntent->customer,
                ]);

                // Create Invoice
                $invoice = Invoice::create([
                    'account_id' => $user->id,
                    'payment_id' => $payment->id,
                    'reference_id' => $propertyId,
                    'reference_type' => 'Botble\RealEstate\Models\Property',
                    'code' => 'INV-' . strtoupper(uniqid()),
                    'amount' => $paymentIntent->amount / 100,
                    'sub_total' => $paymentIntent->amount / 100,
                    'status' => 'completed',
                ]);

                // Create Consult (Reservation)
                if ($propertyId) {
                    Consult::create([
                        'name' => $user->name ?? 'Guest',
                        'email' => $user->email,
                        'property_id' => $propertyId,
                        'status' => 'read',
                        'content' => 'Booked via Stripe Payment',
                    ]);
                }

                return response()->json([
                    'error' => false,
                    'message' => 'Property booking payment confirmed and invoice generated successfully.',
                ]);
            }

            // Package confirmation
            $packageId = $paymentIntent->metadata->package_id ?? null;
            if (!$packageId) {
                return response()->json([
                    'error' => true,
                    'message' => 'Package ID not found in payment intent metadata.',
                ], 400);
            }

            $existingTransaction = Transaction::where('payment_id', $paymentIntent->id)->first();
            if ($existingTransaction) {
                return response()->json([
                    'error' => false,
                    'message' => 'Payment already processed.',
                    'data' => ['current_credits' => $user->credits]
                ]);
            }

            $package = Package::findOrFail($packageId);
            $user->credits += $package->number_of_listings;
            $user->save();
            $user->packages()->attach($package);

            Transaction::query()->create([
                'user_id' => 0,
                'account_id' => $user->id,
                'credits' => $package->number_of_listings,
                'payment_id' => $paymentIntent->id,
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Payment confirmed and package purchased successfully.',
                'data' => ['current_credits' => $user->credits]
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Payment Confirm Error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Failed to confirm payment. ' . $e->getMessage(),
            ], 500);
        }
    }
}
