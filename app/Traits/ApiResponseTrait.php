<?php

namespace App\Traits;

trait ApiResponseTrait
{

    protected function success($data = null, string $message = 'Opération réussie', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }


//    public function success($data = null, $meta = [], $code = 200)
//    {
//        return response()->json([
//            'success' => true,
//            'data' => $data,
//            'pagination' => $meta['pagination'] ?? null,
//            'links' => $meta['links'] ?? null,
//            'message' => $meta['message'] ?? null,
//        ], $code);
//    }

    protected function error(string $message = 'Error', int $status = 500)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }



//    protected function success($data, $pagination = null)
//    {
//        $response = ['success' => true, 'data' => $data];
//
//        if ($pagination) {
//            $response['pagination'] = $pagination['pagination'];
//            $response['links'] = $pagination['links'];
//        }
//
//        return response()->json($response);
//
}
