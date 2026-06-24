<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameStatus;
use App\Models\Country;
use App\Models\Game;
use App\Models\Player;
use App\Models\Town;
use App\Models\TownBuilding;
use App\Models\TownResource;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class GameService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function countries(User $user): array
    {
        $alreadyPlaying = $this->activePlayer($user) !== null;

        return Country::query()
            ->withCount(['players' => fn ($query) => $query->whereHas(
                'game',
                fn ($q) => $q->where('status', GameStatus::Active),
            )])
            ->orderBy('name')
            ->get()
            ->map(function (Country $country) use ($alreadyPlaying): array {
                $season = $this->activeSeason($country);

                return [
                    'id' => $country->id,
                    'name' => $country->name,
                    'slug' => $country->slug,
                    'is_active' => $country->is_active,
                    'players_count' => $country->players_count,
                    'season' => $season === null ? null : [
                        'id' => $season->id,
                        'name' => $season->name,
                        'status' => $season->status->value,
                        'started_at' => $season->started_at?->toIso8601String(),
                    ],
                    'can_join' => ! $alreadyPlaying && $season !== null && $country->is_active,
                ];
            })
            ->all();
    }

    public function join(User $user, Country $country): Player
    {
        if ($this->activePlayer($user) !== null) {
            throw ValidationException::withMessages([
                'country_id' => [__('You already belong to an active country. Finish that season first.')],
            ]);
        }

        $season = $this->activeSeason($country);

        if ($season === null) {
            throw ValidationException::withMessages([
                'country_id' => [__('This country has no active season right now.')],
            ]);
        }

        $town = $season->towns()
            ->whereNull('destroyed_at')
            ->orderBy('id')
            ->first();

        /** @var Player $player */
        $player = $user->players()->create([
            'game_id' => $season->id,
            'country_id' => $country->id,
            'current_town_id' => $town?->id,
        ]);

        return $player;
    }

    public function enterTown(Player $player, Town $town): Player
    {
        if ($town->game_id !== $player->game_id) {
            throw ValidationException::withMessages([
                'town' => [__('This town does not belong to your country.')],
            ]);
        }

        if ($town->destroyed_at !== null) {
            throw ValidationException::withMessages([
                'town' => [__('This town has been destroyed.')],
            ]);
        }

        $player->update(['current_town_id' => $town->id]);

        return $player;
    }

    /**
     * @return array<string, mixed>
     */
    public function state(User $user): array
    {
        $player = $this->activePlayer($user);

        if ($player === null) {
            throw ValidationException::withMessages([
                'player' => [__('You have not joined a country yet.')],
            ]);
        }

        $player->load(['game.country', 'user.title']);

        return [
            'player' => [
                'id' => $player->id,
                'xp' => $player->user->xp,
                'title' => $player->user->title?->name,
                'country' => [
                    'id' => $player->country_id,
                    'name' => $player->game->country?->name,
                ],
                'season' => [
                    'id' => $player->game->id,
                    'name' => $player->game->name,
                    'status' => $player->game->status->value,
                ],
                'daily_actions' => (int) ($player->game->config['daily_actions'] ?? 5),
            ],
            'town' => $this->townState($player->current_town_id),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function townState(?int $townId): ?array
    {
        if ($townId === null) {
            return null;
        }

        $town = Town::query()
            ->with([
                'townResources.resource',
                'townBuildings.building.levels',
            ])
            ->find($townId);

        if ($town === null) {
            return null;
        }

        return [
            'id' => $town->id,
            'name' => $town->name,
            'map_x' => $town->map_x,
            'map_y' => $town->map_y,
            'population' => $town->population,
            'loyalty' => $town->loyalty,
            'destroyed_at' => $town->destroyed_at?->toIso8601String(),
            'resources' => $town->townResources
                ->map(fn (TownResource $townResource): array => [
                    'key' => $townResource->resource->key,
                    'name' => $townResource->resource->name,
                    'is_military' => $townResource->resource->is_military,
                    'amount' => $townResource->amount,
                    'capacity' => $townResource->capacity,
                ])->all(),
            'buildings' => $town->townBuildings
                ->map(fn (TownBuilding $townBuilding): array => [
                    'key' => $townBuilding->building->key,
                    'name' => $townBuilding->building->name,
                    'category' => $townBuilding->building->category,
                    'level' => $townBuilding->level,
                    'max_level' => $townBuilding->building->max_level,
                    'map_x' => $townBuilding->map_x,
                    'map_y' => $townBuilding->map_y,
                    'image' => $townBuilding->image,
                    'contributions' => $townBuilding->contributions ?? [],
                    'next_cost' => $this->nextLevelCost($townBuilding),
                ])->all(),
        ];
    }

    /**
     * @return array<string, int>|null
     */
    private function nextLevelCost(TownBuilding $townBuilding): ?array
    {
        if ($townBuilding->level >= $townBuilding->building->max_level) {
            return null;
        }

        $nextLevel = $townBuilding->building->levels
            ->firstWhere('level', $townBuilding->level + 1);

        /** @var array<string, int>|null $cost */
        $cost = $nextLevel?->cost;

        return $cost;
    }

    public function activePlayer(User $user): ?Player
    {
        return $user->players()
            ->whereHas('game', fn ($query) => $query->where('status', GameStatus::Active))
            ->first();
    }

    private function activeSeason(Country $country): ?Game
    {
        return $country->games()
            ->where('status', GameStatus::Active)
            ->latest('started_at')
            ->first();
    }
}
