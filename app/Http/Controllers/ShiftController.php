<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

namespace App\Http\Controllers;

use App\Mail\TurnAssigned;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Services\ApiService;
use Carbon\Carbon;
use Error;
use Exception;
use Illuminate\Support\Facades\Mail;

class ShiftController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->middleware('auth:api');
    }
    public function index(Request $request)
    {
        $data = [];
        try {
            $query = Shift::with(['user', 'service', 'client', 'service.price']);

            //for id
            if ($request->has('id')) {
                $query->where('id', $request->id)->get();
            }

            // for client_id
            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id)->get();
            }
            // for service_id
            if ($request->has('service_id')) {
                $query->where('service_id', $request->service_id)->get();
            }

            if ($request->has('status')) {
                $query->where('status', $request->status)->get();
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $start_date = $request->start_date;
                $end_date = $request->end_date;
                $query->whereBetween('date_shift', [$start_date, $end_date]);
            }

            // Filtro por fecha única (date_shift)
            if ($request->has('date_shift')) {
                $query->whereDate('date_shift', $request->date_shift);
            }

            $shifts = $query->get();

            $data = [
                "shifts" => $shifts
            ];
            $statusCode = 200;
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
                'service_id' => 'required|exists:services,id',
                'client_id' => 'required|exists:clients,id',
                'user_id' => 'required|exists:users,id',
                'date_shift' => 'required|date',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'status' => 'required|integer',
            ]);

            // Obtén el nombre del servicio
            $serviceName = Service::find($validatedData['service_id'])->name;

            // Validación personalizada para verificar si el usuario tiene un turno en el rango de 15 minutos
            $userHasShiftWithinRange = Shift::where('user_id', $validatedData['user_id'])
                ->whereBetween('date_shift', [
                    Carbon::parse($validatedData['date_shift'])->subMinutes(15),
                    Carbon::parse($validatedData['date_shift'])->addMinutes(15),
                ])
                ->exists();

            if ($userHasShiftWithinRange) {
                throw new \Exception('El usuario ya tiene un turno asignado dentro del rango de 15 minutos.');
            }

            $shift = Shift::createShift($validatedData);
            $data = [
                'shift' => $shift,
                'serviceName' => $serviceName, // Agrega el nombre del servicio al array de datos
            ];

            // Envía un correo electrónico al cliente
            $client = Client::find($validatedData['client_id']);
            if ($client) {
                $mailData = [
                    'clientName' => $client->name,
                    'shiftDate' => $validatedData['date_shift'],
                    'serviceName' => $serviceName, // Agrega el nombre del servicio al array de datos
                    // Otros datos que desees incluir en el correo
                ];

                Mail::to($client->email)->send(new TurnAssigned($mailData));
            }

            return $this->apiService->sendResponse($data, '', 200, true);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->apiService->sendResponse($data, $message, 400, false);
        }
    }

    public function update(Request $request)
    {
        $data = [];
        if (!$request->id) {
            return $this->apiService->sendResponse([], 'El id del turno es requerido', 404, false);
        }
        $shift = Shift::find($request->id);

        if (!$shift) {
            return $this->apiService->sendResponse([], 'El turno no fue encontrado', 404, false);
        }

        try {
            $validatedData = $request->validate([
                'service_id' => 'required|exists:services,id',
                'price' => 'required',
                'client_id' => 'required|exists:clients,id',
                'user_id' => 'required|exists:users,id',
                'date_shift' => 'required|date',
                'description' => 'nullable|string',
            ]);
            $shiftUp = $shift->updateShift($validatedData);
            $data = [
                'shift' => $shiftUp
            ];
            return $this->apiService->sendResponse($data, '', 200, true);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->apiService->sendResponse([], $message, 400, false);
        }
    }

    public function updateStatus(Request $request)
    {
        try {

            if (!$request->id) {
                return $this->apiService->sendResponse([], 'El id del turno es requerido', 404, false);
            }

            $shift = Shift::find($request->id);

            if (!$shift) {
                return $this->apiService->sendResponse([], 'El turno no existe', 404, false);
            }

            $validatedData = $request->validate([
                'status' => 'required',
            ]);

            $shift->update(['status' => $request->status]);

            return $this->apiService->sendResponse($shift, 'Status actualizado correctamente', 200, true);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->apiService->sendResponse([], $message, 400, false);
        }
    }

    public function destroy(Request $request)
    {
        try {

            if (!$request->id) {
                return $this->apiService->sendResponse([], 'El id del turno es requerido', 404, false);
            }

            $shift = Shift::find($request->id);

            if (!$shift) {
                return $this->apiService->sendResponse([], 'El turno no fue encontrado', 404, false);
            }

            $shift->deleteShift();
            return $this->apiService->sendResponse([], 'Turno eliminado con éxito', 200, true);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->apiService->sendResponse([], $message, 400, false);
        }
    }
}
