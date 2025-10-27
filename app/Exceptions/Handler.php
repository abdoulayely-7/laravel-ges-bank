<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // Gérer les modèles non trouvés (ex: route model binding)
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            $ids = $e->getIds();
            $id = is_array($ids) ? implode(', ', $ids) : $ids;

            throw new NotFoundException($model, $id);
        }

        // Si c’est déjà une ApiException (comme NotFoundException)
        if ($e instanceof ApiException) {
            return $e->render();
        }

        return parent::render($request, $e);
    }
}
