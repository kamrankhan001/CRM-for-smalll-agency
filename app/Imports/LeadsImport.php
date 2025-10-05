<?php

namespace App\Imports;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;

class LeadsImport implements ToCollection, WithHeadingRow
{
    private array $errors = [];
    private int $importedCount = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowIndex = $index + 2; // +2 because of header row and 1-based index
            
            try {
                // Validate required fields
                if (empty($row['name'])) {
                    $this->errors[] = "Row {$rowIndex}: Name is required";
                    continue;
                }

                // Prepare data
                $data = [
                    'name' => $row['name'] ?? '',
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'company' => $row['company'] ?? null,
                    'source' => $row['source'] ?? null,
                    'status' => $this->getValidStatus($row['status'] ?? 'new'),
                    'created_by' => Auth::id(),
                ];

                // Handle assigned_to if provided
                if (!empty($row['assigned_to'])) {
                    $assignedUser = User::where('name', $row['assigned_to'])->first();
                    if ($assignedUser) {
                        $data['assigned_to'] = $assignedUser->id;
                    }
                }

                // Check if lead already exists (by email or phone)
                $existingLead = null;
                if (!empty($data['email'])) {
                    $existingLead = Lead::where('email', $data['email'])->first();
                } elseif (!empty($data['phone'])) {
                    $existingLead = Lead::where('phone', $data['phone'])->first();
                }

                if ($existingLead) {
                    // Update existing lead
                    $existingLead->update($data);
                } else {
                    // Create new lead
                    Lead::create($data);
                }

                $this->importedCount++;

            } catch (\Exception $e) {
                $this->errors[] = "Row {$rowIndex}: " . $e->getMessage();
            }
        }
    }

    private function getValidStatus($status): string
    {
        $validStatuses = ['new', 'contacted', 'qualified', 'lost'];
        $status = strtolower(trim($status));
        
        if (in_array($status, $validStatuses)) {
            return $status;
        }
        
        return 'new';
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getTotalRows(): int
    {
        return $this->importedCount + count($this->errors);
    }
}