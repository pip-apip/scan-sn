<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RegionsImport implements ToCollection, WithHeadingRow
{
    /** @var Collection<int, array<string, mixed>>|null */
    public ?Collection $rows = null;

    public function collection(Collection $rows): void
    {
        /** @var Collection<int, array<string, mixed>> $rows */
        $this->rows = $rows;
    }

    public function preview(): array
    {
        $preview = [];

        if ($this->rows === null) {
            return $preview;
        }

        foreach ($this->rows as $index => $row) {

            $errors = [];

            if (blank($row['category'])) {
                $errors[] = [
                    'column' => 'category',
                    'message' => 'Category is required',
                ];
            }

            if (blank($row['name'])) {
                $errors[] = [
                    'column' => 'name',
                    'message' => 'Name is required',
                ];
            }

            $preview[] = [
                'row' => $index + 2, // +2 because heading row
                'category' => $row['category'],
                'name' => $row['name'],
                'valid' => count($errors) === 0,
                'errors' => $errors,
            ];
        }

        return $preview;
    }
}
