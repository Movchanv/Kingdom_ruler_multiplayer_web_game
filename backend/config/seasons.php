<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Plan d'une saison
|--------------------------------------------------------------------------
|
| Source unique du contenu d'une saison : le seeder (premiere installation)
| et la commande `season:start` (saisons suivantes) lisent ce fichier. Ajouter
| une ville ou un point d'interet se fait donc ici, a un seul endroit.
|
| Les coordonnees sont exprimees sur la grille de 1000 x 1000 utilisee par le
| frontend (voir MAP_UNITS dans src/config/worldMap.js).
|
*/

return [

    'country' => 'france-medievale',

    /*
    | Reglages de jeu copies dans `games.config` a la creation. Les modifier
    | ici n'affecte pas les saisons deja creees.
    */
    'config' => [
        'daily_actions' => 5,
        'build_step' => 20,
        'upkeep_hour' => '12:00',
        'vote_hours' => 12,
        'soldier_upkeep' => ['gold' => 1, 'food' => 1],
        'desertion_rate' => 0.2,
        'upkeep_loyalty_penalty' => 5,
    ],

    /*
    | Capacite des stocks de ville. Les soldats n'en ont pas : leur nombre est
    | borne par l'entretien, pas par un grenier.
    */
    'storage_capacity' => 1000,
    'uncapped_resources' => ['soldiers'],

    'towns' => [
        'Paris' => [
            'position' => [480, 300],
            'resources' => ['gold', 'food', 'soldiers', 'wood', 'stone'],
            'pois' => [
                'town_hall' => [400, 300],
                'barracks' => [150, 230],
                'market' => [240, 520],
                'mine' => [735, 150],
                'farm' => [770, 470],
                'forest' => [785, 760],
            ],
        ],
        'Lyon' => [
            'position' => [590, 620],
            'resources' => ['gold', 'food', 'soldiers', 'wood', 'stone'],
            'pois' => [
                'town_hall' => [400, 300],
                'market' => [240, 520],
                'farm' => [770, 470],
                'forest' => [785, 760],
            ],
        ],
    ],

];
