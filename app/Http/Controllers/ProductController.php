<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

use App\Helpers\JsonResponseHelper;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Products;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    //

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:products,name',
            'description' => 'nullable|string',
            'quantity' =>  'required|numeric|min:0',
            'price'       => 'required|numeric|min:0',
            'brand'    => 'required|exists:brand,id',
            'category' => 'required|exists:category,id',
            'media.*'     => 'nullable|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm|max:51200',
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
        $mediaItems = [];

        if ($request->hasFile('media')) {
            $files = $request->file('media');
            // Normalize to array
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                $path = $file->store('products/media', 'public');

                $mime = $file->getMimeType();
                $type = str_starts_with($mime, 'image') ? 'image' : 'video';
                $url  = Storage::url($path);
                $mediaItems[] = [
                    'path' => $path,
                    'type' => $type,
                    'url'  => $url,
                ];
            }
        }

        $product = Products::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price'       => $validated['price'] * 100,
            'quantity' => $validated['quantity'],
            'media'  => json_encode($mediaItems),
            'created_by' => $request->user->id,
        ]);

        Brand::where('id', $validated['brand'])->increment('num_products');
        Category::where('id', $validated['category'])->increment('num_products');


        DB::table('products_brand')->insert([
            'product_id' => $product->id,
            'brand_id'   => $validated['brand'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products_category')->insert([
            'product_id'  => $product->id,
            'category_id' => $validated['category'],
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);


        return JsonResponseHelper::standardResponse(
            200,
            $product,
            'Product created successfully',
        );
    }



    public function index()
    {

        $cursor = request()->query('cursor', 1);
        $page = $cursor;
        $perPage = request()->query('perpage', 1);
        $offset = ($page - 1) * $perPage;
        $products =  DB::table('products')
            // ->select('id', 'name', 'description')
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return  JsonResponseHelper::standardResponse(
            201,
            $products
        );
    }
}
