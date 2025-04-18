<?php

namespace App\Http\Controllers;

abstract class Controller
{
/**
 * Return a BadRequest Status Code and appropriate errrors
 * @param mixed $validator
 *@return mixed Illuminate\Http\JsonResponse
 */

    public function BadRequest($validator, $message = "Request Didn't pass the Validation!."){
        return response()->json([
            "ok" => false,
            "errors" => $validator->errors(),
            "message" => $message
        ],400);
    }

/**
 * Return a Created Status Code and Appropreate message.
 * @param mixed $data
 * @param mixed $message
 * @return mixed|\Illuminate\Http\JsonResponse
 */
    public function Created($data = [], $message = "Created!"){
        return response()->json([
            "ok" => true,
            "data" => $data,
            "message" => $message
        ],201);
    }

 /**
 * Return a Unauthorized Status Code and Appropreate message.
 * @param mixed $data
 * @param mixed $message
 * @return mixed|\Illuminate\Http\JsonResponse
 */
    public function Unauthorized( $message = "Unauthorized!"){
        return response()->json([
            "ok" => false,
            "message" => $message
        ],401);
    }

/**
 * Return of Ok Response!
 * @param mixed $data
 * @param mixed $message
 * @return mixed|\Illuminate\Http\JsonResponse
 */
    public function Ok($data = [], $message = "Ok!"){
        return response()->json([
            "ok" => true,
            "data" => $data,
            "message" => $message
        ],200);
    }
}
