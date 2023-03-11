<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use DB;

class RechargeClientController extends Controller
{
    public function getPlanes(Request $request){

        $producto =  $request->product;
        $devices = DB::connection('app_mobile')
        ->table('pos_rates')
        ->where('product', $producto)
        ->select('pos_rates.*',)
        ->get();
        return response()->json(['rates'=> $devices],200);
        // $data = sizeof($devices);

    }
}
