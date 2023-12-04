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
            $clients = Client::query();

            // Filter by id
            if ($request->has('id')) {
                $clients->where('id', $request->id);
            }

            // Filter by date_birthday (cumpleaños)
            if ($request->has('date_birthday')) {
                $dateBirthday = Carbon::parse($request->date_birthday);

                // Filtrar por mes y día de cumpleaños
                $clients->whereMonth('date_birthday', $dateBirthday->month)
                    ->whereDay('date_birthday', $dateBirthday->day);
            }

            $clients = $clients->get();

            $data = ['clients' => $clients->values()->toArray()];
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
                'email' => 'nullable|email',
                'cod_area' => 'required',
                'phone' => 'required',
                'date_birthday' => 'nullable|date',
            ]);

            $client = Client::createClient($validatedData);
            $data = ['client' => $client];
            return $this->apiService->sendResponse($data, '', 200, true);
        } catch (\Exception $e) {
            $message =  $e->getMessage();
            // Verificar si la excepción es de tipo QueryException y si es por clave única duplicada
            if ($e instanceof \Illuminate\Database\QueryException && strpos($message, 'clients_email_unique') !== false) {
                $message = 'Este correo electrónico ya está registrado';
            }
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

            if (!$request->id) {
                return $this->apiService->sendResponse([], 'El id del cliente es requerido', 400, false);
            }

            if (!$client) {
                return $this->apiService->sendResponse([], 'El cliente no fue encontrado', 404, false);
            }

            $client->deleteClient();
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
