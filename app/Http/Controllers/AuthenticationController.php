<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Carbon;
use App\GuzzleHttp;

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
        $dataUser = DB::connection('mysql')->table('users')->where('phone', $dn)->get();
        $credentials = $request->only('phone', 'password');

        $getdata = sizeof($dataUser);
        if ($getdata == 1) {
            $passwordDB = $dataUser[0]->password;
            $user_id = $dataUser[0]->id;

            $token = $this->guard()->attempt($credentials);

            if (!$token) {
                return response()->json(['error' => 'Unauthorized'], 401);            
            }
    
            DB::connection('mysql')->table('users')->where('phone', $dn)->update(['jwt_token' => $token]);

            if (password_verify($contraseña, $passwordDB)) {
                $devices = DB::connection('mysql')->table('devices')->join('users', 'users.id', '=' ,'devices.user_id')->where('devices.user_id', $user_id)->where('devices.company', 'Conecta')->select('devices.*', 'users.email AS user_email')->get();

                return response()->json([
                    'http_code'=>'200',
                    'message'=>'Inicio de sesion exitoso',
                    'userId' => $user_id,
                    'devices' => $devices,
                    'jwt' => $token
                ], 200);
            }else{
                return response()->json(['http_code'=>'400', 'message' => 'Datos Incorrectos'], 400);
            }
        }else{
            return response()->json(['http_code'=>'400', 'message' => 'Datos Incorrectos'], 400);
        }
        // return $passwordDB;
    }

    public function register(Request $request){
        $name = $request['name'];
        $email = $request['email'];
        $phone = $request['phone'];
        $password = $request['password'];
        $passwordConfirm = $request['passwordConfirm'];
        $credentials_jwt = $request->only('phone', 'password');
        
        if($password == $passwordConfirm){

            $NumeroExistente = DB::connection('mysql')->table('users')
            ->select('phone')
            ->where('phone', '=', $phone)->exists();
            
            $credentials = DB::connection('mysql')->table('users')
            ->select('id')
            ->orderBy('id', 'desc')
            ->first();

            if ($NumeroExistente == 1){
                return response()->json(['http_code'=>'400','message'=>'Número se encuentra registrado','dataDB'=>$credentials],400);
            }else{
                $newPassword = Hash::make($password);

                $data = DB::connection('mysql')->table('users')->insert(['name'=>$name, 'email'=>$email, 'phone'=>$phone, 'password'=>$newPassword]);

                $token = $this->guard()->attempt($credentials_jwt);

                DB::connection('mysql')->table('users')->where('phone', $phone)->update(['jwt_token' => $token]);

                return response()->json([
                    'http_code'=>'200',
                    'message'=>'Registro Existoso',
                    'dataDB'=> $data,
                    'nombre' => $name,
                    'correo' => $email,
                    'telefono' => $phone,
                    'contrasenia' => $password,
                    'jwt' => $token
                ], 200);
            }
            
        }
        // else{
        //     return response()->json(['http_code'=>'500','message'=>'Verifica tu password que coincidan'],500);
        // }
        
    }

    
    public function loginPos(Request $request){
        $username = $request['username'];
        $password = $request['password'];
        $data = DB::connection('mysql')->table('pos_users')->where('username', $username)->get();

        $getdata = sizeof($data);
        if ($getdata == 1) {
            $passwordDB = $data[0]->password;
            $user_id = $data[0]->id;
            $name = $data[0]->first_name;
            $lastname = $data[0]->last_name;
            $saldo = $data[0]->saldo;
    
            if (password_verify($password, $passwordDB)) {
                return response()->json([
                    'http_code'=>'200',
                    'message'=>'Inicio de sesion exitoso',
                    'user_id' => $user_id,
                    'name' => $name,
                    'lastname' => $lastname,
                    'saldo' => $saldo,
                ], 200);
            }else{
                return response()->json(['http_code'=>'400', 'message' => 'Datos Incorrectos'], 400);
            }
        }else{
            return response()->json(['http_code'=>'400', 'message' => 'Datos Incorrectos'], 400);
        }
    }

    protected function respondWithToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    public function guard(){
        return Auth::guard();
    }

    public function updateClient(Request $request){
        // return $request;
        $name = $request->name;
        $lastname = $request->lastname;
        $email = $request->email;
        $picture = $request->picture;
        $clientId = $request->clientId;
        $password = $request->password;
        
        // $verifyPass = empty($password);
        if ($password == null) {
            $data = DB::connection('mysql')->table('users')->where('id', $clientId)->update([
                'name' => $name,
                // 'lastname' => $lastname
                'email' => $email,
                'profile_photo_path' => $picture,
            ]);
            return $data;
        }else{
            // ENCRIPTAR CONTRASEÑA
        }
        $data = DB::connection('mysql')->table('users')->where('id', $clientId)->get();
        return $data;
    }
}