<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class DeviceController extends Controller
{
    //
    public function getDevice(Request $request){
        $user_id =  $request->user_id;
        $device = DB::connection('corp_app')->table('devices')->where('user_id', $user_id)->get();
        return $device;
    }
}
