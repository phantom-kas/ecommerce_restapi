<?php

namespace App\Http\Controllers;

use App\Models\System;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    //



    public function increaseProductCount(int $by = 1)
    {
        System::first()->increment('products_count', $by);
    }

    public function increaseCategoryCount(int $by = 1)
    {
        System::first()->increment('category_count', $by);
    }

    public function increaseBrandCount(int $by = 1)
    {
        System::first()->increment('brand_count', $by);
    }
}
