<?php

namespace App\Exports;

use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeadsExport implements FromCollection, ShouldQueue, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected $filters;

    protected $userId;

    public function __construct($filters = [], $userId = null)
    {
        $this->filters = $filters;
        $this->userId = $userId;
    }

    public function collection()
    {
        return Lead::with(['creator', 'assignee'])
            ->when(isset($this->filters['search']), function ($query) {
                $search = $this->filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
            })
            ->when(isset($this->filters['status']), function ($query) {
                $query->where('status', $this->filters['status']);
            })
            ->when(isset($this->filters['assigned_to']), function ($query) {
                $query->where('assigned_to', $this->filters['assigned_to']);
            })
            ->when(isset($this->filters['date_from']), function ($query) {
                $query->whereDate('created_at', '>=', $this->filters['date_from']);
            })
            ->when(isset($this->filters['date_to']), function ($query) {
                $query->whereDate('created_at', '<=', $this->filters['date_to']);
            })
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Company',
            'Source',
            'Status',
            'Assigned To',
            'Created By',
            'Created At',
            'Updated At',
        ];
    }

    public function map($lead): array
    {
        return [
            $lead->id,
            $lead->name,
            $lead->email,
            $lead->phone,
            $lead->company,
            $lead->source,
            ucfirst($lead->status),
            $lead->assignee ? $lead->assignee->name : 'Not Assigned',
            $lead->creator ? $lead->creator->name : 'Unknown',
            $lead->created_at->format('Y-m-d H:i:s'),
            $lead->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}
