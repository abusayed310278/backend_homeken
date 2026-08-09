<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Botble\RealEstate\Models\Category;
use Botble\RealEstate\Models\Property;
use Botble\Location\Models\City;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('status', 'published')
            ->orderBy('order', 'asc')
            ->get(['id', 'name']);
            
        $properties = Property::whereIn('status', ['selling', 'renting'])
            ->with(['slugable', 'city', 'currency'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();
            
        // Map images correctly since botble stores them as json
        $properties->transform(function ($property) {
            $property->image_url = $property->image_thumb; // Or the first image in images array
            if ($property->images && count($property->images) > 0) {
                 $property->image_url = \RvMedia::getImageUrl($property->images[0], 'medium', false, \RvMedia::getDefaultImage());
            } else {
                 $property->image_url = \RvMedia::getDefaultImage();
            }
            return $property;
        });
            
        $cities = City::where('status', 'published')
            ->take(10)
            ->get();

        $cities->transform(function ($city) {
            $city->properties_count = Property::where('city_id', $city->id)->count();
            if ($city->image) {
                $city->image_url = \RvMedia::getImageUrl($city->image, 'medium', false, \RvMedia::getDefaultImage());
            } else {
                $city->image_url = \RvMedia::getDefaultImage();
            }
            return $city;
        });

        // Sort by properties count descending
        $cities = $cities->sortByDesc('properties_count')->values();

        return response()->json([
            'error' => false,
            'data' => [
                'categories' => $categories,
                'properties' => $properties,
                'cities' => $cities,
            ]
        ]);
    }
}
