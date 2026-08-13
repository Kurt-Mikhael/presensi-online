<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterEmployee extends Model
{
    protected $connection = 'master';

    protected $table = 'tb_pegawai';

    public $timestamps = false;

    protected $guarded = [];
}
