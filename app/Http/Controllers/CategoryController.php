<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class CategoryController extends Controller
{
    //

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:category,name',
            'description' => 'nullable|string',
        ]);
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
        $brand = Category::create([
            'name'        => $validated['name'],
            'description' => $validated['description'],
        ]);

        return JsonResponseHelper::standardResponse(
            201,
            $brand,
            'Category created successfully'
        );
    }


    public function index()
    {
        $cursor = request()->query('cursor', 1);
        $page = $cursor;
        // items per page
        $perPage = request()->query('perpage', 1);
        // calculate offset
        $offset = ($page - 1) * $perPage;
        $categories =  DB::table('category')
            // ->select('id', 'name', 'description')
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();
        return  JsonResponseHelper::standardResponse(
            201,
            $categories,
        );
    }

    public function short()
    {
        // calculate offset
        $categories =  DB::table('category')
            ->select('id', 'name', )
            ->orderBy('id', 'desc')
            ->get();
        return  JsonResponseHelper::standardResponse(
            201,
            $categories,
        );
    }
}
