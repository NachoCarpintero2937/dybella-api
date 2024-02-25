<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

namespace App\Http\Controllers;

use App\Mail\Recordatory;
use App\Mail\TurnAssigned;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Services\ApiService;
use Carbon\Carbon;
use Error;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ShiftController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->middleware('auth:api', ['except' => ['sendEmailsForNewShiftsInFiveMinutes']]);
        // sendEmailsForNewShiftsInFiveMinutes
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

            // Order by date_shift
            $query->orderBy('date_shift');

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
                ->where('status', 0) // Agregar condición para el estado 0
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
                'status' => 'required'
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
                'price' => 'required',
                'description' => 'nullable|string',
            ]);

            $shift->update($validatedData);

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
    public function getShiftsForTomorrowAndSendReminderEmail()
    {
        $data = [];
        try {
            $tomorrowDate = Carbon::tomorrow()->toDateString();

            $query = Shift::with(['user', 'service', 'client', 'service.price'])
                ->whereDate('date_shift', $tomorrowDate)
                ->where('status', 0);

            $shiftsForTomorrow = $query->get();

            // Envía el correo electrónico de recordatorio a cada cliente
            $countEmail = 0;
            foreach ($shiftsForTomorrow as $shift) {
                $countEmail++;
                $clientEmail = $shift->client->email; // Asumiendo que la relación con el cliente está configurada correctamente
                $mailValidator = [
                    "name" => $shift->client->name,
                    "service" => $shift->service->name
                ];
                // Verifica si el cliente tiene una dirección de correo electrónico antes de enviar el correo
                if ($clientEmail) {
                    Mail::to($clientEmail)->send(new Recordatory($mailValidator));
                }
            }

            $data = [
                "countEmails" => $countEmail
            ];
            $statusCode = 200;
            return $this->apiService->sendResponse($data, '', $statusCode, true);
        } catch (Exception $e) {
            $message = $e->getMessage();
            return $this->apiService->sendResponse($data, $message, 400, false);
        }
    }

    // emails
    public function sendEmailsForNewShiftsInFiveMinutes()
    {
        // Extraer horas de inicio y fin
        $currentTime = Carbon::now()->subMinutes(5);
        $endTime = $currentTime->copy()->addMinutes(5);
        var_dump($currentTime->format('Y-m-d H:i:s'));
        var_dump($endTime->format('Y-m-d H:i:s'));

        // Consulta SQL directa con BETWEEN
        $consulta = "
            SELECT *
            FROM shifts
            WHERE created_at BETWEEN '" . $currentTime . "' AND '" . $endTime . "'
        ";


        // Ejecuta la consulta SQL
        $newShifts = DB::select($consulta);
        var_dump($newShifts);

        foreach ($newShifts as $shift) {
            // Obtén los datos necesarios y envía el correo
            $validatedData = [
                'service_id' => $shift->service_id,
                'client_id' => $shift->client_id,
                'user_id' => $shift->user_id,
                'date_shift' => $shift->date_shift,
                'description' => $shift->description,
                'price' => $shift->price,
                'status' => $shift->status,
            ];

            $this->sendEmailForNewShift($validatedData);
        }
    }

    public function sendEmailForNewShift($validatedData)
    {
        // Obtén el nombre del servicio
        $serviceName = Service::find($validatedData['service_id'])->name;

        // Envía un correo electrónico al cliente
        $client = Client::find($validatedData['client_id']);
        if ($client) {
            $mailData = [
                'clientName' => $client->name,
                'shiftDate' => $validatedData['date_shift'],
                'serviceName' => $serviceName,
                // Otros datos que desees incluir en el correo
            ];
            if ($client->email) {
                Mail::to($client->email)->send(new TurnAssigned($mailData));
            }
        }
    }

    public function reportsMonth()
    {
        try {
            // Obtener el total de precios agrupados por mes para registros con status 1
            $monthlyTotals = DB::table('shifts')
                ->select(DB::raw('YEAR(date_shift) as year'), DB::raw('MONTH(date_shift) as month'), DB::raw('SUM(price) as total'))
                ->where('status', 1)
                ->groupBy(DB::raw('YEAR(date_shift)'), DB::raw('MONTH(date_shift)'))
                ->get();

            // Obtener la cantidad de reservas canceladas (estado 2) por mes
            $canceledCounts = DB::table('shifts')
                ->select(DB::raw('YEAR(date_shift) as year'), DB::raw('MONTH(date_shift) as month'), DB::raw('COUNT(*) as canceled_count'))
                ->where('status', 2)
                ->groupBy(DB::raw('YEAR(date_shift)'), DB::raw('MONTH(date_shift)'))
                ->get();

            // Crear un array asociativo para almacenar los totales, reservas canceladas y totales por año
            $totalPrices = [];
            $cancelledShifts = [];
            $totalYears = [];
            $allMonths = [
                'january', 'february', 'march', 'april', 'may', 'june',
                'july', 'august', 'september', 'october', 'november', 'december'
            ];

            foreach ($allMonths as $month) {
                $monthName = trans('date.months.' . $month);
                $totalPrices[$monthName] = 0;
                $cancelledShifts[$monthName] = 0;
            }

            foreach ($monthlyTotals as $total) {
                $year = $total->year;
                $monthName = trans('date.months.' . strtolower(Carbon::create()->month($total->month)->format('F')));

                // Inicializar el contador de precios por año si aún no existe
                if (!isset($totalYears[$year])) {
                    $totalYears[$year] = 0;
                }

                $totalYears[$year] += (float) $total->total;

                $totalPrices[$monthName] = (float) $total->total;
            }

            foreach ($canceledCounts as $canceledCount) {
                $monthName = trans('date.months.' . strtolower(Carbon::create()->month($canceledCount->month)->format('F')));
                $cancelledShifts[$monthName] = (int) $canceledCount->canceled_count;
            }

            // Devolver la respuesta exitosa
            $data = [
                'totalPrices' => $totalPrices,
                'cancelled_shifts' => $cancelledShifts,
                'totalYears' => $totalYears,
            ];

            $statusCode = 200;
            return $this->apiService->sendResponse($data, '', $statusCode, true);
        } catch (\Exception $e) {
            // En caso de excepción, devolver la respuesta de error
            $data = ['error' => $e->getMessage()];
            $statusCode = 500;
            return $this->apiService->sendResponse($data, '', $statusCode, false);
        }
    }
}
