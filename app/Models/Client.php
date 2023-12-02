<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['name', 'email','cod_area', 'phone', 'date_birthday'];

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

    public function deleteClient()
    {
        try {
            $this->delete();
            return $this;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    use HasFactory;
}
