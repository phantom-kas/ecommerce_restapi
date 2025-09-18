<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

Route::get('/product/{slug}-{id}', function ($slug, $id) {
    $product = DB::table('products')->find($id);

    if (!$product) {
        abort(404);
    }
    $crawlerDetect = new CrawlerDetect;

    if ($crawlerDetect->isCrawler()) {
        $media = json_decode($product->media, true);
        $image = $media[0]['url'] ?? asset('default.jpg');
        return view('product-share', [
            'product' => $product,
            'image'   => $image,
        ]);
    }
    return redirect(env("FRONTEND_ORIGIN")."/product/{$id}");
});




Route::get('/category/{slug}-{id}', function ($slug, $id) {
    $category = DB::table('category')->find($id);

    if (!$category) {
        abort(404);
    }

    $products = DB::select("
        SELECT p.* 
        FROM products p
        INNER JOIN products_category pc ON pc.product_id = p.id 
        WHERE pc.category_id = ?
    ", [$id]);

    $items = collect($products)->map(function ($p, $index) {
        $media = json_decode($p->media, true);
        $image = $media[0]['url'] ?? asset('default.jpg');
        return [
            'id' => $p->id,
            'slug' => '$slug',
            'name' => $p->name,
            'description' => $p->description,
            'price' => $p->price / 100, // divide by 100 for decimals
            'currency' => 'GHS',
            'image' => $image,
            'position' => $index + 1,
        ];
    })->toArray();

    $crawlerDetect = new CrawlerDetect;

    // if ($crawlerDetect->isCrawler()) {
    if (true) {
        // Build JSON-LD here
        $jsonLd = [
            "@context" => "https://schema.org",
            "@type" => "ItemList",
            "name" => $category->name,
            "itemListElement" => array_map(function ($p) {
                return [
                    "@type" => "Product",
                    "position" => $p['position'],
                    "name" => $p['name'],
                    "image" => $p['image'],
                    "description" => strip_tags($p['description'] ?? ''),
                    "offers" => [
                        "@type" => "Offer",
                        "priceCurrency" => $p['currency'],
                        "price" => number_format($p['price'], 2, '.', ''),
                        "availability" => "https://schema.org/InStock",
                        "url" => url('/product/'.$p['slug'].'-'.$p['id']),
                    ],
                ];
            }, $items),
        ];

        return view('category-share', [
            'category' => $category,
            'items'    => $items,
            'jsonLd'   => $jsonLd,
        ]);
    }

    return redirect(env("FRONTEND_ORIGIN")."/category/{$id}");
});