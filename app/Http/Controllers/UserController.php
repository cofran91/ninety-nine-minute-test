<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class UserController extends Controller
{
    /**
     * Usuarios - index
     * Listado de usuarios
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users =  User::with('rol')->get();

        return response()->json([
            "success" => true,
            "message" => "todos los usuarios",
            "data" => $users,
        ], 200 );
    }

    /**
     * Usuarios - Creación
     * Crea un nuevo usuario.
     * @param  \Illuminate\Http\Request  $request
     * @bodyParam name text required Nombre del usuario
     * @bodyParam email email required Email del usuario
     * @bodyParam password password required password del usuario
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        $validate = Validator::make( $request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'rol_id' => 'nullable|numeric|exists:rols,id',
        ]);

        if( $validate->fails() ){

            $errors = $validate->errors();
            
            return response()->json([
                "success" => false,
                "message" => "Existen errores en los datos enviados",
                "errors" => $errors,
            ], 400 );  
        }
        else{
            try{
                DB::beginTransaction();

                $userNew = new User;
                $userNew = $userNew->create( $request->all() );

                if( $userNew ){

                    DB::commit();

                    return response()->json([
                        "success" => true,
                        "message" => "Usuario creado con exito",
                        "data" => $userNew->load('rol'),
                    ], 200 ); 
                }
                else{
                    DB::rollback();

                    return response()->json([
                        "success" => false,
                        "message" => "Ocurrio un error",
                    ], 400 );
                }
            }
            catch( Throwable $e ){
                DB::rollback();

                return response()->json([
                    "success" => false,
                    "message" => "Ocurrio un error",
                ], 400 );
            }
        }
    }

    /**
     * Usuarios - Actualización
     * Actualizar un usuario.
     * @param  \Illuminate\Http\Request $request
     * @urlParam  id required Id del usuario a actualizar
     * @bodyParam name text required Nombre del usuario
     * @bodyParam email email required Email del usuario
     * @bodyParam password password required password del usuario
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validate = Validator::make( $request->all(), [
            'name' => 'required',
            'email' => [
                'required', 
                'email',
                Rule::unique('users')->ignore($id),
            ],
            'password' => 'sometimes|min:6',
            'rol_id' => 'sometimes|numeric|exists:rols,id',
        ]);

        if( $validate->fails() ){

            $errors = $validate->errors();

            return response()->json([
                "success" => false,
                "message" => "Existen errores en los datos enviados",
                "errors" => $errors,
            ], 400 ); 
        }
        else{
            try{
                DB::beginTransaction();

                $userUpdate = User::where('id', $id)->first();

                if (!$userUpdate) {
                    return response()->json([
                        "success" => false,
                        "message" => "El usuario no existe",
                    ], 404 );
                }

                if( $userUpdate->update( $request->all() ) ){

                    DB::commit();

                    return response()->json([
                        "success" => true,
                        "message" => "usuario actualizado con exito",
                        "data" => $userUpdate,
                    ], 200 ); 
                }
                else{
                    DB::rollback();

                    return response()->json([
                        "success" => false,
                        "message" => "Ocurrio un error",
                    ], 400 );
                }
            }
            catch( Throwable $e ){

                DB::rollback();

                return response()->json([
                    "success" => false,
                    "message" => "Ocurrio un error",
                ], 400 );
            }
        }
    }

    /**
     * Usuarios - Eliminación
     * Eliminar un usuario.
     * @urlParam  id required Id del usuario a eliminar
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        try{
            DB::beginTransaction();

            $userDelete = User::where('id', $id)->first();

            if (!$userDelete) {
                return response()->json([
                    "success" => false,
                    "message" => "El usuario no existe",
                ], 404 );
            }

            if( $userDelete->delete() ){

                DB::commit();
                return response()->json([
                    "success" => true,
                    "message" => "Usuario eliminado con exito",
                ], 200 );
            }
            else{
                DB::rollback();

                $validate->errors()->add( 'Ocurrio un error', "Dato incorrecto" );
                $errors = $validate->errors();

                return response()->json([
                    "success" => false,
                    "message" => "Ocurrio un error",
                ], 400 );
            }
        }
        catch( Throwable $e ){

            DB::rollback();

            return response()->json([
                "success" => false,
                "message" => "Ocurrio un error",
            ], 400 );
        }
    }
}
