<?php

namespace App\Traits;

trait ApiResponseTrait
{

    protected function success($data = null, string $message = 'Opération réussie', int $code = 200)
    {
        // Si les données contiennent 'items', 'pagination' et 'links', c'est une réponse paginée
        if (is_array($data) && isset($data['items'], $data['pagination'], $data['links'])) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data['items'],
                'pagination' => $data['pagination'],
                'links' => $data['links'],
            ], $code);
        }

        // Réponse normale
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }




    protected function error(string $message = 'Error', int $status = 500)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }




}
