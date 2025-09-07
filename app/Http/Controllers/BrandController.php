<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use Illuminate\Http\Request;
use App\Models\Brand;
use Illuminate\Support\Facades\Validator;

class BrandController  extends Controller
{
    //
    public function store(Request $request)
    {
        // dd('hello');
        // dd(['sd']);
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:brand,name',
            'description' => 'nullable|string',
        ]);
        // dd('hello');
        // dd(\App\Models\Brand::class);
        // dd(Brand::class);

        if ($validator->fails()) {
            return JsonResponseHelper::standardResponse(
                400,
                [
                    'status' => 'error',

                ],
                'Invalid input',
                ['errors' => $validator->errors()]
            );
        }

        $validated = $validator->validated();
        $brand = Brand::create([
            'name'        => $validated['name'],
            'description' => $validated['description'],
        ]);

        return JsonResponseHelper::standardResponse(
            201,
            $brand,
            'Brand created successfully'
        );
    }
}
