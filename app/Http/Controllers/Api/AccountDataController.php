<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Botble\RealEstate\Models\Consult;
use Botble\RealEstate\Models\Invoice;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\Review;
use Illuminate\Http\Request;

class AccountDataController extends Controller
{
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
            ->with(['payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'error' => false,
            'data' => $invoices,
        ]);
    }
}
