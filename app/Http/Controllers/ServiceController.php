<?php

namespace App\Http\Controllers;

use App\Models\Price;
use App\Models\Service;
use App\Models\Shift;
use Exception;
use Illuminate\Http\Request;
use App\Services\ApiService;
use Carbon\Carbon;
use Error;
use Illuminate\Support\Facades\Hash;

class ServiceController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->middleware('auth:api');
    }
    public function index(Request $request)
    {

        try {
            $query = Service::with(['user', 'price']);

            //for id
            if ($request->has('id')) {
                $query->where('id', $request->id)->get();
            }

            $data = $query->get();
            $statusCode = 200;
            $data = [
                'services' => $data
            ];
            return $this->apiService->sendResponse($data, '', $statusCode, true);
        } catch (Exception $e) {
            $message =  $e->getMessage();
            return $this->apiService->sendResponse($data, $message, 400, false);
        }
    }

    public function create(Request $request)
    {
        $data = [];
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|string|max:255',
                'user_id' => 'required|exists:users,id',
            ]);

            $priceData = [
                'price' => $validatedData['price'],
                'user_id' => $validatedData['user_id']
            ];
            if (!$request->price_id) {
                $price = Price::create($priceData);
                $validatedData['price_id'] = $price->id;
            }
            $service =  Service::createService($validatedData);
            $data = [
                'service' => $service
            ];
            return $this->apiService->sendResponse($data, '', 200, true);
        } catch (\Exception $e) {
            $message =  $e->getMessage();
            return $this->apiService->sendResponse($data, $message, 400, false);
        }
    }

    public function update(Request $request)
    {
        $data = [];

        if (!$request->id) {
            return $this->apiService->sendResponse([], 'El id del servicio es requerido', 400, false);
        }

        $service = Service::find($request->id);
        if (!$service) {
            return $this->apiService->sendResponse([], 'El servicio no fue encontrado', 404, false);
        }

        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|string|max:255',
                'price_id' => 'required|exists:prices,id',
                'user_id' => 'required|exists:users,id',
            ]);

            // Obtener el precio existente
            $existingPrice = Price::find($validatedData['price_id']);

            if (!$existingPrice) {
                return $this->apiService->sendResponse([], 'El precio no fue encontrado', 404, false);
            }

            // Actualizar la columna "price" en la tabla prices
            $existingPrice->update(['price' => $validatedData['price']]);

            // Actualizar el servicio
            $service->update($validatedData);

            // Actualizar la columna "price" en la tabla shifts para los turnos posteriores con status igual a 0
            $shiftsToUpdate = Shift::where('service_id', $service->id)
                ->where('date_shift', '>', Carbon::now()) // Turnos posteriores a la fecha actual
                ->where('status', 0) // Status igual a 0
                ->get();

            foreach ($shiftsToUpdate as $shift) {
                $shift->update(['price' => $validatedData['price']]);
            }

            $data = [
                'service' => $service
            ];

            return $this->apiService->sendResponse($data, '', 200, true);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->apiService->sendResponse([], $message, 400, false);
        }
    }

    public function destroy(Request $request)
    {
        try {

            if (!$request->id) {
                return $this->apiService->sendResponse([], 'El id del servicio es requerido', 400, false);
            }

            $service = Service::find($request->id);
            if (!$service) {
                return $this->apiService->sendResponse([], 'El cliente no fue encontrado', 404, false);
            }

            $service->deleteService();
            return $this->apiService->sendResponse([], 'Cliente eliminado con éxito', 200, true);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->apiService->sendResponse([], $message, 400, false);
        }
    }
}
