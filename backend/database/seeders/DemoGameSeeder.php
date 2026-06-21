<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EventType;
use App\Enums\GameStatus;
use App\Enums\QuestStatus;
use App\Enums\UserRole;
use App\Models\Action;
use App\Models\Building;
use App\Models\BuildingLevel;
use App\Models\Country;
use App\Models\Event;
use App\Models\Game;
use App\Models\Law;
use App\Models\Player;
use App\Models\PlayerQuest;
use App\Models\Quest;
use App\Models\Resource;
use App\Models\Title;
use App\Models\Town;
use App\Models\TownBuilding;
use App\Models\TownResource;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoGameSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCountries();
        $this->seedTitles();
        $this->seedResources();
        $this->seedActions();
        $this->seedBuildings();
        $this->seedLaws();
        $this->seedEvents();
        $this->seedQuests();
        $this->seedDemoGame();
    }

    private function seedCountries(): void
    {
        Country::firstOrCreate(
            ['slug' => 'france-medievale'],
            ['name' => 'France médiévale', 'description' => 'Le royaume de France au Moyen Âge.', 'is_active' => true],
        );
    }

    private function seedTitles(): void
    {
        $titles = [
            ['Paysan', 0, 0],
            ['Chevalier', 100, 1],
            ['Baron', 500, 2],
            ['Duc', 2000, 3],
            ['Roi', 10000, 4],
        ];

        foreach ($titles as [$name, $minXp, $rank]) {
            Title::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'min_xp' => $minXp, 'rank' => $rank],
            );
        }
    }

    private function seedResources(): void
    {
        $resources = [
            ['gold', 'Or', false],
            ['wood', 'Bois', false],
            ['stone', 'Pierre', false],
            ['food', 'Nourriture', false],
            ['soldiers', 'Soldats', true],
        ];

        foreach ($resources as [$key, $name, $isMilitary]) {
            Resource::firstOrCreate(
                ['key' => $key],
                ['name' => $name, 'is_military' => $isMilitary, 'is_active' => true],
            );
        }
    }

    private function seedActions(): void
    {
        $actions = [
            ['mine_gold', 'Miner de l\'or', 1, 5],
            ['harvest_wood', 'Récolter du bois', 1, 3],
            ['harvest_stone', 'Récolter de la pierre', 1, 3],
            ['harvest_food', 'Récolter de la nourriture', 1, 3],
            ['recruit_soldiers', 'Recruter des soldats', 1, 4],
            ['build', 'Avancer une construction', 1, 6],
            ['adventure', 'Partir à l\'aventure', 1, 8],
        ];

        foreach ($actions as [$key, $name, $apCost, $baseXp]) {
            Action::firstOrCreate(
                ['key' => $key],
                ['name' => $name, 'ap_cost' => $apCost, 'base_xp' => $baseXp, 'is_active' => true],
            );
        }
    }

    private function seedBuildings(): void
    {
        $buildings = [
            ['farm', 'Ferme', 'production', ['food_per_day' => 10], ['wood' => 50, 'food' => 20]],
            ['sawmill', 'Scierie', 'production', ['wood_per_day' => 10], ['wood' => 40, 'stone' => 20]],
            ['quarry', 'Carrière', 'production', ['stone_per_day' => 10], ['wood' => 40, 'stone' => 20]],
            ['barracks', 'Caserne', 'military', ['soldier_capacity' => 50], ['wood' => 80, 'stone' => 60]],
            ['warehouse', 'Entrepôt', 'storage', ['storage' => 500], ['wood' => 60, 'stone' => 40]],
            ['wall', 'Muraille', 'defense', ['defense' => 10], ['stone' => 120]],
        ];

        foreach ($buildings as [$key, $name, $category, $bonus, $cost]) {
            $building = Building::firstOrCreate(
                ['key' => $key],
                ['name' => $name, 'category' => $category, 'max_level' => 3, 'is_active' => true],
            );

            for ($level = 1; $level <= 3; $level++) {
                BuildingLevel::firstOrCreate(
                    ['building_id' => $building->id, 'level' => $level],
                    [
                        'bonus' => array_map(fn (int $value): int => $value * $level, $bonus),
                        'cost' => array_map(fn (int $value): int => $value * $level, $cost),
                        'build_points' => 10 * $level,
                    ],
                );
            }
        }
    }

    private function seedLaws(): void
    {
        $laws = [
            ['tax_relief', 'Allègement fiscal', ['gold_per_day' => 5]],
            ['conscription', 'Conscription', ['soldier_recruit_bonus' => 2]],
            ['trade_pact', 'Pacte commercial', ['food_per_day' => 5]],
        ];

        foreach ($laws as [$key, $name, $bonus]) {
            Law::firstOrCreate(
                ['key' => $key],
                ['name' => $name, 'bonus' => $bonus, 'is_active' => true],
            );
        }
    }

    private function seedEvents(): void
    {
        $events = [
            ['Trésor caché', 'Vous découvrez un coffre oublié.', ['gold' => 50, 'xp' => 5], 40],
            ['Embuscade', 'Des bandits attaquent votre convoi.', ['soldiers' => -2, 'loyalty' => -2], 30],
            ['Bonne récolte', 'Un fermier reconnaissant vous offre des vivres.', ['food' => 30], 20],
            ['Élan de ferveur', 'Le peuple acclame son seigneur.', ['loyalty' => 5], 10],
            ['Journée bénie', 'Le seigneur vous accorde une action supplémentaire.', ['free_action' => 1], 5],
        ];

        foreach ($events as [$name, $description, $effects, $weight]) {
            Event::firstOrCreate(
                ['name' => $name],
                ['type' => EventType::Adventure, 'description' => $description, 'effects' => $effects, 'weight' => $weight, 'is_active' => true],
            );
        }
    }

    private function seedQuests(): void
    {
        $quests = [
            ['first_gold', 'Premier filon', 'Miner de l\'or 3 fois.', 'perform_action', 3, 'mine_gold', 50, ['gold' => 100]],
            ['builder', 'Bâtisseur', 'Avancer une construction.', 'perform_action', 1, 'build', 40, ['wood' => 50]],
            ['adventurer', 'Aventurier', 'Partir à l\'aventure une fois.', 'perform_action', 1, 'adventure', 60, null],
        ];

        foreach ($quests as [$key, $name, $description, $objectiveType, $target, $actionKey, $xpReward, $rewards]) {
            $action = Action::query()->where('key', $actionKey)->firstOrFail();

            Quest::firstOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'description' => $description,
                    'objective_type' => $objectiveType,
                    'objective_target' => $target,
                    'action_id' => $action->id,
                    'xp_reward' => $xpReward,
                    'rewards' => $rewards,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedDemoGame(): void
    {
        $france = Country::query()->where('slug', 'france-medievale')->firstOrFail();
        $paysan = Title::query()->where('slug', 'paysan')->firstOrFail();

        $admin = User::firstOrCreate(
            ['email' => 'admin@medieval-realm.test'],
            ['name' => 'Admin', 'password' => 'password', 'role' => UserRole::Admin],
        );

        $playerUser = User::firstOrCreate(
            ['email' => 'player@medieval-realm.test'],
            ['name' => 'Joueur Démo', 'password' => 'password', 'role' => UserRole::Player],
        );

        $game = Game::firstOrCreate(
            ['name' => 'France Médiévale — Saison 1'],
            [
                'status' => GameStatus::Active,
                'config' => [
                    'daily_actions' => 5,
                    'upkeep_hour' => '12:00',
                    'vote_hours' => 12,
                    'soldier_upkeep' => ['gold' => 1, 'food' => 1],
                    'desertion_rate' => 0.2,
                ],
                'started_at' => now(),
                'created_by' => $admin->id,
            ],
        );

        $player = Player::firstOrCreate(
            ['user_id' => $playerUser->id, 'game_id' => $game->id],
            ['country_id' => $france->id, 'title_id' => $paysan->id, 'display_name' => 'Seigneur Démo', 'xp' => 0],
        );

        $town = Town::firstOrCreate(
            ['game_id' => $game->id, 'player_id' => $player->id],
            ['country_id' => $france->id, 'name' => 'Paris', 'population' => 250, 'loyalty' => 100],
        );

        $startingResources = ['gold' => 500, 'wood' => 300, 'stone' => 200, 'food' => 400, 'soldiers' => 10];

        foreach ($startingResources as $key => $amount) {
            $resource = Resource::query()->where('key', $key)->firstOrFail();

            TownResource::firstOrCreate(
                ['town_id' => $town->id, 'resource_id' => $resource->id],
                ['amount' => $amount, 'capacity' => $key === 'soldiers' ? null : 1000],
            );
        }

        foreach (Building::all() as $building) {
            TownBuilding::firstOrCreate(
                ['town_id' => $town->id, 'building_id' => $building->id],
                ['level' => 0, 'build_progress' => 0],
            );
        }

        foreach (Quest::all() as $quest) {
            PlayerQuest::firstOrCreate(
                ['player_id' => $player->id, 'quest_id' => $quest->id],
                ['status' => QuestStatus::InProgress, 'progress' => 0],
            );
        }
    }
}
