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

        Category::increaseProducts($validated['category'], $validated['quantity']);
        Brand::increaseProducts($validated['brand'], $validated['quantity']);



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

    public function index(Request $request)
    {
        $cursor = $request->query('cursor', 1);
        $page = $cursor;
        $category = $request->query('category');
        $brands = $request->query('brand', []);
        $sort = $request->query('sort');
        $perPage = request()->query('perpage', 1);
        $offset = ($page - 1) * $perPage;
        // $placeholders = implode(',', array_fill(0, count($category), '?'));
        $query = DB::table('products as p')
            ->select('p.*')
            ->when($brands, function ($q) use ($brands) {
                $q->join('products_brand as pb', 'p.id', '=', 'pb.product_id')
                    ->join('brand as b', 'b.id', '=', 'pb.brand_id')
                    ->whereIn('b.name',$brands);
            });
        if ($category) {
             $query->join("products_category as pc",'p.id' , '=', 'pc.product_id')
             ->join('category as c', 'c.id', '=', 'pc.category_id')
                    ->where('c.name', $category);
        }
        if ($sort === 'price-asc') {
            $query->orderBy('p.price', 'asc');
        } elseif ($sort === 'price-desc') {
            $query->orderBy('p.price', 'desc');
        } elseif ($sort === 'rating') {
            $query->orderBy('p.rating', 'desc');
        } else {
            $query->orderBy('p.id', 'desc');
        }


        $products = [];

        $products =  $query
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

    public function get($id)
    {
        $product = Products::find($id);
        if (! $product) {
            return JsonResponseHelper::standardResponse(404, null, 'Product not found');
        }
        $media = json_decode($product->media);
        $categories = DB::select("SELECT c.id, c.name from products_category as pc inner join category as c on pc.category_id = c.id  where pc.product_id = ?", [$id]);
        $brand = DB::select("SELECT b.name ,b.id from products_brand as pb inner join brand as b on pb.brand_id = b.id  where pb.product_id = ?", [$id]);

        return  JsonResponseHelper::standardResponse(200, ['product' => $product, 'media' => $media,  'categories' => $categories, 'brand' => $brand]);
    }


    public function addCategory(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|exists:category,id',
        ]);
        if ($validator->fails()) {
            return JsonResponseHelper::standardResponse(400, ['status' => 'error',], 'Invalid input', ['errors' => $validator->errors()]);
        }
        $validated = $validator->validated();
        if (count(DB::select("SELECT id from products_category where product_id = ? and category_id = ? limit 1", [$id, $validated['category']])) > 0) {
            return JsonResponseHelper::standardResponse(400, null, 'Produt already has this category');
        };
        $product = Products::find($id);
        DB::table('products_category')->insert(['product_id' => $id, 'category_id' => $validated['category']]);
        Category::increaseProducts($validated['category'], $product->quantity);
        return JsonResponseHelper::standardResponse(200, null, 'Produt add to category successfully');
    }


    public function removeCategory(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|exists:category,id',
        ]);
        if ($validator->fails()) {
            return JsonResponseHelper::standardResponse(400, ['status' => 'error',], 'Invalid input', ['errors' => $validator->errors()]);
        }
        $validated = $validator->validated();
        if (count(DB::select("SELECT id from products_category where product_id = ? and category_id = ? limit 1", [$id, $validated['category']])) < 1) {
            return JsonResponseHelper::standardResponse(400, null, 'Produt dos not belong in this category');
        };
        $product = Products::find($id);

        DB::table('products_category')
            ->where('product_id', $id)
            ->where('category_id', $validated['category'])
            ->delete();
        Category::increaseProducts($validated['category'], $product->quantity * -1);
        return JsonResponseHelper::standardResponse(200, null, 'Produt removed from category successfully');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:products,name,' . $id,
            'description' => 'nullable|string',
            'quantity' =>  'required|numeric|min:0',
            'price'       => 'required|numeric|min:0',
            'brand'    => 'required|exists:brand,id',
        ]);
        if ($validator->fails()) {
            return JsonResponseHelper::standardResponse(400, ['status' => 'error'], 'Invalid input', ['errors' => $validator->errors()]);
        }
        // $product = Products::find($id);
        $Oldbrand = DB::select("SELECT brand_id from products_brand where product_id = ? limit 1", [$id]);
        $validated = $validator->validated();
        $product =  Products::find($id);

        Brand::increaseProducts($Oldbrand[0]->brand_id, $product->quantity * -1);
        Brand::increaseProducts($validated['brand'], $validated['quantity']);
        if ($validated['brand'] !=  $Oldbrand[0]->brand_id) {
            DB::table('products_brand')->where('product_id', $id)->update(['brand_id' => $validated['brand']]);
        }
        $product->update(['price' => $validated['price'] * 100, 'name' => $validated['name'], 'description' => $validated['description'], 'quantity' => $validated['quantity']]);


        return JsonResponseHelper::standardResponse(200, null, 'update successfull');
    }
}
