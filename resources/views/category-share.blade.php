<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $category->name ?? 'Category' }} - My Shop</title>

    <meta property="og:title" content="{{ $category->name ?? 'Category' }}">
    <meta property="og:description" content="Browse products in {{ $category->name }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $items[0]['image'] ?? asset('default.jpg') }}">

    {{-- JSON-LD --}}
    <script type="application/ld+json">
        {!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}
    </script>
</head>
<body>
    <h1>{{ $category->name ?? 'Products' }}</h1>
    <ul>
        @foreach ($items as $p)
            <li>
                <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" width="200">
                <h2>{{ $p['name'] }}</h2>
                <p>{{ $p['description'] }}</p>
                <div>
                    <strong>{{ $p['currency'] }} {{ number_format($p['price'], 2) }}</strong>
                </div>
            </li>
        @endforeach
    </ul>
</body>
</html>
