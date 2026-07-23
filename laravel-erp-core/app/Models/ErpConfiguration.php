<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErpConfiguration extends Model
{
    protected $table = 'configurations';

    protected $fillable = [
        'name',
        'value',
    ];
}
