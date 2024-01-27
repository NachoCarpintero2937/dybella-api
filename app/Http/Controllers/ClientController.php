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
            // Filtrar solo clientes con status igual a 0
            $clients = Client::where('status', 0);

            // Filtro por id
            if ($request->has('id')) {
                $clients = $clients->where('id', $request->id);
            }

            // Filtro por fecha de cumpleaños (día y mes, excluyendo el año actual)
            if ($request->has('date_birthday')) {
                $day = Carbon::parse($request->date_birthday)->day;
                $month = Carbon::parse($request->date_birthday)->month;
                $year = Carbon::now()->year;

                $clients = $clients->whereDay('date_birthday', $day)
                    ->whereMonth('date_birthday', $month)
                    ->whereYear('date_birthday', '!=', $year);
            }

            // Ordenar por nombre
            $clients = $clients->orderBy('name')->get();

            $data = ['clients' => $clients->toArray()];
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
            // Agregar el valor del estado (status) al conjunto de datos validados
            $validatedData['status'] = 0;
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

            // Intenta realizar el borrado lógico
            $client->softDeleteClient();

            return $this->apiService->sendResponse([], 'Cliente eliminado con éxito', 200, true);
        } catch (\Exception $e) {
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
