<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $product->name }}" />
    <meta property="og:description" content="{{ Str::limit(strip_tags($product->description), 150) }}" />
    <meta property="og:image" content="{{ $image }}" />
    <meta property="og:url" content="{{ url('/product/'. $product->id) }}" />
    <meta property="og:type" content="product" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $product->name }}" />
    <meta name="twitter:description" content="{{ Str::limit(strip_tags($product->description), 150) }}" />
    <meta name="twitter:image" content="{{ $image }}" />

    <title>{{ $product->name }}</title>
</head>
<body>
    <h1>{{ $product->name }}</h1>
    <img src="{{ $image }}">
    <p>This page is for crawlers (Facebook, WhatsApp, Twitter, etc.).</p>
</body>
</html>
