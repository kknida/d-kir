<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SapKcl extends Model
{
    protected $table = 'sap_kcls';

    protected $fillable = [
        'plant',
        'material',
        'material_description',
        'storage_location',
        'batch',
        'unrestricted',
        'currency',
        'name_1',
        'material_type',
        'material_group',
        'value_unrestricted',
        'descr_of_storage_loc',
        'base_unit_of_measure',
    ];

    protected $casts = [
        'unrestricted'       => 'double',
        'value_unrestricted' => 'double',
    ];
}