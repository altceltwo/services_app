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
        $dn = $request['phone'];
        $contraseña = $request['password'];
        $data = DB::connection('corp_app')->table('users')->where('phone', $dn)->get();
        $passwordDB = $data[0]->password;

        if (password_verify($contraseña, $passwordDB)) {
            return response()->json(['http_code'=>'200', 'message' => 'Inicio de sesión exitoso']);
        }else{
            return response()->json(['http_code'=>'400', 'message' => 'Contraseña Incorrecta']);

        }
        // return $passwordDB;
    }

    public function register(Request $request){
        $name = $request['name'];
        $data = DB::connection('app_mobile')->table('users')->insert([
            'name'=> $name
        ]);
        
    }
}