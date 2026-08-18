<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Botble\RealEstate\Models\Consult;
use Botble\RealEstate\Models\Invoice;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\Review;
use Botble\RealEstate\Models\Package;
use Botble\RealEstate\Models\Transaction;
use Illuminate\Http\Request;

class AccountDataController extends Controller
{
    public function getTrips(Request $request)
    {
        $user = $request->user();
        
        $consults = Consult::where('email', $user->email)
            ->whereNotNull('property_id')
            ->with(['property', 'property.city'])
            ->orderBy('created_at', 'desc')
            ->get();

        $trips = $consults->map(function ($consult) {
            $property = $consult->property;
            if (!$property) return null;
            return [
                'id' => $property->id,
                'name' => $property->name,
                'title' => $property->name,
                'location' => $property->city->name ?? $property->location,
                'date' => $consult->created_at->format('M d - M d'),
                'image_url' => $property->image,
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
                'created_at' => $consult->created_at,
            ];
        })->filter()->values();

        return response()->json([
            'error' => false,
            'success' => true,
            'data' => $trips,
        ]);
    }

    public function getProperties(Request $request)
    {
        $user = $request->user();
        $properties = Property::where('author_id', $user->id)
            ->where('author_type', 'Botble\RealEstate\Models\Account')
            ->with(['currency', 'city', 'state'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'error' => false,
            'data' => $properties,
        ]);
    }

    public function getConsults(Request $request)
    {
        $user = $request->user();
        
        // Consults where the user is the sender (by email),
        // or where the consult was made on one of the user's properties.
        $consults = Consult::where('email', $user->email)
            ->orWhereHas('property', function ($query) use ($user) {
                $query->where('author_id', $user->id)
                      ->where('author_type', 'Botble\RealEstate\Models\Account');
            })
            ->with(['property'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'error' => false,
            'data' => $consults,
        ]);
    }

    public function getReviews(Request $request)
    {
        $user = $request->user();

        // Reviews written by the user, or reviews written about their properties.
        $reviews = Review::where('account_id', $user->id)
            ->orWhereHasMorph('reviewable', [Property::class], function ($query) use ($user) {
                $query->where('author_id', $user->id)
                      ->where('author_type', 'Botble\RealEstate\Models\Account');
            })
            ->with(['reviewable', 'author'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'error' => false,
            'data' => $reviews,
        ]);
    }

    public function getInvoices(Request $request)
    {
        $user = $request->user();

        $invoices = Invoice::where('account_id', $user->id)
            ->with(['payment', 'reference'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'error' => false,
            'data' => $invoices,
        ]);
    }

    public function getPackages(Request $request)
    {
        $user = $request->user();
        
        $packages = Package::where('status', 'published')
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'error' => false,
            'data' => [
                'current_credits' => $user->credits,
                'packages' => $packages
            ],
        ]);
    }

    public function subscribePackage(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:re_packages,id',
        ]);

        $user = $request->user();
        $package = Package::findOrFail($request->input('package_id'));

        // Check account limit
        if ($package->account_limit && $user->packages()->where('package_id', $package->id)->count() >= $package->account_limit) {
            return response()->json([
                'error' => true,
                'message' => 'You have reached the limit of purchasing this package.',
            ], 403);
        }

        // Add credits
        $user->credits += $package->number_of_listings;
        $user->save();

        $user->packages()->attach($package);

        Transaction::query()->create([
            'user_id' => 0,
            'account_id' => $user->id,
            'credits' => $package->number_of_listings,
            'payment_id' => null, // Assuming payment is handled or mocked for now
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Package purchased successfully.',
            'data' => [
                'current_credits' => $user->credits,
            ]
        ]);
    }
}
