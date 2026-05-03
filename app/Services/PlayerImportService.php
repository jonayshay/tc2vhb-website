<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Player;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Spatie\SimpleExcel\SimpleExcelReader;

class PlayerImportService
{
    public function import(string $csvPath): array
    {
        $imported  = 0;
        $skipped   = 0;
        $unmatched = 0;

        SimpleExcelReader::create($csvPath, 'csv')
            ->useDelimiter(';')
            ->getRows()
            ->each(function (array $row) use (&$imported, &$skipped, &$unmatched): void {
                $lastName   = trim($row['Nom'] ?? '');
                $firstName  = trim($row['Prenom'] ?? '');
                $rawDate    = trim($row['Né(e) le'] ?? '');
                $gender     = trim($row['sexe'] ?? '');
                $license    = trim($row['Numero Licence'] ?? '') ?: null;
                $droitImage = trim($row['DroitImage'] ?? '');
                $qualite    = trim($row['Qualite'] ?? '');

                if (empty($lastName) || empty($firstName) || empty($rawDate)) {
                    return;
                }

                if (stripos($qualite, 'dirigeant') !== false) {
                    $skipped++;
                    return;
                }

                try {
                    $birthDate = Carbon::parse($rawDate);
                    if (! $birthDate->isValid()) {
                        throw new InvalidFormatException("Invalid date: $rawDate");
                    }
                } catch (InvalidFormatException) {
                    $skipped++;
                    return;
                }

                if (Player::where('last_name', $lastName)
                    ->where('first_name', $firstName)
                    ->whereDate('birth_date', $birthDate->toDateString())
                    ->exists()
                ) {
                    $skipped++;
                    return;
                }

                $category = $this->findYouthCategory($birthDate->year, $gender)
                    ?? $this->findAdultCategory($qualite, $gender);

                if ($category === null) {
                    $unmatched++;
                }

                Player::create([
                    'category_id'      => $category?->id,
                    'last_name'        => $lastName,
                    'first_name'       => $firstName,
                    'birth_date'       => $birthDate->toDateString(),
                    'gender'           => $gender ?: null,
                    'license_number'   => $license,
                    'has_image_rights' => in_array(strtolower($droitImage), ['oui', 'o', '1'], true),
                ]);

                $imported++;
            });

        return [
            'imported'  => $imported,
            'skipped'   => $skipped,
            'unmatched' => $unmatched,
        ];
    }

    private function findYouthCategory(int $birthYear, string $csvGender): ?Category
    {
        $genders = match (strtoupper(trim($csvGender))) {
            'M', 'MASCULIN'           => ['M', 'Mixte'],
            'F', 'FÉMININ', 'FEMININ' => ['F', 'Mixte'],
            default                   => ['Mixte'],
        };

        return Category::whereHas(
            'season',
            fn ($q) => $q->where('is_current', true)
        )
            ->where('type', 'youth')
            ->where('birth_year_min', '<=', $birthYear)
            ->where('birth_year_max', '>=', $birthYear)
            ->whereIn('gender', $genders)
            ->first();
    }

    private function findAdultCategory(string $qualite, string $csvGender): ?Category
    {
        $isLoisirs = stripos($qualite, 'loisir') !== false;
        $type      = $isLoisirs ? 'loisirs' : 'senior';

        $genders = match (strtoupper(trim($csvGender))) {
            'M', 'MASCULIN'           => ['M', 'Mixte'],
            'F', 'FÉMININ', 'FEMININ' => ['F', 'Mixte'],
            default                   => ['Mixte'],
        };

        return Category::whereHas(
            'season',
            fn ($q) => $q->where('is_current', true)
        )
            ->where('type', $type)
            ->whereIn('gender', $genders)
            ->first();
    }
}
