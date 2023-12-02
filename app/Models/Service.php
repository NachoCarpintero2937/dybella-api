<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'price_id', 'user_id'];

    public static function createService($data)
    {
        return static::create($data);
    }

    public function updateService($data)
    {
        try {
            $this->update($data);
            return $this;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function deleteSrvice()
    {
        try {
            $this->delete();
            return $this;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // Definir relaciones con otros modelos
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function price()
    {
        return $this->belongsTo(Price::class, 'price_id');
    }

    use HasFactory;
}
