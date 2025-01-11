<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Promotion\Models\Coupon;
use Currency;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CouponsExport implements FromCollection, WithHeadings, WithStyles
{
    public array $columns;
    public array $dateRange;
    public $id;

    public function __construct($columns, $dateRange)
    {
        $this->columns = $columns;
        $this->dateRange = $dateRange;
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            // Capitalize each word and replace underscores with spaces
            if ($column != $this->columns[1]) {
                $modifiedHeadings[] = ucwords(str_replace('_', ' ', $column));
            }
        }

        return $modifiedHeadings;
    }

    public function collection()
    {
        $Promotion_id = $this->columns[1];
        $query = Coupon::where('promotion_id', $Promotion_id)
                    ->whereDate('created_at', '>=', $this->dateRange[0])
                    ->whereDate('created_at', '<=', $this->dateRange[1])
                    ->get();

        $data = [];
        foreach ($query as $row) {
            $rowData = [];
            foreach ($this->columns as $column) {
                if ($column != $this->columns[1]) {
                    $rowData[] = $row->$column;
                }
            }
            $data[] = $rowData;
        }

        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
