<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Player;
use App\Models\Season;
use App\Services\PlayerImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function writeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($path, $content);
        return $path;
    }

    public function test_imports_player_and_assigns_category_by_birth_year(): void
    {
        $season = Season::factory()->create(['is_current' => true]);
        $category = Category::factory()->create([
            'season_id'      => $season->id,
            'gender'         => 'M',
            'birth_year_min' => 2014,
            'birth_year_max' => 2015,
        ]);

        $csv = "Nom;Prenom;Né(e) le;sexe;Numero Licence;DroitImage\n" .
               "Dupont;Jean;2014-01-15;M;123456;Oui\n";
        $path = $this->writeCsv($csv);

        $result = (new PlayerImportService())->import($path);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEquals(0, $result['unmatched']);
        $this->assertDatabaseHas('players', [
            'last_name'        => 'Dupont',
            'first_name'       => 'Jean',
            'category_id'      => $category->id,
            'has_image_rights' => true,
        ]);
    }

    public function test_skips_duplicate_based_on_name_and_birth_date(): void
    {
        $season = Season::factory()->create(['is_current' => true]);
        Player::factory()->create([
            'last_name'  => 'Dupont',
            'first_name' => 'Jean',
            'birth_date' => '2014-01-15',
        ]);

        $csv = "Nom;Prenom;Né(e) le;sexe;Numero Licence;DroitImage\n" .
               "Dupont;Jean;2014-01-15;M;123456;Oui\n";
        $path = $this->writeCsv($csv);

        $result = (new PlayerImportService())->import($path);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertDatabaseCount('players', 1);
    }

    public function test_creates_player_with_null_category_when_no_match(): void
    {
        Season::factory()->create(['is_current' => true]);

        $csv = "Nom;Prenom;Né(e) le;sexe;Numero Licence;DroitImage\n" .
               "Martin;Alice;1980-01-15;F;654321;Non\n";
        $path = $this->writeCsv($csv);

        $result = (new PlayerImportService())->import($path);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEquals(1, $result['unmatched']);
        $this->assertDatabaseHas('players', [
            'last_name'   => 'Martin',
            'category_id' => null,
        ]);
    }

    public function test_droit_image_oui_sets_has_image_rights_true(): void
    {
        Season::factory()->create(['is_current' => true]);

        $csv = "Nom;Prenom;Né(e) le;sexe;Numero Licence;DroitImage\n" .
               "A;B;2010-01-01;M;1;Oui\n" .
               "C;D;2010-01-02;M;2;Non\n" .
               "E;F;2010-01-03;M;3;O\n" .
               "G;H;2010-01-04;M;4;1\n";
        $path = $this->writeCsv($csv);

        (new PlayerImportService())->import($path);

        $this->assertTrue(Player::where('last_name', 'A')->first()->has_image_rights);
        $this->assertFalse(Player::where('last_name', 'C')->first()->has_image_rights);
        $this->assertTrue(Player::where('last_name', 'E')->first()->has_image_rights);
        $this->assertTrue(Player::where('last_name', 'G')->first()->has_image_rights);
    }

    public function test_female_player_is_assigned_to_female_category(): void
    {
        $season = Season::factory()->create(['is_current' => true]);
        $femCategory = Category::factory()->create([
            'season_id'      => $season->id,
            'gender'         => 'F',
            'birth_year_min' => 2012,
            'birth_year_max' => 2013,
        ]);
        $masCategory = Category::factory()->create([
            'season_id'      => $season->id,
            'gender'         => 'M',
            'birth_year_min' => 2012,
            'birth_year_max' => 2013,
        ]);

        $csv = "Nom;Prenom;Né(e) le;sexe;Numero Licence;DroitImage\n" .
               "Durand;Léa;2012-05-10;F;999;Non\n";
        $path = $this->writeCsv($csv);

        (new PlayerImportService())->import($path);

        $this->assertDatabaseHas('players', [
            'last_name'   => 'Durand',
            'category_id' => $femCategory->id,
        ]);
    }

    public function test_row_with_malformed_date_is_skipped(): void
    {
        Season::factory()->create(['is_current' => true]);

        $csv = "Nom;Prenom;Né(e) le;sexe;Numero Licence;DroitImage\n" .
               "Dupont;Jean;not-a-date;M;1;Non\n" .
               "Martin;Paul;2014-01-15;M;2;Non\n";
        $path = $this->writeCsv($csv);

        $result = (new PlayerImportService())->import($path);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertDatabaseMissing('players', ['last_name' => 'Dupont']);
        $this->assertDatabaseHas('players', ['last_name' => 'Martin']);
    }
}
