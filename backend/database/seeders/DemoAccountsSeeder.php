<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestStatus;
use App\Enums\UserRole;
use App\Models\Country;
use App\Models\Game;
use App\Models\Player;
use App\Models\PlayerQuest;
use App\Models\Quest;
use App\Models\Title;
use App\Models\Town;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $france = Country::query()->where('slug', 'france-medievale')->firstOrFail();
        $paysan = Title::query()->where('slug', 'paysan')->firstOrFail();

        $admin = User::firstOrCreate(
            ['email' => 'admin@medieval-realm.test'],
            [
                'username' => 'admin',
                'password' => 'password',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
                'terms_accepted_at' => now(),
                'privacy_policy_version' => '1.0',
                'xp' => 0,
                'title_id' => $paysan->id,
            ],
        );

        $playerUser = User::firstOrCreate(
            ['email' => 'player@medieval-realm.test'],
            [
                'username' => 'seigneur_demo',
                'password' => 'password',
                'role' => UserRole::Player,
                'email_verified_at' => now(),
                'terms_accepted_at' => now(),
                'privacy_policy_version' => '1.0',
                'xp' => 0,
                'title_id' => $paysan->id,
            ],
        );

        User::firstOrCreate(
            ['email' => 'researcher@medieval-realm.test'],
            [
                'username' => 'chercheur',
                'password' => 'password',
                'role' => UserRole::Researcher,
                'email_verified_at' => now(),
                'terms_accepted_at' => now(),
                'privacy_policy_version' => '1.0',
                'xp' => 0,
                'title_id' => $paysan->id,
            ],
        );
        $game = Game::query()->where('name', 'France Médiévale — Saison 1')->first()
            ?? Game::query()->where('country_id', $france->id)->orderBy('id')->first();

        if ($game === null) {
            $this->command?->warn('Aucune saison trouvee : lancez SeasonSeeder avant DemoAccountsSeeder.');

            return;
        }

        $paris = Town::query()->where('game_id', $game->id)->where('name', 'Paris')->first()
            ?? Town::query()->where('game_id', $game->id)->orderBy('id')->first();

        $player = Player::firstOrCreate(
            ['user_id' => $playerUser->id, 'game_id' => $game->id],
            ['country_id' => $france->id, 'current_town_id' => $paris?->id],
        );

        foreach (Quest::all() as $quest) {
            PlayerQuest::firstOrCreate(
                ['player_id' => $player->id, 'quest_id' => $quest->id],
                ['status' => QuestStatus::InProgress, 'progress' => 0],
            );
        }
    }
}
