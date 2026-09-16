<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\GameStatus;
use App\Enums\UserRole;
use App\Models\Country;
use App\Models\Game;
use App\Models\User;
use App\Services\SeasonBuilder;
use Illuminate\Console\Command;

final class StartSeason extends Command
{
    protected $signature = 'season:start
        {name : Nom de la saison, par exemple "France Médiévale — Saison 2"}
        {--country= : Slug du pays ; celui de config/seasons.php par défaut}
        {--force : Créer même si une saison est déjà active sur ce pays}';

    protected $description = 'Crée une nouvelle saison et ses villes à partir de config/seasons.php.';

    public function handle(SeasonBuilder $builder): int
    {
        /** @var string $name */
        $name = $this->argument('name');
        $slug = (string) ($this->option('country') ?: config('seasons.country'));

        $country = Country::query()->where('slug', $slug)->first();

        if ($country === null) {
            $this->error("Pays introuvable : {$slug}.");

            return self::FAILURE;
        }

        if (Game::query()->where('name', $name)->exists()) {
            $this->error("Une saison porte déjà le nom « {$name} ».");

            return self::FAILURE;
        }

        // Deux saisons actives sur un meme pays rendraient `activeSeason()`
        // ambigu : les joueurs rejoindraient la plus recente sans le savoir.
        $active = Game::query()
            ->where('country_id', $country->id)
            ->where('status', GameStatus::Active)
            ->first();

        if ($active !== null && ! $this->option('force')) {
            $this->error("« {$active->name} » est encore active sur {$country->name}.");
            $this->line('Clôturez-la d\'abord, ou relancez avec --force.');

            return self::FAILURE;
        }

        $createdBy = User::query()->where('role', UserRole::Admin)->value('id');

        $game = $builder->build($name, $country, $createdBy === null ? null : (int) $createdBy);

        $villes = $game->towns()->count();
        $this->info("Saison « {$game->name} » créée (#{$game->id}) sur {$country->name} : {$villes} ville(s).");

        return self::SUCCESS;
    }
}
