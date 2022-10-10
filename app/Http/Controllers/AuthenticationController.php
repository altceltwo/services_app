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
        $name = $request['name'];
        $email = $request['email'];
        $phone = $request['phone'];
        $password = $request['password'];
        $passwordConfir = $request['passwordConfir'];


        if($password == $passwordConfir){

            $NumeroExistente = DB::connection('app_mobile')->table('users')
            ->select('phone')
            ->where('phone', '=', $phone)->exists();

            if ($NumeroExistente == 1){
                return response()->json(['http_code'=>'400','message'=>'Número se encuentra registrado']);
            }else{
                $newPassword = Hash::make($password);
                $data = DB::connection('app_mobile')->table('users')->insert(['name'=>$name, 'email'=>$email, 'phone'=>$phone, 'password'=>$newPassword]);
                return response()->json(['http_code'=>'200','message'=>'Registro Existoso']);
            }
            
        }else{
            return response()->json(['http_code'=>'400','message'=>'Verifica tu password que coincidan']);
        }
        
    }
}