<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['name', 'email', 'cod_area', 'phone', 'date_birthday', 'status'];

    public static function createClient($data)
    {
        return static::create($data);
    }

    public function updateClient($data)
    {
        try {
            $this->update($data);
            return $this;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    public function softDeleteClient()
    {
        $this->status = 1; // Cambiar el estado del cliente a inactivo
        $this->save();
    }


    use HasFactory;
}
