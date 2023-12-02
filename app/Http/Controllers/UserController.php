<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use App\Services\ApiService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index()
    {
        $data = [];
        try {
            $users = User::all();
            $data = ['users' => $users];
            return $this->apiService->sendResponse($data, '', 200, true);
        } catch (\Exception $e) {
            $message =  $e->getMessage();
            return $this->apiService->sendResponse($data, $message, 400, false);
        }
    }

    public function usersToShift(Request $request)
    {
        $data = [];
        try {
            $query = User::with(['shifts.service.price', 'shifts.client']);
            if ($request->id) {
                $query->find($request->id);
            }
    
            if ($request->start_date && $request->end_date) {
                // Filtra los turnos en un rango de fechas
                $startDate = $request->start_date;
                $endDate = $request->end_date;
                $query->with(['shifts' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date_shift', [$startDate, $endDate]);
                }]);
            }
    
            $users = $query->get();
    
            // Establece una relación shifts vacía si no se encontraron registros
            $users->each(function ($user) {
                $user->setRelation('shifts', $user->shifts ?? collect());
            });
    
            // Agregar un contador de citas y clientes para cada usuario
            $users->each(function ($user) {
                $user->shifts_count = $user->shifts->count();
                $user->clients_count = $user->shifts->pluck('client_id')->unique()->count();
            });
    
            // Obtener los servicios a prestar por rango de fecha
            $servicesToPrest = $users->flatMap(function ($user) use ($startDate, $endDate) {
                return $user->shifts->whereBetween('date_shift', [$startDate, $endDate])->pluck('service_id')->unique();
            });
    
            // Obtener la cantidad de servicios únicos
            $allServicesCount = $servicesToPrest->count();
    
            // Obtener los objetos completos de los servicios a través de la relación en Shift
            $serviceObjects = Service::with('price')->whereIn('id', $servicesToPrest)->get();
    
            // Agregar la cuenta de turnos para cada servicio en today_service
            $serviceObjects->each(function ($service) use ($users, $startDate, $endDate) {
                $service->count = $users->flatMap->shifts
                    ->where('service_id', $service->id)
                    ->whereBetween('date_shift', [$startDate, $endDate])
                    ->count();
            });
    
            $data = [
                'users' => $users,
                'all_shifts_count' => $users->flatMap->shifts->count(),
                'all_clients_count' => $users->flatMap->shifts->pluck('client_id')->unique()->count(),
                'all_services_count' => $allServicesCount,
                'today_service' => $serviceObjects,
            ];
    
            return $this->apiService->sendResponse($data, '', 200, true);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->apiService->sendResponse($data, $message, 400, false);
        }
    }

    public function create(Request $request)
    {
        $data = [];
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);

            $user = User::createUser($validatedData);
            $data = ['user' => $user];
            return $this->apiService->sendResponse($data, '', 200, true);
        } catch (\Exception $e) {
            $message =  $e->getMessage();
            return $this->apiService->sendResponse($data, $message, 400, false);
        }
    }
}
