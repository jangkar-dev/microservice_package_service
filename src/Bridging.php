<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bridging extends Model
{
    protected $connection = 'shared';
    protected $primaryKey = 'id';
    public $incrementing = false;
    use HasFactory;
    protected $fillable = [
        'id',
        'model',
        'vendor_id',
        'vendor_primary_id',
    ];
    public function scopeCompany($query)
    {
        return $query->where('model', Company::class);
    }
}
