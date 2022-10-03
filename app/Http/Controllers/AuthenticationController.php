<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticationController extends Controller
{
    //

    public function prueba(Request $request){
        $x = DB::connection('mysql2')->table('alt_cambios_ofertas')->where('id', 5)->get();
        return $x;
    }

    public function login(Request $request){
        $dn = $request->dn;
        $contraseña = $request->post('password');
        $password = Hash::make($contraseña);
        return $password;
        $data = DB::connection('corp_app')->table('users')->where('phone', $dn)->get();
        return $data;
    }

    public function register(Request $request){
        return $request;
    }
}