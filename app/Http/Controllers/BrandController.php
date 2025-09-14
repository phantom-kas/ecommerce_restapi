<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use Illuminate\Http\Request;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;
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

        app(SystemController::class)->increaseBrandCount();

        return JsonResponseHelper::standardResponse(
            201,
            $brand,
            'Brand created successfully'
        );
    }

    public function index()
    {

        $cursor = request()->query('cursor', 1);
        $page = $cursor;

        // items per page
        $perPage = request()->query('perpage', 20);
        // calculate offset
        $offset = ($page - 1) * $perPage;
        $brands =  DB::table('brand')
            // ->select('id', 'name', 'description')
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();


        return  JsonResponseHelper::standardResponse(
            201,
            $brands,
            'Brand created successfully'
        );
    }


    public function short()
    {
        // calculate offset
        $categories =  DB::table('brand')
            ->select('id', 'name',)
            ->orderBy('id', 'desc')
            ->get();
        return  JsonResponseHelper::standardResponse(
            201,
            $categories,
        );
    }
}
