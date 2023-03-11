<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Carbon;
use App\GuzzleHttp;

class ConsumosController extends Controller
{
    public function index(){
        //
    }

    public function create(){
        //
    }

    public function store(Request $request){
        //
    }

    public function show($id){
        //
    }

    public function edit($id){
        //
    }

    public function update(Request $request, $id){
        //
    }

    public function destroy($id){
        //
    }

    public function consultCdrs(Request $request){
        return 1;
        $type = $request->type;
        $phone = $request->phone;

        $dateStart = $request->dateStart;
        $dateEnd = $request->dateEnd;

        $dateStart = date('Y-m-d H:i:s', strtotime($dateStart));
        $dateEnd = date('Y-m-d H:i:s', strtotime($dateEnd));

        if ($dateStart == $dateEnd) {
            $dateEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dateEnd)->addDays(1);
        }else {
            $dateEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dateEnd);
        }
        $dateStart = Carbon::createFromFormat('Y-m-d H:i:s', $dateStart);

        switch ($type) {
            case 'SMS':
                $consult = DB::connection('local')->table('sms')
                                                ->where("CallingPartyIMSI", '=', $phone)
                                                ->whereBetween('ChargingTime', [ $dateStart, $dateEnd ])
                                                ->orderBy('ChargingTime', 'DESC')
                                                ->get();
                break;
            
            case 'Voz':
                $consult = DB::connection('local')->table('voz')
                                                ->where("RECIPIENT_NUMBER", '=', $phone)
                                                ->whereBetween('ChargingTime', [ $dateStart, $dateEnd ])
                                                ->orderBy('ChargingTime', 'DESC')
                                                ->get();
                break;
            
            case 'Datos':
                $consult = "datos";
                break;
            default:
                $consult = "error";
                break;
        }

        // return [$dateStart, $dateEnd];
        return $consult;

    }
}
