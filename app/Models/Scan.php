<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    use HasFactory;
    protected $table = 'scans';
    protected $fillable = [
        'agent_id',
        'scanned_id',
    ];

    public function scannedUser()
    {
        return $this->belongsTo(User::class, 'scanned_id');
    }
}
