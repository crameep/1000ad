<?php

namespace App\Services;

/**
 * Game Data Service
 *
 * Provides building definitions, soldier definitions, and civilization modifiers.
 * Ported from startSession.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class GameDataService
{
    /**
     * Get all building definitions for a given civilization.
     *
     * @param int $civId Civilization ID (1-6)
     * @return array Array of building definition objects indexed 1-16
     */
    public function getBuildings(int $civId): array
    {
        $buildings = $this->getBaseBuildings();
        $this->applyCivBuildingModifiers($buildings, $civId);
        return $buildings;
    }

    /**
     * Get all soldier definitions for a given civilization.
     *
     * @param int $civId Civilization ID (1-6)
     * @return array Array of soldier definition objects indexed 1-9
     */
    public function getSoldiers(int $civId): array
    {
        $soldiers = $this->getBaseSoldiers();
        $this->applyCivSoldierModifiers($soldiers, $civId);
        return $soldiers;
    }

    /**
     * Get population/food constants for a given civilization.
     */
    public function getConstants(int $civId): array
    {
        $constants = [
            'people_eat_one_food' => gameConfig('people_eat_one_food'),
            'soldiers_eat_one_food' => gameConfig('soldiers_eat_one_food'),
            'extra_food_per_land' => gameConfig('extra_food_per_land'),
            'people_burn_one_wood' => gameConfig('people_burn_one_wood'),
        ];

        // Civ-specific constant overrides
        if ($civId === 1) { // Vikings
            $constants['people_burn_one_wood'] = 125;
        } elseif ($civId === 2) { // Franks
            $constants['extra_food_per_land'] = 840;
        } elseif ($civId === 4) { // Byzantines
            $constants['people_eat_one_food'] = 60;
        } elseif ($civId === 5) { // Mongols
            $constants['extra_food_per_land'] = 760;
        }

        return $constants;
    }

    /**
     * Get local trade prices.
     */
    public function getLocalPrices(): array
    {
        return gameConfig('local_prices');
    }

    /**
     * Base building definitions (before civ modifiers).
     */
    protected function getBaseBuildings(): array
    {
        $buildings = [];

        // 1: Wood Cutter
        $buildings[1] = [
            'name' => 'Wood Cutter',
            'db_column' => 'wood_cutter',
            'land' => 'F',
            'workers' => 6,
            'sq' => 4,
            'food_eaten' => 0,
            'cost_wood' => 2,
            'cost_iron' => 0,
            'cost_gold' => 25,
            'allow_off' => true,
            'production' => 4,
            'production_name' => 'wood',
            'description' => 'Produces wood. 6 workers, 4 wood/turn.',
        ];

        // 2: Hunter
        $buildings[2] = [
            'name' => 'Hunter',
            'db_column' => 'hunter',
            'land' => 'F',
            'workers' => 6,
            'sq' => 2,
            'food_eaten' => 0,
            'cost_wood' => 4,
            'cost_iron' => 0,
            'cost_gold' => 25,
            'allow_off' => true,
            'production' => 3,
            'production_name' => 'food',
            'description' => 'Produces food year-round. 6 workers, 3 food/turn.',
        ];

        // 3: Farm
        $buildings[3] = [
            'name' => 'Farm',
            'db_column' => 'farmer',
            'land' => 'P',
            'workers' => 12,
            'sq' => 4,
            'food_eaten' => 0,
            'cost_wood' => 8,
            'cost_iron' => 1,
            'cost_gold' => 25,
            'allow_off' => true,
            'production' => 8,
            'production_name' => 'food',
            'description' => 'Produces food in spring/summer only (Apr-Sep). 12 workers, 8 food/turn.',
        ];

        // 4: House
        $buildings[4] = [
            'name' => 'House',
            'db_column' => 'house',
            'land' => 'P',
            'workers' => 0,
            'sq' => 2,
            'food_eaten' => 1,
            'cost_wood' => 4,
            'cost_iron' => 0,
            'cost_gold' => 100,
            'allow_off' => false,
            'people' => 100,
            'production_name' => '',
            'description' => 'Provides housing for 100 people. No workers needed.',
        ];

        // 5: Iron Mine
        $buildings[5] = [
            'name' => 'Iron Mine',
            'db_column' => 'iron_mine',
            'land' => 'M',
            'workers' => 8,
            'sq' => 2,
            'cost_wood' => 6,
            'cost_iron' => 0,
            'cost_gold' => 100,
            'allow_off' => true,
            'production' => 1,
            'production_name' => 'iron',
            'description' => 'Produces iron. 8 workers, 1 iron/turn.',
        ];

        // 6: Gold Mine
        $buildings[6] = [
            'name' => 'Gold Mine',
            'db_column' => 'gold_mine',
            'land' => 'M',
            'workers' => 12,
            'sq' => 6,
            'cost_wood' => 10,
            'cost_iron' => 10,
            'cost_gold' => 1000,
            'allow_off' => true,
            'production' => 100,
            'production_name' => 'gold',
            'description' => 'Produces gold. 12 workers, 100 gold/turn.',
        ];

        // 7: Tool Maker
        $buildings[7] = [
            'name' => 'Tool Maker',
            'db_column' => 'tool_maker',
            'land' => 'P',
            'workers' => 10,
            'sq' => 2,
            'food_eaten' => 0,
            'cost_wood' => 6,
            'cost_iron' => 2,
            'cost_gold' => 200,
            'allow_off' => true,
            'production' => 1,
            'wood_need' => 2,
            'iron_need' => 2,
            'production_name' => 'tools',
            'num_builders' => 6,
            'description' => 'Produces tools and speeds construction. Consumes 2 wood + 2 iron/turn.',
        ];

        // 8: Weaponsmith
        $buildings[8] = [
            'name' => 'Weaponsmith',
            'db_column' => 'weapon_smith',
            'land' => 'P',
            'workers' => 10,
            'sq' => 4,
            'food_eaten' => 0,
            'cost_wood' => 10,
            'cost_iron' => 4,
            'cost_gold' => 600,
            'allow_off' => true,
            'production' => 1,
            'wood_need' => 25,
            'iron_need' => 25,
            'mace_wood' => 6,
            'mace_iron' => 6,
            'production_name' => 'weapons',
            'description' => 'Produces swords, bows, and maces. Consumes wood + iron.',
        ];

        // 9: Fort
        $buildings[9] = [
            'name' => 'Fort',
            'db_column' => 'fort',
            'land' => 'P',
            'workers' => 0,
            'sq' => 12,
            'food_eaten' => 2,
            'cost_wood' => 20,
            'cost_iron' => 8,
            'cost_gold' => 1000,
            'allow_off' => false,
            'max_train' => 2,
            'max_units' => 15,
            'need_gold' => 25,
            'production_name' => '',
            'description' => 'Trains and garrisons up to 15 military units.',
        ];

        // 10: Tower
        $buildings[10] = [
            'name' => 'Tower',
            'db_column' => 'tower',
            'land' => 'P',
            'workers' => 0,
            'sq' => 4,
            'food_eaten' => 0,
            'cost_wood' => 20,
            'cost_iron' => 20,
            'cost_gold' => 400,
            'allow_off' => false,
            'production_name' => '',
            'description' => 'Defensive structure. Increases land defense strength.',
        ];

        // 11: Town Center
        $buildings[11] = [
            'name' => 'Town Center',
            'db_column' => 'town_center',
            'land' => 'P',
            'workers' => 0,
            'sq' => 25,
            'food_eaten' => 0,
            'cost_wood' => 100,
            'cost_iron' => 40,
            'cost_gold' => 2500,
            'allow_off' => false,
            'max_units' => 10,
            'people' => 100,
            'supplies' => 1000,
            'max_explorers' => 6,
            'food_per_explorer' => 5,
            'production_name' => '',
            'max_local_trades' => 100,
            'description' => 'Houses 100 people, stores 1000 supplies, enables 6 explorers per center.',
        ];

        // 12: Market
        $buildings[12] = [
            'name' => 'Market',
            'db_column' => 'market',
            'land' => 'P',
            'workers' => 6,
            'sq' => 4,
            'food_eaten' => 0,
            'cost_wood' => 20,
            'cost_iron' => 2,
            'cost_gold' => 250,
            'allow_off' => false,
            'max_trades' => 50,
            'production_name' => '',
            'description' => 'Enables 50 public market trades per market.',
        ];

        // 13: Warehouse
        $buildings[13] = [
            'name' => 'Warehouse',
            'db_column' => 'warehouse',
            'land' => 'P',
            'workers' => 4,
            'sq' => 2,
            'food_eaten' => 0,
            'cost_wood' => 15,
            'cost_iron' => 0,
            'cost_gold' => 100,
            'allow_off' => false,
            'supplies' => 2500,
            'production_name' => '',
            'description' => 'Stores 2500 supplies, protecting them from raids.',
        ];

        // 14: Stable
        $buildings[14] = [
            'name' => 'Stable',
            'db_column' => 'stable',
            'land' => 'P',
            'workers' => 12,
            'sq' => 4,
            'food_eaten' => 0,
            'cost_wood' => 10,
            'cost_iron' => 2,
            'cost_gold' => 200,
            'allow_off' => true,
            'production' => 1,
            'food_need' => 100,
            'production_name' => 'horses',
            'description' => 'Produces horses. Consumes 100 food/turn. 12 workers.',
        ];

        // 15: Mage Tower
        $buildings[15] = [
            'name' => 'Mage Tower',
            'db_column' => 'mage_tower',
            'land' => 'P',
            'workers' => 20,
            'sq' => 10,
            'food_eaten' => 0,
            'cost_wood' => 50,
            'cost_iron' => 50,
            'cost_gold' => 2000,
            'allow_off' => true,
            'production' => 1,
            'gold_need' => 100,
            'production_name' => 'research points',
            'description' => 'Produces research points. Consumes 100 gold/turn. 20 workers.',
        ];

        // 16: Winery
        $buildings[16] = [
            'name' => 'Winery',
            'db_column' => 'winery',
            'land' => 'P',
            'workers' => 12,
            'sq' => 6,
            'food_eaten' => 0,
            'cost_wood' => 10,
            'cost_iron' => 2,
            'cost_gold' => 1000,
            'allow_off' => true,
            'production' => 1,
            'gold_need' => 10,
            'production_name' => 'wine',
            'description' => 'Produces wine and 10 gold. Consumes 10 gold/turn. 12 workers.',
        ];

        return $buildings;
    }

    /**
     * Base soldier definitions (before civ modifiers).
     */
    protected function getBaseSoldiers(): array
    {
        $soldiers = [];

        // 1: Archer
        $soldiers[1] = [
            'name' => 'Archer',
            'db_name' => 'archers',
            'turns' => 6,
            'attack_pt' => 4,
            'defense_pt' => 12,
            'gold_per_turn' => 3,
            'take_land' => 0.05,
        ];

        // 2: Swordsman
        $soldiers[2] = [
            'name' => 'Swordsman',
            'db_name' => 'swordsman',
            'turns' => 4,
            'attack_pt' => 8,
            'defense_pt' => 6,
            'gold_per_turn' => 3,
            'take_land' => 0.10,
        ];

        // 3: Horseman
        $soldiers[3] = [
            'name' => 'Horseman',
            'db_name' => 'horseman',
            'turns' => 8,
            'attack_pt' => 10,
            'defense_pt' => 10,
            'gold_per_turn' => 5,
            'take_land' => 0.15,
        ];

        // 4: Tower (defensive only)
        $soldiers[4] = [
            'name' => 'Tower',
            'db_name' => 'tower',
            'turns' => 0,
            'attack_pt' => 0,
            'defense_pt' => 50,
            'gold_per_turn' => 0,
            'take_land' => 0,
        ];

        // 5: Catapult
        $soldiers[5] = [
            'name' => 'Catapult',
            'db_name' => 'catapults',
            'turns' => 8,
            'attack_pt' => 25,
            'defense_pt' => 25,
            'gold_per_turn' => 0,
            'train_cost' => 250,
            'take_land' => 0,
        ];

        // 6: Macemen
        $soldiers[6] = [
            'name' => 'Macemen',
            'db_name' => 'macemen',
            'turns' => 3,
            'attack_pt' => 6,
            'defense_pt' => 3,
            'gold_per_turn' => 2,
            'take_land' => 0.06,
        ];

        // 7: Trained Peasant
        $soldiers[7] = [
            'name' => 'Trained Peasant',
            'db_name' => 'trained_peasants',
            'turns' => 1,
            'attack_pt' => 1,
            'defense_pt' => 2,
            'gold_per_turn' => 0.1,
            'take_land' => 0.01,
        ];

        // 8: Thieves
        $soldiers[8] = [
            'name' => 'Thieves',
            'db_name' => 'thieves',
            'turns' => 10,
            'attack_pt' => 50,
            'defense_pt' => 55,
            'gold_per_turn' => 25,
            'take_land' => 0,
        ];

        // 9: Unique Unit (base - modified per civ)
        $soldiers[9] = [
            'name' => 'Unique Unit',
            'db_name' => 'uunit',
            'turns' => 12,
            'attack_pt' => 1,
            'defense_pt' => 1,
            'gold_per_turn' => 25,
            'train_gold' => 1000,
            'train_swords' => 0,
            'train_bows' => 0,
            'train_horses' => 0,
            'take_land' => 0,
        ];

        return $soldiers;
    }

    /**
     * Apply civilization-specific building modifiers.
     */
    protected function applyCivBuildingModifiers(array &$buildings, int $civId): void
    {
        switch ($civId) {
            case 1: // Vikings
                $buildings[14]['sq'] = 6;            // Stable: more land
                $buildings[14]['food_need'] = 150;    // Stable: more food
                $buildings[13]['supplies'] = 1250;    // Warehouse: half storage
                $buildings[4]['people'] = 75;         // House: fewer people
                $buildings[3]['production'] = 6;      // Farm: less food
                $buildings[1]['sq'] = 2;              // Wood Cutter: less land
                $buildings[1]['production'] = 6;      // Wood Cutter: more wood
                $buildings[2]['production'] = 5;      // Hunter: more food
                $buildings[5]['production'] = 2;      // Iron Mine: more iron
                break;

            case 2: // Franks
                $buildings[11]['sq'] = 35;            // Town Center: more land
                $buildings[9]['max_units'] = 12;      // Fort: fewer units
                $buildings[15]['sq'] = 12;            // Mage Tower: more land
                $buildings[15]['workers'] = 15;       // Mage Tower: fewer workers needed
                $buildings[3]['sq'] = 2;              // Farm: less land
                $buildings[7]['num_builders'] = 10;   // Tool Maker: more builders
                $buildings[10]['sq'] = 3;             // Tower: less land
                $buildings[10]['cost_wood'] = 10;     // Tower: cheaper
                $buildings[10]['cost_iron'] = 10;     // Tower: cheaper
                $buildings[11]['max_explorers'] = 7;  // Town Center: more explorers
                break;

            case 3: // Japanese
                $buildings[2]['production'] = 2;      // Hunter: less food
                $buildings[1]['sq'] = 5;              // Wood Cutter: more land
                $buildings[12]['max_trades'] = 40;    // Market: fewer trades
                $buildings[14]['sq'] = 8;             // Stable: more land
                $buildings[14]['food_need'] = 125;    // Stable: more food
                $buildings[3]['production'] = 10;     // Farm: more food
                $buildings[4]['people'] = 120;        // House: more people
                $buildings[11]['sq'] = 20;            // Town Center: less land
                $buildings[15]['production'] = 1.5;   // Mage Tower: more research
                break;

            case 4: // Byzantines
                $buildings[5]['sq'] = 3;              // Iron Mine: more land
                $buildings[7]['num_builders'] = 5;    // Tool Maker: fewer builders
                $buildings[6]['sq'] = 2;              // Gold Mine: less land
                $buildings[6]['production'] = 200;    // Gold Mine: more gold
                $buildings[11]['sq'] = 22;            // Town Center: less land
                $buildings[12]['max_trades'] = 100;   // Market: more trades
                $buildings[13]['supplies'] = 5000;    // Warehouse: double storage
                $buildings[15]['sq'] = 8;             // Mage Tower: less land
                break;

            case 5: // Mongols
                $buildings[11]['max_explorers'] = 5;  // Town Center: fewer explorers
                $buildings[15]['gold_need'] = 200;    // Mage Tower: more gold needed
                $buildings[3]['production'] = 6;      // Farm: less food
                $buildings[9]['sq'] = 8;              // Fort: less land
                $buildings[9]['max_units'] = 20;      // Fort: more units
                $buildings[8]['production'] = 2;      // Weaponsmith: more production
                $buildings[14]['production'] = 2;     // Stable: more horses
                $buildings[7]['production'] = 2;      // Tool Maker: more tools
                $buildings[7]['num_builders'] = 8;    // Tool Maker: more builders
                $buildings[2]['production'] = 4;      // Hunter: more food
                break;

            case 6: // Incas
                $buildings[15]['gold_need'] = 40;     // Mage Tower: less gold
                $buildings[11]['people'] = 100;       // Town Center: population
                $buildings[11]['supplies'] = 5000;    // Town Center: more storage
                $buildings[12]['max_trades'] = 100;   // Market: more trades
                $buildings[11]['sq'] = 30;            // Town Center: more land
                $buildings[5]['sq'] = 3;              // Iron Mine: more land
                break;
        }
    }

    /**
     * Apply civilization-specific soldier modifiers.
     */
    protected function applyCivSoldierModifiers(array &$soldiers, int $civId): void
    {
        switch ($civId) {
            case 1: // Vikings - Berserker
                $soldiers[9]['name'] = 'Berserker';
                $soldiers[9]['attack_pt'] = 25;
                $soldiers[9]['defense_pt'] = 5;
                $soldiers[9]['train_swords'] = 5;
                $soldiers[9]['train_horses'] = 1;
                $soldiers[9]['train_bows'] = 1;
                $soldiers[9]['take_land'] = 0.30;
                break;

            case 2: // Franks - Paladin
                $soldiers[9]['name'] = 'Paladin';
                $soldiers[9]['attack_pt'] = 5;
                $soldiers[9]['defense_pt'] = 30;
                $soldiers[9]['train_swords'] = 6;
                $soldiers[9]['train_horses'] = 1;
                $soldiers[9]['take_land'] = 0.30;
                // Franks bonuses
                $soldiers[4]['defense_pt'] = 65;      // Tower: more defense
                $soldiers[1]['defense_pt'] = 15;      // Archer: more defense
                break;

            case 3: // Japanese - Samurai
                $soldiers[9]['name'] = 'Samurai';
                $soldiers[9]['attack_pt'] = 20;
                $soldiers[9]['defense_pt'] = 10;
                $soldiers[9]['train_swords'] = 10;
                $soldiers[9]['take_land'] = 0.50;
                break;

            case 4: // Byzantines - Cataphract
                $soldiers[9]['name'] = 'Cataphract';
                $soldiers[9]['attack_pt'] = 15;
                $soldiers[9]['defense_pt'] = 15;
                $soldiers[9]['train_swords'] = 1;
                $soldiers[9]['train_horses'] = 1;
                $soldiers[9]['train_bows'] = 1;
                $soldiers[9]['take_land'] = 0.20;
                // Byzantine bonuses
                $soldiers[1]['defense_pt'] = 14;      // Archer: more defense
                $soldiers[5]['defense_pt'] = 30;      // Catapult: more defense
                $soldiers[5]['attack_pt'] = 30;       // Catapult: more attack
                break;

            case 5: // Mongols - Horse Archer
                $soldiers[9]['name'] = 'Horse Archer';
                $soldiers[9]['attack_pt'] = 20;
                $soldiers[9]['defense_pt'] = 5;
                $soldiers[9]['train_horses'] = 1;
                $soldiers[9]['train_bows'] = 1;
                $soldiers[9]['take_land'] = 0.15;
                $soldiers[9]['turns'] = 10;
                $soldiers[9]['train_gold'] = 100;
                $soldiers[9]['gold_per_turn'] = 5;
                break;

            case 6: // Incas - Shaman
                $soldiers[9]['name'] = 'Shaman';
                $soldiers[9]['attack_pt'] = 1;
                $soldiers[9]['defense_pt'] = 1;
                $soldiers[9]['train_horses'] = 0;
                $soldiers[9]['train_bows'] = 0;
                $soldiers[9]['take_land'] = 5;
                $soldiers[9]['turns'] = 14;
                $soldiers[9]['train_gold'] = 5000;
                $soldiers[9]['gold_per_turn'] = 50;
                // Inca bonuses
                $soldiers[8]['defense_pt'] = 80;      // Thieves: more defense
                $soldiers[2]['attack_pt'] = 9;        // Swordsman: more attack
                $soldiers[6]['attack_pt'] = 8;        // Macemen: more attack
                // Inca penalties
                $soldiers[3]['turns'] = 80;           // Horseman: useless
                $soldiers[3]['attack_pt'] = 1;
                $soldiers[3]['defense_pt'] = 1;
                $soldiers[5]['attack_pt'] = 16;       // Catapult: weaker
                $soldiers[5]['defense_pt'] = 20;
                break;
        }
    }

    /**
     * Get the display order for buildings (matching original CF loop order).
     */
    public function getBuildingDisplayOrder(): array
    {
        return [2, 3, 1, 5, 6, 16, 7, 8, 14, 15, 9, 10, 11, 12, 13, 4];
    }

    /**
     * Get research names by ID.
     */
    public function getResearchNames(): array
    {
        return [
            1 => 'Attack Points',
            2 => 'Defense Points',
            3 => 'Thieves Strength',
            4 => 'Military Losses',
            5 => 'Food Production',
            6 => 'Mine Production',
            7 => 'Weapons/Tools Production',
            8 => 'Space Effectiveness',
            9 => 'Markets Output',
            10 => 'Explorers',
            11 => 'Catapults Strength',
            12 => 'Wood Production',
        ];
    }
}
