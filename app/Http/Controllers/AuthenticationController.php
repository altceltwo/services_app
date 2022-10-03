<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class AuthenticationController extends Controller
{
    //

    public function prueba(Request $request){
        $x = DB::connection('mysql2')->table('alt_cambios_ofertas')->where('id', 5)->get();
        return $x;
    }

    public function login(Request $request){
        $dn = $request->post('dn');
        $contraseña = $request->post('password');
        $password = $contraseña;
        $data = DB::connection('corp_app')->table('users')->where('phone', $dn)->where('password', $password)->get();

        return $data;
    }
}