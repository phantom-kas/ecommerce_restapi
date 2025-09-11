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


        app(CategoryController::class)->increaseCategoryCount();
        app(SystemController::class)->increaseBrandCount();

        Category::increaseProducts($validated['category']);
        Brand::increaseProducts($validated['brand']);



        return JsonResponseHelper::standardResponse(
            200,
            $product,
            'Product created successfully',
        );
    }



    public function getMedia($id)
    {
        $media = Products::getMediaById($id);

        if ($media === false) {

            return JsonResponseHelper::standardResponse(
                404,
                [$media],
                'Product not found or no media'
            );
        }



        return  JsonResponseHelper::standardResponse(
            201,
            [
                'id' => $id,
                'media' => $media,
            ]
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
    public function addMedia(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'media'     => 'nullable|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm|max:51200',
        ]);
        if ($validator->fails()) {
            return JsonResponseHelper::standardResponse(
                400,
                ['status' => 'error'],
                'Invalid input',
                ['errors' => $validator->errors()]
            );
        }
        $media = Products::getMediaById($id);
        $product = Products::find($id);

        if (! $product) {
            return JsonResponseHelper::standardResponse(404, null, 'Product not found');
        }
        if ($media === false) {
            return JsonResponseHelper::standardResponse(404, null, 'Product not found or no media');
        }


        if ($request->hasFile('media')) {
            $file = $request->file('media');
            // Normalize to array
            $path = $file->store('products/media', 'public');
            $mime = $file->getMimeType();
            $type = str_starts_with($mime, 'image') ? 'image' : 'video';
            $url  = Storage::url($path);
            array_push($media, [
                'path' => $path,
                'type' => $type,
                'url'  => $url,
            ]);
        }

        $product->update([
            'media' => json_encode($media),
        ]);

        return JsonResponseHelper::standardResponse(200, [
            'status' => 'success',
            'media'  => $media,
        ], 'Media added successfully');
    }


    public function deleteMedia(Request $request, $id)
    {

        $media = Products::getMediaById($id);
        $product = Products::find($id);

        if (! $product) {
            return JsonResponseHelper::standardResponse(404, null, 'Product not found');
        }
        if (! $media) {
            return JsonResponseHelper::standardResponse(404, null, 'Product not found or no media');
        }


        $validator = Validator::make($request->all(), [
            'index' =>  'required|numeric|min:0',
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


        // dd($media);
        $validated = $validator->validated();
        if (isset($media[$validated['index']]->path) && Storage::disk('public')->exists($media[$validated['index']]->path)) {
            Storage::disk('public')->delete($media[$validated['index']]->path);
        }



        // Remove from array
        array_splice($media,  $validated['index'], 1);
        $product->media = json_encode($media);
        $product->save();


        return JsonResponseHelper::standardResponse(
            200,
            $media,
            'Media deleted successfully'
        );
    }


    public function delete($id)
    {
        $product = Products::getProduct($id);
        if (! $product) {
            return JsonResponseHelper::standardResponse(404, null, 'Product not found');
        }
        if ($product->status == 'deleted') {
            return JsonResponseHelper::standardResponse(404, null, 'Product already deleted');
        }
        Brand::increaseProducts($product->brand, ($product->quantity) * -1);
        Category::increaseCategoryCountOfProduct($id, ($product->quantity) * -1);
        // $product->status = 'deleted';
        DB::table('products')
            ->where('id', $id)
            ->update(['status' => 'deleted']);
        return JsonResponseHelper::standardResponse(200, null, 'Product  deleted successfully');
    }



    public function restoreDeleted($id)
    {
        $product = Products::getProduct($id);
        if (! $product) {
            return JsonResponseHelper::standardResponse(404, null, 'Product not found');
        }
        if ($product->status != 'deleted') {
            return JsonResponseHelper::standardResponse(404, null, 'Product not already deleted');
        }
        Brand::increaseProducts($product->brand, $product->quantity);
        Category::increaseCategoryCountOfProduct($id, $product->quantity);
        // $product->status = 'deleted';
        DB::table('products')
            ->where('id', $id)
            ->update(['status' => 'succeeded']);
        return JsonResponseHelper::standardResponse(200, null, 'Product  restored successfully');
    }
}
