<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EventDifficulty;
use App\Enums\EventType;
use App\Models\Action;
use App\Models\Building;
use App\Models\BuildingLevel;
use App\Models\Country;
use App\Models\Event;
use App\Models\Law;
use App\Models\Quest;
use App\Models\Resource;
use App\Models\Title;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReferenceDataSeeder extends Seeder
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
            ['food', 'Nourriture', false],
            ['soldiers', 'Soldats', true],
            ['wood', 'Bois', false],
            ['stone', 'Pierre', false],
            ['iron', 'Fer', false],
            ['coal', 'Charbon', false],
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
            ['mine_gold', 'Miner de l\'or', 1, 5, ['gold' => 10]],
            ['harvest_food', 'Récolter du blé', 1, 3, ['food' => 8]],
            ['recruit_soldiers', 'Recruter des soldats', 1, 4, ['soldiers' => 3]],
            ['harvest_wood', 'Récolter du bois', 1, 3, ['wood' => 8]],
            ['harvest_stone', 'Extraire de la pierre', 1, 3, ['stone' => 6]],
            ['harvest_iron', 'Extraire du fer', 1, 3, ['iron' => 5]],
            ['harvest_coal', 'Extraire du charbon', 1, 3, ['coal' => 5]],
            ['build', 'Avancer une construction', 1, 6, null],
            ['adventure', 'Partir à l\'aventure', 1, 8, null],
        ];

        foreach ($actions as [$key, $name, $apCost, $baseXp, $effects]) {
            Action::firstOrCreate(
                ['key' => $key],
                ['name' => $name, 'ap_cost' => $apCost, 'base_xp' => $baseXp, 'effects' => $effects, 'is_active' => true],
            );
        }
    }

    private function seedBuildings(): void
    {
        // Les 6 bâtiments visibles sur la carte de ville : chaque bâtiment de
        // production booste la ressource qu'il incarne ; l'hôtel de ville
        // entretient la loyauté (et héberge les votes de lois côté interface).
        $buildings = [
            ['town_hall', 'Hôtel de ville', 'civic', ['loyalty_per_day' => 2], ['wood' => 50, 'stone' => 50]],
            ['farm', 'Ferme', 'production', ['food_bonus_pct' => 10], ['wood' => 50, 'stone' => 10]],
            ['forest', 'Forêt', 'production', ['wood_bonus_pct' => 10], ['wood' => 30, 'stone' => 20]],
            ['mine', 'Mine de pierre', 'production', ['stone_bonus_pct' => 10], ['wood' => 40, 'stone' => 30]],
            ['barracks', 'Caserne', 'military', ['soldiers_bonus_pct' => 10], ['wood' => 80, 'stone' => 60]],
            ['market', 'Place du marché', 'production', ['gold_bonus_pct' => 10], ['wood' => 60, 'stone' => 40]],
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
            [EventType::Adventure, 'Trésor caché', 'Vous découvrez un coffre oublié.', ['gold' => 50], EventDifficulty::Easy, 40],
            [EventType::Adventure, 'Bonne récolte', 'Un fermier reconnaissant offre des vivres.', ['food' => 30], EventDifficulty::Easy, 30],
            [EventType::Adventure, 'Embuscade', 'Des bandits attaquent le convoi.', ['soldiers' => -2, 'loyalty' => -2], EventDifficulty::Medium, 20],
            [EventType::Adventure, 'Peste', 'Une épidémie frappe la ville.', ['loyalty' => -5, 'food' => -20], EventDifficulty::Hard, 10],
            [EventType::Adventure, 'Journée bénie', 'Le seigneur accorde une action supplémentaire.', ['free_action' => 1], EventDifficulty::Easy, 5],

            [EventType::World, 'Raid de pillards', 'Des pillards ravagent les abords de la ville.', ['gold' => -30, 'loyalty' => -3], EventDifficulty::Easy, 30],
            [EventType::World, 'Disette', 'Les réserves de nourriture s\'épuisent.', ['food' => -25, 'loyalty' => -5], EventDifficulty::Medium, 20],
            [EventType::World, 'Révolte populaire', 'Le peuple se soulève contre l\'autorité.', ['loyalty' => -10, 'soldiers' => -3], EventDifficulty::Hard, 10],
        ];

        foreach ($events as [$type, $name, $description, $effects, $difficulty, $weight]) {
            Event::firstOrCreate(
                ['name' => $name],
                [
                    'type' => $type,
                    'difficulty' => $difficulty,
                    'description' => $description,
                    'effects' => $effects,
                    'weight' => $weight,
                    'is_active' => true,
                ],
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
}
