<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SystemController extends Controller
{
    //
    public function getSettings()
    {
        $system = DB::select("SELECT call_to_action_text , alt_checkout_link,media,hero_text ,allow_checkout from system")[0];
        return JsonResponseHelper::standardResponse(200, $system, 'Get System');
    }

    public function updateSettingsAllowCheckout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ac'        => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return JsonResponseHelper::standardResponse(
                400,
                ['status' => 'error',],
                'Invalid input',
                ['errors' => $validator->errors()]
            );
        }
        $validated = $validator->validated();
        $affected = DB::table("system")->where('id', 1)->update(['allow_checkout'=> $validated['ac']]);
        return JsonResponseHelper::standardResponse(200, $affected, 'Update successfull');
    }


    public function updateSettingsMelink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mel'        => 'required|url',
        ]);
        if ($validator->fails()) {
            return JsonResponseHelper::standardResponse(
                400,
                ['status' => 'error',],
                'Invalid input',
                ['errors' => $validator->errors()]
            );
        }
        $validated = $validator->validated();
        $affected = DB::table("system")->where('id', 1)->update(['alt_checkout_link'=> $validated['mel']]);
        return JsonResponseHelper::standardResponse(200, $affected, 'Update successfull');
    }



    public function updateCta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cta'        => 'required|string',
        ]);
        if ($validator->fails()) {
            return JsonResponseHelper::standardResponse(
                400,
                ['status' => 'error',],
                'Invalid input',
                ['errors' => $validator->errors()]
            );
        }
        $validated = $validator->validated();
        $affected = DB::table("system")->where('id', 1)->update(['call_to_action_text'=> $validated['cta']]);
        return JsonResponseHelper::standardResponse(200, $affected, 'Update successfull');
    }



    public function updateHerotxt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'htxt'        => 'required|string',
        ]);
        if ($validator->fails()) {
            return JsonResponseHelper::standardResponse(
                400,
                ['status' => 'error',],
                'Invalid input',
                ['errors' => $validator->errors()]
            );
        }
        $validated = $validator->validated();
        $affected = DB::table("system")->where('id', 1)->update(['hero_text'=> $validated['htxt']]);
        return JsonResponseHelper::standardResponse(200, $affected, 'Update successfull');
    }


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
