<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class OrderController extends Controller
{
    /**
     * Ordenes - index
     * Listado de ordenes
     * @urlParam  id required Id de la orden a consultar
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        try{
            $order = Order::where('id', $id)->first();

            if ( !$order ) {
                return response()->json([
                    "success" => false,
                    "message" => "La orden no existe",
                ], 404 );
            }

            if ($this->clientValidation($order)) {
                return response()->json([
                    "success" => false,
                    "message" => "No tiene acceso a esta orden",
                ], 400 );
            }

            $data = [
                'Estado' => $order->status->name,
                'Cliente' => $order->user->name,
                'Origen' => $order->originAddress->city,
                'Destino' => $order->arrivalAddress->city,
                'Valor del envio' => "$".number_format($order->value,2)
            ];

            return response()->json([
                "success" => true,
                "message" => "Esta es la orden con este id",
                "data" => $data,
            ], 200 );
        }catch( Throwable $e ){
            DB::rollback();

            return response()->json([
                "success" => false,
                "message" => "Ocurrio un error",
            ], 400 );
        }
    }

    /**
     * Ordenes - Creación
     * Crea un nueva Orden.
     * @param  \Illuminate\Http\Request  $request
     * @bodyParam origin_address array required array con la información de la dirección de origen
     * @bodyParam origin_address.latitude string numeric latitud de la coordenada de origen
     * @bodyParam origin_address.logitude string numeric logitud de la coordenada de origen
     * @bodyParam origin_address.city string required ciudad
     * @bodyParam origin_address.road_type string required Tipo de via
     * @bodyParam origin_address.road_name string required Nombre de la via
     * @bodyParam origin_address.road_identifier string Identificador de la via
     * @bodyParam origin_address.second_way_number integer required Número de la segunda via
     * @bodyParam origin_address.second_way_identifier string Identificador de la segunda via
     * @bodyParam origin_address.House_number string required Número de la casa
     * @bodyParam origin_address.house_number_identifier string identificador de la casa
     * @bodyParam origin_address.comments string Otras instrucciones

     * @bodyParam arrival_address array required array con la información de la dirección de destino
     * @bodyParam arrival_address.latitude string numeric latitud de la coordenada de destino
     * @bodyParam origin_address.logitude string numeric logitud de la coordenada de destino
     * @bodyParam arrival_address.city string required ciudad
     * @bodyParam arrival_address.road_type string required Tipo de via
     * @bodyParam arrival_address.road_name string required Nombre de la via
     * @bodyParam arrival_address.road_identifier string Identificador de la via
     * @bodyParam arrival_address.second_way_number integer required Número de la segunda via
     * @bodyParam arrival_address.second_way_identifier string Identificador de la segunda via
     * @bodyParam arrival_address.House_number string require Número de la casad
     * @bodyParam arrival_address.house_number_identifier string identificador de la casa
     * @bodyParam arrival_address.comments string Otras instrucciones

     * @bodyParam product_amount integer required Cantidad de productos 
     * @bodyParam product_weight decimal required Peso de los productos 
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        try{
            $validate = Validator::make($request->all(), [
                'origin_address' => 'required|array',
                'arrival_address' => 'required|array',
                'product_amount' => 'required',
                'product_weight' => 'required'
            ]);
            if( $validate->fails() ){

                $errors = $validate->errors();
                
                return response()->json([
                    "success" => false,
                    "message" => "Existen errores en los datos enviados",
                    "errors" => $errors,
                ], 400 );  
            }

            if ($request->product_weight > 25) {
                return response()->json([
                    "success" => false,
                    "message" => "El peso del envio excede el servicio estándar, deberá comunicarse con la empresa para realizar un convenio especial.",
                ], 400 ); 
            }

            $validatorOriginAddress = Validator::make($request->input('origin_address'), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'city' => 'required',
                'road_type' => 'required',
                'road_name' => 'required',
                'second_way_number' => 'required',
                'House_number' => 'required',
            ]);
            if ($validatorOriginAddress->fails()) {
                $errors = $validatorOriginAddress->errors();
                
                return response()->json([
                    "success" => false,
                    "message" => "Existen errores en los datos enviados",
                    "errors" => $errors,
                ], 400 ); 
            }

            $validatorArrivalAddress = Validator::make($request->input('arrival_address'), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|min:-180|max:180',
                'city' => 'required',
                'road_type' => 'required',
                'road_name' => 'required',
                'second_way_number' => 'required',
                'House_number' => 'required',
            ]);
            if ($validatorArrivalAddress->fails()) {
                $errors = $validatorArrivalAddress->errors();
                
                return response()->json([
                    "success" => false,
                    "message" => "Existen errores en los datos enviados",
                    "errors" => $errors,
                ], 400 ); 
            }
        
            DB::beginTransaction(); 
            $originAddress = new Address;
            $originAddress = $originAddress->create( $request->input('origin_address') );

            $arrivalAddress = new Address;
            $arrivalAddress = $arrivalAddress->create( $request->input('arrival_address') );

            if( $originAddress && $arrivalAddress){

                $orderData = $request->all();
                $orderData['user_id'] = auth()->user()->id;
                $orderData['status_id'] = 1;
                $orderData['origin_address_id'] = $originAddress->id;
                $orderData['arrival_address_id'] = $arrivalAddress->id;
                $orderData['value'] = $this->simulate($request, true);
                $orderNew = new Order;
                $orderNew = $orderNew->create( $orderData );
                if (  $orderNew ) {
                    DB::commit();
                    $data = [
                        'Estado' => $orderNew->status->name,
                        'Cliente' => $orderNew->user->name,
                        'Origen' => $orderNew->originAddress->city,
                        'Destino' => $orderNew->arrivalAddress->city,
                        'Valor del envio' => "$".number_format($orderNew->value,2)
                    ];
                    return response()->json([
                        "success" => true,
                        "message" => "Orden creado con exito",
                        "data" => $data,
                    ], 200 ); 
                }else{
                    DB::rollback();

                    return response()->json([
                        "success" => false,
                        "message" => "Ocurrio un error",
                    ], 400 );
                }
            }
            else{
                DB::rollback();

                return response()->json([
                    "success" => false,
                    "message" => "Ocurrio un error",
                ], 400 );
            }
        }catch( Throwable $e ){
            DB::rollback();

            return response()->json([
                "success" => false,
                "message" => "Ocurrio un error",
            ], 400 );
        }
    }

    /**
     * Ordenes - Actualización
     * Actualiza una orden.
     * @param  \Illuminate\Http\Request  $request
     * @urlParam  id required Id de la orden a actualizar
     * @bodyParam status_id array integer Id del estado que desea actualizar
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $validate = Validator::make($request->all(), [
                'status_id' => 'required|numeric|exists:statuses,id',
            ]);
            if( $validate->fails() ){

                $errors = $validate->errors();
                
                return response()->json([
                    "success" => false,
                    "message" => "Existen errores en los datos enviados",
                    "errors" => $errors,
                ], 400 );  
            }

            $orderUpdate = Order::where('id', $id)->first();

            if (!$orderUpdate) {
                return response()->json([
                    "success" => false,
                    "message" => "La orden no existe",
                ], 404 );
            }

            if ($request->status_id == 6 ) {
                return response()->json([
                    "success" => false,
                    "message" => "Para este cambio de estado es necesario utilizar el servicio de cancelación",
                ], 400 ); 
            }
            
            DB::beginTransaction();

            if (  $orderUpdate->update( $request->all() ) ) {
                DB::commit();
                $data = [
                    'Estado' => $orderUpdate->status->name,
                    'Cliente' => $orderUpdate->user->name
                ];
                return response()->json([
                    "success" => true,
                    "message" => "Orden actualizado con exito",
                    "data" => $data,
                ], 200 ); 
            }else{
                DB::rollback();

                return response()->json([
                    "success" => false,
                    "message" => "Ocurrio un error",
                ], 400 );
            }
        }catch( Throwable $e ){
            DB::rollback();

            return response()->json([
                "success" => false,
                "message" => "Ocurrio un error",
            ], 400 );
        }
    }

    /**
     * Ordenes - Cancelación
     * Cancela una Orden.
     * @param  \Illuminate\Http\Request  $request
     * @urlParam  id required Id de la orden a cancelar
     * @return \Illuminate\Http\Response
     */
    public function cancel(Request $request, $id)
    {
        try{
            $orderCancel = Order::where('id', $id)->first();

            if (!$orderCancel) {
                return response()->json([
                    "success" => false,
                    "message" => "La orden no existe",
                ], 404 );
            }

            if ($this->clientValidation($orderCancel)) {
                return response()->json([
                    "success" => false,
                    "message" => "No tiene acceso a esta orden",
                ], 400 );
            }

            if ($orderCancel->status_id == 6  ) {
                return response()->json([
                    "success" => false,
                    "message" => "La entrega ya fue cancelada",
                ], 400 ); 
            }

            if ($orderCancel->status_id == 4 || $orderCancel->status_id == 5) {
                return response()->json([
                    "success" => false,
                    "message" => "La entrega se encuentra en el tramo final y no puede ser cancelada",
                ], 400 ); 
            }

            $creationDate =  new Carbon( $orderCancel['created_at'] );
            $creationDate =  $creationDate->format('Y-m-d h:i:s');
            $creationDate = Carbon::parse($creationDate);

            $todayDate =  Carbon::now()->format('Y-m-d h:i:s');
            $todayDate = Carbon::parse($todayDate);

            $minutesDiff = $creationDate->diffInMinutes($todayDate);

            if ($minutesDiff < 2) {
                $dataCancellation['devolution'] = 1;
            }
            $note = isset($request['devolution']) ? 'El reembolso sera aplicado de manera adecuada' : 'El reembolso no sera aplicado porque supero el tiempo limite';

            $dataCancellation['status_id'] = 6;
            
            DB::beginTransaction();

            if (  $orderCancel->update( $dataCancellation ) ) {
                DB::commit();
                $data = [
                    'Estado' => $orderCancel->status->name,
                    'Cliente' => $orderCancel->user->name,
                    'Nota' => $note
                ];
                return response()->json([
                    "success" => true,
                    "message" => "Orden creado con exito",
                    "data" => $data,
                ], 200 ); 
            }else{
                DB::rollback();

                return response()->json([
                    "success" => false,
                    "message" => "Ocurrio un error",
                ], 400 );
            }
        }catch( Throwable $e ){
            DB::rollback();

            return response()->json([
                "success" => false,
                "message" => "Ocurrio un error",
            ], 400 );
        }
    }

    public function simulate(Request $request, $internParam = false)
    {
        try {
            $validate = Validator::make($request->all(), [
                'origin_address' => 'required|array',
                'arrival_address' => 'required|array',
                'product_amount' => 'required',
                'product_weight' => 'required'
            ]);
            if( $validate->fails() ){

                $errors = $validate->errors();
                
                return response()->json([
                    "success" => false,
                    "message" => "Existen errores en los datos enviados",
                    "errors" => $errors,
                ], 400 );  
            }

            if ($request->product_weight > 25) {
                return response()->json([
                    "success" => false,
                    "message" => "El peso del envio excede el servicio estándar, deberá comunicarse con la empresa para realizar un convenio especial.",
                ], 400 ); 
            }

            $validatorOriginAddress = Validator::make($request->input('origin_address'), [
                'latitude' => 'required|min:-90|max:90',
                'longitude' => 'required|numeric|between:-180,180',
                'city' => 'required',
                'road_type' => 'required',
                'road_name' => 'required',
                'second_way_number' => 'required',
                'House_number' => 'required',
            ]);
            if ($validatorOriginAddress->fails()) {
                $errors = $validatorOriginAddress->errors();
                
                return response()->json([
                    "success" => false,
                    "message" => "Existen errores en los datos enviados",
                    "errors" => $errors,
                ], 400 ); 
            }

            $validatorArrivalAddress = Validator::make($request->input('arrival_address'), [
                'latitude' => 'required|min:-90|max:90',
                'longitude' => 'required|min:-180|max:180',
                'city' => 'required',
                'road_type' => 'required',
                'road_name' => 'required',
                'second_way_number' => 'required',
                'House_number' => 'required',
            ]);
            if ($validatorArrivalAddress->fails()) {
                $errors = $validatorArrivalAddress->errors();
                
                return response()->json([
                    "success" => false,
                    "message" => "Existen errores en los datos enviados",
                    "errors" => $errors,
                ], 400 ); 
            }

            // calculate distance
            $originAddress = $request->input('origin_address');
            $arrivalAddress = $request->input('arrival_address');

            $theta = $originAddress['longitude'] - $arrivalAddress['longitude'];
            $distance = sin(deg2rad($originAddress['latitude'])) * sin(deg2rad($arrivalAddress['latitude'])) +  cos(deg2rad($originAddress['latitude'])) * cos(deg2rad($arrivalAddress['latitude'])) * cos(deg2rad($theta));
            $distance = acos($distance);
            $distance = rad2deg($distance);
            $distance = $distance * 60 * 1.1515 * 1.609344;

            // calculate package type
            if ($request['product_weight'] <= 5) {
                $packageType = 'S';
            }else if($request['product_weight'] > 5 && $request['product_weight'] <= 15){
                $packageType = 'M';
            }elseif ($request['product_weight'] > 15 && $request['product_weight'] <= 25) {
                $packageType = 'L';
            }

            // asignamos valor del kilometro
            switch ( $packageType ) {
                case 'S':
                    $valuePerDistance = 100; 
                    break;
                case 'M':
                    $valuePerDistance = 300; 
                    break;
                case 'L':
                    $valuePerDistance = 500; 
                    break;
            }

            // asignamos el coeficiente de acuerdo a la distancia
            if ($distance <= 50) {
               $coeficientPerDistance = 1;
            }elseif ($distance > 50 && $distance <= 100) {
               $coeficientPerDistance = 0.8;
            }elseif ($distance > 100 && $distance <= 200) {
               $coeficientPerDistance = 0.6;
            }
            $orderValue = $distance*$valuePerDistance*$coeficientPerDistance;

            if ( $internParam ) {
                return $orderValue; 
            }else{
                return response()->json([
                    "success" => true,
                    "message" => "Valor total del envio",
                    "data" => "$".number_format($orderValue, 2),
                ], 200 );
            }
        }catch( Throwable $e ){
            return response()->json([
                "success" => false,
                "message" => "Ocurrio un error",
            ], 400 );
        }
    }

    public function clientValidation($order)
    {
        $user = Auth()->user();
        if ($user->rol_id != 1) {
            if ($order->user_id != $user->id ) {
                return true;
            }
        }
    }
}
