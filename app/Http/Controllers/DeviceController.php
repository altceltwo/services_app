<?php

namespace App\Http\Controllers;
// use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;
use Http;
use App\GuzzleHttp;

class DeviceController extends Controller
{
    //
    public function getDevice(Request $request){
        $user_id =  $request->userid;
        $devices = DB::connection('mysql')->table('devices')->join('users', 'users.id', '=' ,'devices.user_id')->where('devices.user_id', $user_id)->where('devices.company', 'Conecta')->select('devices.*', 'users.email AS user_email')->get();
        //return $devices;
        $data = sizeof($devices);

        if ($data > 0) {
            // return $devices;
            return response()->json([
                'http_code'=>'200',
                'message'=>'Cuenta con dispositivos',
                'devices' => $devices,
            ], 200);
        }else{
            return response()->json(['http_code'=>'400', 'message' => 'No hay dispositivos'], 400);
        }
    }

    public function addDevice(Request $request){
        // return $request->dn;
        $dn = $request->dn;
        $userId = $request->user_id;
        $x = DB::connection('mysql')->table('numbers')->where('MSISDN', $dn)->where('status', 'taken')->exists();

        if ($x == 1) {
            $dataNumber = DB::connection('mysql')->table('numbers')->where('MSISDN', $dn)->where('status', 'taken')->select('numbers.*')->get();
            $product = $dataNumber[0]->producto;
            $product = trim($product);
            $date = date('Y-m-d h:i:s');
            $data = DB::connection('mysql')->table('devices')->insert(['number'=>$dn, 'company'=> 'Conecta', 'service'=>$product, 'user_id'=>$userId,'created_at'=>$date]);

            $devices = DB::connection('mysql')->table('devices')->join('users', 'users.id', '=' ,'devices.user_id')->where('devices.user_id', $userId)->where('devices.company', 'Conecta')->where('number', $dn)->select('devices.*', 'users.email AS user_email')->get();
            // return $devices;
            if ($data == 1) {
                // return $devices;
                return response()->json([
                    'http_code'=>'200',
                    'message'=>'Se agrego dispositivo',
                    'deviceNew' => $devices
                ], 200);
            }else{
                return response()->json(['http_code'=>'400', 'message' => 'No se agrego dispositivos'], 400);
            }
            return $data;
        }else{
            return response()->json(['http_code'=>'400', 'message' => 'No se agrego dispositivos'], 400);
        }
        return $x;
    }

    public function accessTokenRequestPost(){
        // $prelaunch = 'TzBpSndNOWlkc1ZvZDdoVThrOHcyQTJuQXhQTDdORWU6bm1GaHlCWjdYbWhtaTRTUw==';
        $production = 'ZjRWc3RzQXM4V1c0WFkyQVVtbVBSTE1pRDFGZldFQ0k6YkpHakpCcnBkWGZoajczUg==';

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$production
        ])->post('https://altanredes-prod.apigee.net/v1/oauth/accesstoken?grant-type=client_credentials', [
            'Authorization' => 'Basic '.$production,
        ]);
        return $response->json();
    }

    public function consultUf(Request $request){
        $msisdn = $request->get('msisdn');
        $service = $request->get('product');
        $accessTokenResponse = DeviceController::accessTokenRequestPost();
        if ($accessTokenResponse['status'] == 'approved') {
            $accessToken = $accessTokenResponse['accessToken'];
            
            $url_production = 'https://altanredes-prod.apigee.net/cm/v1/subscribers/'.$msisdn.'/profile';
                    
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken
            ])->get($url_production);

            $consultUF = $response->json();
            // return $consultUF;
            $responseSubscriber = $consultUF['responseSubscriber'];
            $information = $responseSubscriber['information'];
            $status = $responseSubscriber['status']['subStatus'];
            $offer = $responseSubscriber['primaryOffering']['offeringId'];
            $freeUnits = $responseSubscriber['freeUnits'];
            $coordinates = $responseSubscriber['information']['coordinates'];
            $char = explode(',',$coordinates);

            if($service == 'MIFI' || $service == 'MOV'){
                $lat_hbb = null;
                $lng_hbb = null;
            }

            $data['consultUF']['status'] = $status;
            $data['consultUF']['imei'] = $information['IMEI'];
            $data['consultUF']['icc'] = $information['ICCID'];

            if($status == 'Active'){
                $data['consultUF']['status_color'] = 'success';
            }else if($status == 'Suspend (B2W)' || $status == 'Barring (B1W) (Notified by client)' || $status == 'Barring (B1W) (By NoB28)' || $status == 'Suspend (B2W) (By mobility)' || $status = 'Suspend (B2W) (By IMEI locked)' || $status == 'Predeactivate'){
                $data['consultUF']['status_color'] = 'warning';
            }

            if($service == 'MIFI'){
                $data['FreeUnitsBoolean'] = 0;
                $data['FreeUnits2Boolean'] = 0;
                $data['consultUF']['offerID'] = 0;

                for ($i=0; $i < sizeof($freeUnits); $i++) {
                    if($freeUnits[$i]['name'] == 'Free Units' || $freeUnits[$i]['name'] == 'FU_Altan-RN'){
                        $totalAmt = $freeUnits[$i]['freeUnit']['totalAmt'];
                        $unusedAmt = $freeUnits[$i]['freeUnit']['unusedAmt'];
                        $percentageFree = ($unusedAmt/$totalAmt)*100;
                        $data['FreeUnits'] = array('totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree);
                        $data['FreeUnitsBoolean'] = 1;

                        $detailOfferings = $freeUnits[$i]['detailOfferings'];
                        // return $detailOfferings;

                        $data['effectiveDatePrimary'] = ClientController::formatDateConsultUF($detailOfferings[0]['effectiveDate']);
                        $data['expireDatePrimary'] = ClientController::formatDateConsultUF($detailOfferings[0]['expireDate']);
                        $expire_date = $detailOfferings[0]['expireDate'];
                        // return $expire_date;
                        $expire_date = substr($expire_date,0,8);

                        $data['consultUF']['offerID'] = $detailOfferings[0]['offeringId'];
                    }

                    if($freeUnits[$i]['name'] == 'Free Units 2' || $freeUnits[$i]['name'] == 'FU_Altan-RN_P2'){
                        $totalAmt = $freeUnits[$i]['freeUnit']['totalAmt'];
                        $unusedAmt = $freeUnits[$i]['freeUnit']['unusedAmt'];
                        $percentageFree = ($unusedAmt/$totalAmt)*100;
                        $data['FreeUnits2'] = array('totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree);
                        $data['FreeUnits2Boolean'] = 1;

                        $detailOfferings = $freeUnits[$i]['detailOfferings'];

                        $data['effectiveDateSurplus'] = ClientController::formatDateConsultUF($detailOfferings[0]['effectiveDate']);
                        $data['expireDateSurplus'] = ClientController::formatDateConsultUF($detailOfferings[0]['expireDate']);
                    }
                }

                $rateData = DB::table('numbers')
                                   ->leftJoin('activations','activations.numbers_id','=','numbers.id')
                                   ->leftJoin('rates','rates.id','=','activations.rate_id')
                                   ->where('numbers.MSISDN',$data['DN'])
                                   ->select('rates.name AS rate_name')
                                   ->get();

                if($status == 'Suspend (B2W)' || $status == 'Suspend (B2W) (By mobility)'){
                    $data['consultUF']['rate'] = $rateData[0]->rate_name.'/Suspendido por falta de pago';    
                }else if($status == 'Active'){
                    $data['consultUF']['rate'] = $rateData[0]->rate_name;
                }

                if($status == 'Active'){
                    // return $service;
                    Number::where('id',$number_id)->update([
                        'traffic_outbound' => 'activo',
                        'traffic_outbound_incoming' => 'activo',
                        'status_altan' => 'activo'
                    ]);
    
                    if($service = 'MIFI'){
                        Activation::where('numbers_id',$number_id)->update(['expire_date'=>$expire_date]);
                    }
    
                    // if($service = 'HBB'){
                    //     Activation::where('numbers_id',$number_id)->update(['expire_date'=>$expire_date,'lat_hbb'=>$lat_hbb,'lng_hbb'=>$lng_hbb]);
                    // }
                }else if($status == 'Suspend (B2W)'){
                    Number::where('id',$number_id)->update([
                        'traffic_outbound' => 'activo',
                        'traffic_outbound_incoming' => 'inactivo',
                        'status_altan' => 'activo'
                    ]);
                    if($service = 'MIFI'){
                        Activation::where('numbers_id',$number_id)->update(['expire_date'=>$expire_date]);
                    }
                    // if($service = 'HBB'){
                    //     Activation::where('numbers_id',$number_id)->update(['expire_date'=>$expire_date,'lat_hbb'=>$lat_hbb,'lng_hbb'=>$lng_hbb]);
                    // }
                }else if($status == 'Predeactivate'){
                    Number::where('id',$number_id)->update([
                        'traffic_outbound' => 'activo',
                        'traffic_outbound_incoming' => 'activo',
                        'status_altan' => 'predeactivate'
                    ]);
                }else if($status == 'Barring (B1W) (Notified by client)'){
                    Number::where('id',$number_id)->update([
                        'traffic_outbound' => 'inactivo',
                        'traffic_outbound_incoming' => 'activo',
                        'status_altan' => 'activo'
                    ]);
                }

                if($data['FreeUnits2Boolean'] == 0){
                    $data['FreeUnits2'] = array('totalAmt'=>0,'unusedAmt'=>0,'freePercentage'=>0);
                    $data['effectiveDateSurplus'] = 'No se ha generado recarga.';
                    $data['expireDateSurplus'] = 'No se ha generado recarga.';
                }
            }else if($service == 'MOV'){
                $data['consultUF']['freeUnits']['extra'] = [];
                $data['consultUF']['freeUnits']['nacionales'] = [];
                $data['consultUF']['freeUnits']['ri'] = [];
                $data['consultUF']['offerID'] = 0;
                for ($i=0; $i < sizeof($freeUnits); $i++) {
                    $totalAmt = $freeUnits[$i]['freeUnit']['totalAmt'];
                    $unusedAmt = $freeUnits[$i]['freeUnit']['unusedAmt'];
                    $percentageFree = ($unusedAmt/$totalAmt)*100;
                    $indexDetailtOfferings = sizeof($freeUnits[$i]['detailOfferings']);
                    $indexDetailtOfferings = $indexDetailtOfferings-1;
                    $effectiveDate = DeviceController::formatDateConsultUF($freeUnits[$i]['detailOfferings'][$indexDetailtOfferings]['effectiveDate']);
                    $expireDate = DeviceController::formatDateConsultUF($freeUnits[$i]['detailOfferings'][$indexDetailtOfferings]['expireDate']);

                    if ($offer == '1709977001') {
                        if ($freeUnits[$i]['name'] == 'FU_Data_Altan-NR-IR_NA_CT') {
                            $data['consultUF']['offerID'] = $freeUnits[$i]['detailOfferings'][$indexDetailtOfferings]['offeringId'];
                            array_push($data['consultUF']['freeUnits']['nacionales'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Datos Nacionales','description'=>'MB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
                        }else if($freeUnits[$i]['name'] == 'FU_ThrMBB_Altan-RN_512kbps_P2'){
                            $data['consultUF']['offerID'] = $freeUnits[$i]['detailOfferings'][$indexDetailtOfferings]['offeringId'];
                            array_push($data['consultUF']['freeUnits']['nacionales'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Datos Nacionales','description'=>'MB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
                        }else if($freeUnits[$i]['name'] == 'FreeData_Altan-RN_P2'){
                            $data['consultUF']['offerID'] = $freeUnits[$i]['detailOfferings'][$indexDetailtOfferings]['offeringId'];
                            array_push($data['consultUF']['freeUnits']['nacionales'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Datos Nacionales','description'=>'MB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
                        }else if($freeUnits[$i]['name'] == 'FreeData_Altan-RN'){
                            $data['consultUF']['offerID'] = $freeUnits[$i]['detailOfferings'][$indexDetailtOfferings]['offeringId'];
                            array_push($data['consultUF']['freeUnits']['nacionales'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Datos Nacionales','description'=>'MB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_SMS_Altan-NR-LDI_NA'){
                            array_push($data['consultUF']['freeUnits']['nacionales'],array(
                                'totalAmt'=>$totalAmt,'unusedAmt'=>$unusedAmt,'freePercentage'=>$percentageFree,'name'=>'SMS Nacionales','description'=>'sms','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_Min_Altan-NR-LDI_NA'){
                            array_push($data['consultUF']['freeUnits']['nacionales'],array(
                                'totalAmt'=>$totalAmt,'unusedAmt'=>$unusedAmt,'freePercentage'=>$percentageFree,'name'=>'Minutos Nacionales','description'=>'min','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_Data_Altan-NR-IR_NA'){
                            array_push($data['consultUF']['freeUnits']['ri'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Datos RI','description'=>'GB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_SMS_Altan-NR-IR-LDI_NA'){
                            array_push($data['consultUF']['freeUnits']['ri'],array(
                                'totalAmt'=>$totalAmt,'unusedAmt'=>$unusedAmt,'freePercentage'=>$percentageFree,'name'=>'SMS RI','description'=>'sms','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_Min_Altan-NR-IR-LDI_NA'){
                            array_push($data['consultUF']['freeUnits']['ri'],array(
                                'totalAmt'=>$totalAmt,'unusedAmt'=>$unusedAmt,'freePercentage'=>$percentageFree,'name'=>'Minutos RI','description'=>'min','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_Redirect_Altan-RN'){
                            array_push($data['consultUF']['freeUnits']['extra'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Navegación en Portal Cautivo','description'=>'MB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_ThrMBB_Altan-RN_512kbps'){
                            array_push($data['consultUF']['freeUnits']['extra'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Velocidad Reducida','description'=>'MB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }
                    }else{
                        if ($freeUnits[$i]['name'] == 'FU_Data_Altan-NR-IR_NA_CT') {
                            $data['consultUF']['offerID'] = $freeUnits[$i]['detailOfferings'][$indexDetailtOfferings]['offeringId'];
                            array_push($data['consultUF']['freeUnits']['nacionales'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Datos Nacionales','description'=>'MB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
                        }else if($freeUnits[$i]['name'] == 'FreeData_Altan-RN'){
                            $data['consultUF']['offerID'] = $freeUnits[$i]['detailOfferings'][$indexDetailtOfferings]['offeringId'];
                            array_push($data['consultUF']['freeUnits']['nacionales'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Datos Nacionales','description'=>'MB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_SMS_Altan-NR-LDI_NA'){
                            array_push($data['consultUF']['freeUnits']['nacionales'],array(
                                'totalAmt'=>$totalAmt,'unusedAmt'=>$unusedAmt,'freePercentage'=>$percentageFree,'name'=>'SMS Nacionales','description'=>'sms','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_Min_Altan-NR-LDI_NA'){
                            array_push($data['consultUF']['freeUnits']['nacionales'],array(
                                'totalAmt'=>$totalAmt,'unusedAmt'=>$unusedAmt,'freePercentage'=>$percentageFree,'name'=>'Minutos Nacionales','description'=>'min','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_Data_Altan-NR-IR_NA'){
                            array_push($data['consultUF']['freeUnits']['ri'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Datos RI','description'=>'GB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_SMS_Altan-NR-IR-LDI_NA'){
                            array_push($data['consultUF']['freeUnits']['ri'],array(
                                'totalAmt'=>$totalAmt,'unusedAmt'=>$unusedAmt,'freePercentage'=>$percentageFree,'name'=>'SMS RI','description'=>'sms','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_Min_Altan-NR-IR-LDI_NA'){
                            array_push($data['consultUF']['freeUnits']['ri'],array(
                                'totalAmt'=>$totalAmt,'unusedAmt'=>$unusedAmt,'freePercentage'=>$percentageFree,'name'=>'Minutos RI','description'=>'min','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_Redirect_Altan-RN'){
                            array_push($data['consultUF']['freeUnits']['extra'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Navegación en Portal Cautivo','description'=>'MB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }else if($freeUnits[$i]['name'] == 'FU_ThrMBB_Altan-RN_512kbps'){
                            array_push($data['consultUF']['freeUnits']['extra'],array(
                                'totalAmt'=>$totalAmt/1024,'unusedAmt'=>$unusedAmt/1024,'freePercentage'=>$percentageFree,'name'=>'Velocidad Reducida','description'=>'MB','effectiveDate'=>$effectiveDate,'expireDate'=>$expireDate
                            ));
    
                        }
                    }
                }
                if($data['consultUF']['offerID'] == 0){
                    $data['consultUF']['rate'] = 'PLAN NO CONTRATADO';    
                }else{
                    // return $data['consultUF']['offerID'];
                    $rateData = DB::connection('mysql')->table('offers')->where('offerID',$offer)->first();
                    $data['consultUF']['rate'] = $rateData->name_second;
                }

            }
            return $data;
        }
    }

    public function formatDateConsultUF($date){
        $year = substr($date,0,4);
        $month = substr($date,4,2);
        $day = substr($date,6,2);
        $hour = substr($date,8,2);
        $minute = substr($date,10,2);
        $second = substr($date,12,2);
        $date = $day.'-'.$month.'-'.$year.' '.$hour.':'.$minute.':'.$second;
        return $date;
    }
}
