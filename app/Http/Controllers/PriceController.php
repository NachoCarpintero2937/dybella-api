<?php

namespace App\Http\Controllers;

use App\Models\Price;
use App\Services\ApiService;
use Error;
use Exception;
use Illuminate\Http\Request;

class PriceController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->middleware('auth:api');
    }

    public function index()
    {
        $data = [];
        try {
            $prices = Price::all();
            $data = ['prices' => $prices];
            return $this->apiService->sendResponse($data, '', 200, true);
        } catch (\Exception $e) {
            $message =  $e->getMessage();
            return $this->apiService->sendResponse($data, $message, 400, false);
        }
    }
    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'price' => 'required|numeric',
                'user_id' => 'required|exists:users,id',
                'description' => 'nullable|string',
            ]);
            return  Price::createPrice($validatedData);
        } catch (\Exception $e) {
            return new Error($e->getMessage());
        }
    }

    public function update(Request $request)
    {
        $data = [];
        $price = Price::find($request->id);

        if (!$request->id) {
            return $this->apiService->sendResponse([], 'El id del precio es requerido', 400, false);
        }
        if (!$price) {
            return $this->apiService->sendResponse([], 'El precio no fue encontrado', 404, false);
        }

        try {
            $validatedData = $request->validate([
                'price' => 'required|numeric',
                'user_id' => 'required|exists:users,id',
                'description' => 'nullable|string',
            ]);
            $priceUp = $price->updatePrice($validatedData);
            $data = [
                'price' => $priceUp
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
                return $this->apiService->sendResponse([], 'El id del precio es requerido', 400, false);
            }

            $price = Price::find($request->id);

            if (!$price) {
                return $this->apiService->sendResponse([], 'El precio no fue encontrado', 404, false);
            }
            $price->deleteClient();
            return $this->apiService->sendResponse([], 'Precio eliminado con éxito', 200, true);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->apiService->sendResponse([], $message, 400, false);
        }
    }
}
