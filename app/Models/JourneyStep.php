<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JourneyStep extends Model
{
    use HasFactory, HasLocalizedFields, LogsAllActivity;

    protected $guarded = ['id'];
}
