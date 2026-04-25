<?php

namespace App\Imports;

use App\Models\SapKcl;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SapKclImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new SapKcl([
            'plant'                  => $row['plant'],
            'material'               => $row['material'],
            'material_description'   => $row['material_description'],
            'storage_location'       => $row['storage_location'],
            'batch'                  => $row['batch'],
            'unrestricted'           => $row['unrestricted'] ?? 0,
            'currency'               => $row['currency'],
            'name_1'                 => $row['name_1'],
            'material_type'          => $row['material_type'],
            'material_group'         => $row['material_group'],
            'value_unrestricted'     => $row['value_unrestricted'] ?? 0,
            'descr_of_storage_loc'   => $row['descr_of_storage_loc'],
            'base_unit_of_measure'   => $row['base_unit_of_measure'],
        ]);
    }
}