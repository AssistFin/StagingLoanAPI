<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorSetting;
use App\Models\Menu;
use App\Models\Submenu;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = VendorSetting::all();
        return view('admin.vendors.index', compact('vendors'));
    }

    public function toggle(Request $request, VendorSetting $vendor, $field)
    {
        if (in_array($field, ['kyc', 'credit_bureau', 'bsa_report'])) {
            
        // Turn OFF all except blocked ones (value 2)
        VendorSetting::where($field, '!=', 2)->update([$field => 0]);

            // Only update if current vendor is not blocked
            if ($vendor->$field != 2) {
                $vendor->$field = 1; // ON
                $vendor->save();
            }

            $vendors = VendorSetting::all();
            return view('admin.vendors.index', compact('vendors'));
        }

        return response()->json(['success' => false], 400);
    }
}