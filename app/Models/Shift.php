<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['service_id', 'client_id', 'user_id', 'date_shift', 'description','price','status'];
    use HasFactory;


    public static function createShift($data)
    {
        try {
            return static::create($data);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    public function updateShift($data)
    {
        try {
            $this->update($data);
            return $this;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function deleteShift()
    {
        try {
            $this->delete();
            return $this;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    use HasFactory;
}
