<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use DateTime;

class PosController extends Controller
{
    public function getDataUser(Request $request){
        $user_id = $request['user_id'];
        $data = DB::connection('mysql')->table('pos_users')->where('id',$user_id)->select('pos_users.*')->get();
        
        if(sizeof($data) > 0){
            return response()->json([
                    'http_code'=>'200',
                    'message'=>'Datos encontrados',
                    'user_id' => $user_id,
                    'name' => $data[0]->first_name,
                    'lastname' => $data[0]->last_name,
                    'saldo' => $data[0]->saldo,
                    'salesforce' => $data[0]->salesforce
                ], 200);
        }else{
            return response()->json(['http_code'=>'400', 'message' => 'Datos No Encontrados','user_id' => 0, 'name' => 0, 'lastname' => 0, 'saldo' => 0], 400);
        }
    }
    
    public function getRates(Request $request){
        $msisdn = $request['msisdn'];
        $offeringId = $request['offeringId'];
        $dataNumber = DB::connection('mysql')->table('numbers2')->where('MSISDN',$msisdn)->where('deleted_at',null)->select('numbers2.*')->get();
        
        if(sizeof($dataNumber) > 0){
            $foundNumber = $dataNumber[0]->MSISDN;
            $expire_date = $dataNumber[0]->expire_date;
            $number_id = $dataNumber[0]->id;
            $product = $dataNumber[0]->producto;
            $product = trim($product);
            
            $rates = [];
            
            if($product == 'MOV'){
                $rates = DB::connection('mysql')->table('pos_rates')->where('product',$product)->where('type_product', 'recarga')->select('pos_rates.*')->get();
            }else{
                $now = date('Y-m-d');
                $payment = DB::connection('mysql')->table('payments')->where('number_id', $number_id)->where('status','pending')->exists();
                
                if($payment && $now >= $expire_date){
                    return response()->json(['message' => 'Al parecer el MSISDN '.$msisdn.' tiene una mensualidad por pagar.'],400); 
                }
                
                $rates = DB::connection('mysql')->table('pos_rates')->where('product',$product)->where('type_product', 'recarga')->where('father_offer', $offeringId)->select('pos_rates.*')->get();
            }
            
            
            if(sizeof($rates) > 0){
                return response()->json(['message' => 'Rates available', 'rates' => $rates],200);
            }else{
               return response()->json(['message' => 'Rates not available for '.$msisdn],400); 
            }
            
        }else{
            return response()->json(['message' => 'MSISDN Not Found', 'http_code' => 400],400);
        }
        
        return response()->json(['rates' => $rates]);
    }

    public function getRatesActivationPos(Request $request){
        $msisdn = $request['msisdn'];
        $dataNumber = DB::connection('mysql')->table('numbers2')->where('MSISDN',$msisdn)->where('deleted_at',null)->select('numbers2.*')->get();
        
        if(sizeof($dataNumber) > 0){
            $product = $dataNumber[0]->producto;
            $product = trim($product);
            
            $rates = [];
            
            // if($product == 'MOV'){
                $rates = DB::connection('mysql')->table('pos_rates')->where('product',$product)->where('type_product', 'alta')->select('pos_rates.*')->get();
            // }else{
            //     $now = date('Y-m-d');
            //     $payment = DB::connection('mysql')->table('payments')->where('number_id', $number_id)->where('status','pending')->exists();
                
            //     if($payment && $now >= $expire_date){
            //         return response()->json(['message' => 'Al parecer el MSISDN '.$msisdn.' tiene una mensualidad por pagar.'],400); 
            //     }
                
            //     $rates = DB::connection('mysql')->table('pos_rates')->where('product',$product)->where('type_product', 'recarga')->where('father_offer', $offeringId)->select('pos_rates.*')->get();
            // }
            
            
            if(sizeof($rates) > 0){
                return response()->json(['message' => 'Rates available', 'rates' => $rates],200);
            }else{
               return response()->json(['message' => 'Rates not available for '.$msisdn],400); 
            }
            
        }else{
            return response()->json(['message' => 'MSISDN Not Found', 'http_code' => 400],400);
        }
        
        return response()->json(['rates' => $rates]);
    }
    
    public function saveRechargePos(Request $request){
        $user_id = $request['userID'];
        $rate_id = $request['rate_id'];
        $rate_name = $request['rate_name'];
        $price = $request['price'];
        $product = $request['product'];
        $type_product = $request['type_product'];
        $effectiveDate = $request['effectiveDate'];
        $msisdn = $request['msisdn'];
        $order_id = $request['orderID'];
        $salesforceUser = $request['salesforceUser'];
        $saldo = $request['saldo'];
        $saldo = $saldo - $price;
        
        $data = [
            'msisdn' => $msisdn,
            'rate_id' => $rate_id,
            'rate_name' => $rate_name,
            'price' => $price,
            'product' => $product,
            'type_product' => $type_product,
            'order_id' => $order_id,
            'user_id' => $user_id,
            'effectiveDate' => $effectiveDate
        ];
        
        $x = DB::connection('mysql')->table('pos_recharges')->insert($data);
        $y = true;

        if($salesforceUser == 0){
            $y = DB::connection('mysql')->table('pos_users')->where('id',$user_id)->update(['saldo' => $saldo]);
        }
        
        if($x && $y){
            return response()->json(['message'=>'Successfully saved.','http_code' => 200],200);
        }else{
            return response()->json(['message'=>'Algo sali車 mal, intente de nuevo o notifique a Desarrollo.','http_code' => 500],500);
        }
        
    }

    public function saveDataActivationPos(Request $request){
        $user_id = $request['dealer_id'];
        $saldo = $request['saldo_user'];
        $salesforceUser = $request['salesforceUser'];
        $dateToPort = date("Y-m-d");

        if($user_id == null || $user_id == 0 || $user_id == "empty"){
            return response()->json(['message'=>'Al parecer no hemos podido identificar qué usuario eres. Esto se debe a que la app no guardó bien tus datos, por favor inicia sesión de nuevo, de lo contrario, no se te podrá contabilizar la activación o portabilidad.','http_code' => 500],500);
        }

        if($request['portIn'] == 1){
            $isValidToPortDate = PosController::validateDatePortToday($request['datePortIn']);

            if(!$isValidToPortDate){
                $dateToPort = PosController::validateDatePortIn(date("Y-m-d"));
            }else{
                $dateToPort = PosController::validateDatePortIn($request['datePortIn']);
            }
        }
        

        $data = [
            'msisdn' => $request['msisdn'],
            'number_id' => $request['number_id'],
            'offerID' => $request['offerID'],
            'pos_rate_id' => $request['pos_rate_id'],
            'client_name' => $request['client_name'],
            'client_lastname' => $request['client_lastname'],
            'address' => $request['address'],
            'date_activation' => $request['date_activation'],
            'order_id' => $request['order_id'],
            'amount' => $request['amount'],
            'dealer_id' => $request['dealer_id'],
            'portIn' => $request['portIn'],
            'datePortIn' => $dateToPort,
            'msisdnPorted' => $request['msisdnPorted'],
            'nip' => $request['nip'],
        ];

        $x = DB::connection('mysql')->table('pos_activations')->insert($data);
        $y = true;

        if($salesforceUser == 0){
            $y = DB::connection('mysql')->table('pos_users')->where('id',$user_id)->update(['saldo' => $saldo]);
        }

        if($x && $y){
            return response()->json(['message'=>'Successfully saved.','http_code' => 200],200);
        }else{
            return response()->json(['message'=>'Algo salió mal, intente de nuevo o notifique a Desarrollo.','http_code' => 500],500);
        }
    }
    
    public function getNumberByIcc(Request $request){
        $icc = $request['icc'];
        $dataNumber = DB::connection('mysql')->table('numbers2')->where('icc_id',$icc)->where('deleted_at',null)->select('numbers2.*')->get();
        
        if(sizeof($dataNumber) > 0){
            return response()->json(['message' => 'Number available.','number' => $dataNumber[0]],200); 
        }else{
            return response()->json(['message' => 'No se encontró el ICC: '.$icc],400); 
        }
    }

    public function validateDatePortToday($date){
        $date = date("Y-m-d",strtotime($date));
        $now = date("Y-m-d");
        $flag = true;

        if($date >= $now){
            $flag = true;
        }else{
            $flag = false;
        }

        return $flag;
    }

    public function validateDatePortIn($date){
        date_default_timezone_set('America/Mexico_City');
        $day = date('D', strtotime($date));
        $datePortRequest = date("Y-m-d",strtotime($date));
        $today = date("Y-m-d");
        $hourNow = date("H");
        $dateToPort = "";

        if($datePortRequest > $today){
            $dateToPort = $datePortRequest;
        }else{
            if($day == "Sun" || $day == "Sat"){
                $date = $day == "Sun" ? date("Y-m-d",strtotime($date."+ 1 days")) : $date;
                $date = $day == "Sat" ? date("Y-m-d",strtotime($date."+ 2 days")) : $date;
                $dateToPort = $date;
            }else{
                if($day == "Fri"){
                    if($hourNow > 16){
                        $date = date("Y-m-d",strtotime($date."+ 3 days"));
                        $dateToPort = $date;
                    }else{
                        $dateToPort = $date;
                    }
                }else{
                    if($hourNow > 16){
                        $date = date("Y-m-d",strtotime($date."+ 1 days"));
                        $dateToPort = $date;
                    }else{
                        $dateToPort = $date;
                    }
                }
            }
        }

        return $dateToPort;
    }
}