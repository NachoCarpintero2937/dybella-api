<?php

namespace App\Http\Controllers;

use App\Mail\BirthdayGreetings;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Services\ApiService;
use Carbon\Carbon;
use Error;
use Illuminate\Support\Facades\Mail;

class ClientController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->middleware('auth:api', ['except' => ['create', 'getBirthdayClient']]);
    }

    public function index(Request $request)
    {
        $data = [];
        try {
            $clients = Client::with('shifts')->get();

            // Filtro por id
            if ($request->has('id')) {
                $clients = $clients->where('id', $request->id);
            }

            // Filtro por fecha de cumpleaños
            if ($request->has('date_birthday')) {
                $clients = $clients->filter(function ($client) use ($request) {
                    $birthday = Carbon::parse($client->date_birthday);
                    return $this->hasBirthdayToday($client->date_birthday) && $birthday->year !== Carbon::now()->year;
                });
            }

            // Agregar el indicador de turno para cada cliente
            $clients->transform(function ($client) {
                $client->shift = $client->shifts->isNotEmpty(); // Indica si el cliente tiene turnos
                unset($client->shifts); // No necesitamos los detalles de los turnos aquí
                return $client;
            });

            // Ordenar por nombre
            $clients = $clients->sortBy('name');

            $data = ['clients' => $clients->values()->toArray()];
            return $this->apiService->sendResponse($data, '', 200, true);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->apiService->sendResponse($data, $message, 400, false);
        }
    }

    private function hasBirthdayToday($birthday)
    {
        $birthday = Carbon::parse($birthday);

        // Obtener solo el día y el mes de la fecha de cumpleaños
        $birthdayWithoutYear = $birthday->setYear(Carbon::now()->year);

        // Comparar solo el día y el mes sin tener en cuenta el año
        return $birthdayWithoutYear->isSameDay(Carbon::now());
    }
    public function create(Request $request)
    {
        $data = [];
        try {
            $validatedData = $request->validate(
                [
                    'name' => 'required|string|max:255',
                    'email' => 'nullable|email',
                    'cod_area' => 'required',
                    'phone' => 'required|unique:clients,phone',
                    'date_birthday' => 'nullable|date',
                ],
                [
                    'phone.unique' => 'Este cliente ya se encuentra registrado',
                ]
            );

            $client = Client::createClient($validatedData);
            $data = ['client' => $client];
            return $this->apiService->sendResponse($data, '', 200, true);
        } catch (\Illuminate\Database\QueryException $e) {
            $message = $e->getMessage();
            return $this->apiService->sendResponse($data, $message, 400, false);
        }
    }


    public function update(Request $request)
    {
        $data = [];

        if (!$request->id) {
            return $this->apiService->sendResponse([], 'El id del cliente es requerido', 400, false);
        }

        $client = Client::find($request->id);

        if (!$client) {
            return $this->apiService->sendResponse([], 'El cliente no fue encontrado', 404, false);
        }

        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email',
                'cod_area' => 'required',
                'phone' => 'required',
                'date_birthday' => 'nullable|date',
            ]);
            $clientUp = $client->updateClient($validatedData);
            $data = [
                'client' => $clientUp
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
                return $this->apiService->sendResponse([], 'El id del cliente es requerido', 400, false);
            }

            $client = Client::find($request->id);

            if (!$client) {
                return $this->apiService->sendResponse([], 'El cliente no fue encontrado', 404, false);
            }

            // Intenta eliminar el cliente
            $client->deleteClient();

            return $this->apiService->sendResponse([], 'Cliente eliminado con éxito', 200, true);
        } catch (\Illuminate\Database\QueryException $e) {
            // Error de integridad de la base de datos
            if ($e->errorInfo[1] === 1451) {
                return $this->apiService->sendResponse([], 'No se puede eliminar el cliente porque tiene turnos asignados', 400, false);
            } else {
                // Otro tipo de error de base de datos
                $message = $e->getMessage();
                return $this->apiService->sendResponse([], $message, 400, false);
            }
        } catch (\Exception $e) {
            // Otros tipos de excepciones
            $message = $e->getMessage();
            return $this->apiService->sendResponse([], $message, 400, false);
        }
    }

    public function getBirthdayClient()
    {
        $currentDate = Carbon::now();

        $clients = Client::whereMonth('date_birthday', $currentDate->month)
            ->whereDay('date_birthday', $currentDate->day)
            ->get();
        foreach ($clients as $client) {
            if ($client->email)
                Mail::to($client->email)->send(new BirthdayGreetings($client));
        }
    }
}
