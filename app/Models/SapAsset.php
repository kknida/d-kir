<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SapAsset extends Model
{
    protected $table = 'sap_assets';

    protected $fillable = [
        'asset_number', 'sub_number', 'asset_name', 'original_asset',
        'asset_description', 'asset_main_no_text', 'acquis_val',
        'accum_dep', 'book_val', 'quantity', 'base_unit_of_measure',
        'useful_life', 'useful_life_in_periods', 'asset_class',
        'capitalized_on', 'is_locked','asset_description_2', 'asset_location'
    ];

    protected $casts = [
        'capitalized_on' => 'date',
        'is_locked' => 'boolean',
    ];
}