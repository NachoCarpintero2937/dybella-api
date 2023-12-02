<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $fillable = ['price', 'user_id', 'description'];
    public static function createPrice($data)
    {
        return static::create($data);
    }

    public function updatePrice($data)
    {
        try {
            $this->update($data);
            return $this;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function deletePrice()
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
