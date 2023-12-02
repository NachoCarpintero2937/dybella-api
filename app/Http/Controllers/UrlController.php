<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Services\ApiService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use PSpell\Config;

class UrlController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->middleware('auth:api', ['except' => ['index']]);
    }
    public function index(Request $request)
    {
        try {
            $url = Url::findOrFail($request->id);

            // Verifica si el estado es 1 (consumido)
            if ($url->status == 1) {
                throw new Exception("URL INEXISTENTE");
            }

            // Verifica si han pasado más de 30 minutos desde la creación de la URL
            $createdDateTime = Carbon::parse($url->created_at);
            $currentDateTime = Carbon::now();

            if ($currentDateTime->diffInMinutes($createdDateTime) > 30) {
                throw new Exception("Url expirada");
            }

            // Aquí asumo que $this->apiService es una instancia de tu servicio API
            $statusCode = 200;
            return $this->apiService->sendResponse($url, 'URL válida.', $statusCode, true);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $statusCode = 400;
            return $this->apiService->sendResponse([], $message, $statusCode, false);
        }
    }

    public function create(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $urlBase = env('APP_BASE_URL');
            $data = [
                'url' => $urlBase,
                'status' => 0, // Establece automáticamente el status en 0
                'user_id' => $request->user_id,
            ];

            $url =  Url::create($data);

            // Aquí asumo que $this->apiService es una instancia de tu servicio API
            $statusCode = 200;
            return $this->apiService->sendResponse($url, '', $statusCode, true);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $statusCode = 400;
            return $this->apiService->sendResponse([], $message, $statusCode, false);
        }
    }

    public function update(Request $request)
    {
        try {
            // Valida que el usuario y el enlace existan antes de continuar
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            // Verifica que se haya proporcionado un ID en la solicitud
            if (!$request->id) {
                throw new Exception("Se requiere un ID válido para la actualización.");
            }

            $url = Url::findOrFail($request->id);

            // Asegúrate de que el enlace pertenezca al usuario proporcionado
            if ($url->user_id != $request->user_id) {
                throw new Exception("El enlace no pertenece al usuario especificado.");
            }

            // Actualiza el estado del enlace a desactivado (status = 1)
            $url->update(['status' => 1]);

            // Aquí asumo que $this->apiService es una instancia de tu servicio API
            $statusCode = 200;
            return $this->apiService->sendResponse($url, 'Enlace desactivado correctamente.', $statusCode, true);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $statusCode = 400;
            return $this->apiService->sendResponse([], $message, $statusCode, false);
        }
    }
}
