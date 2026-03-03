<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * Status Controller
 *
 * Displays empire status: goods, army, people, and monthly production summary.
 * Ported from status.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class StatusController extends Controller
{
    /**
     * Show the status page.
     * Ported from status.cfm
     */
    public function index()
    {
        $player = Auth::user();
        $buildings = session('buildings');
        $soldiers = session('soldiers');
        $constants = session('constants');

        // --- Goods ---
        $totalGoods = $player->wood + $player->iron + $player->food + $player->tools
            + $player->swords + $player->bows + $player->horses + $player->maces + $player->wine;

        $townCenterB = $buildings[11];
        $warehouseB = $buildings[13];
        $warehouseSpace = $player->town_center * $townCenterB['supplies']
            + $player->warehouse * $warehouseB['supplies'];
        $warehouseSpace = round($warehouseSpace + $warehouseSpace * ($player->research8 / 100));

        $extraSpace = $warehouseSpace - $totalGoods;

        // --- Army ---
        $totalArmy = $player->swordsman + $player->archers + $player->horseman
            + $player->trained_peasants + $player->macemen + $player->thieves
            + $player->catapults + $player->uunit;

        $fortB = $buildings[9];
        $maxArmy = $player->town_center * $townCenterB['max_units']
            + $player->fort * $fortB['max_units'];
        $armyFreeSpace = $maxArmy - $totalArmy;

        // --- People ---
        $houseB = $buildings[4];
        $houseSpace = $player->house * $houseB['people']
            + $player->town_center * $townCenterB['people'];
        $houseSpace = round($houseSpace + $houseSpace * ($player->research8 / 100));
        $peopleFreeSpace = $houseSpace - $player->people;

        // --- Unique unit name ---
        $uunitName = $soldiers[9]['name'];

        // --- Monthly Summary Calculations ---

        // Local trade multiplier
        $localTradeMulti = config('game.local_trade_multiplier');
        $extra = 1;
        $s = $player->score;
        while ($s > 100000) {
            $extra += $localTradeMulti;
            $s /= 2;
        }

        // Local trade buy/sell calculations
        $localPrices = config('game.local_prices');
        $buyWood = 0; $buyIron = 0; $buyFood = 0; $buyTools = 0; $buyGold = 0;

        if ($player->auto_buy_wood > 0) {
            $woodBuyPrice = round($localPrices['wood']['buy'] * $extra);
            $buyGold -= $woodBuyPrice * $player->auto_buy_wood;
            $buyWood += $player->auto_buy_wood;
        }
        if ($player->auto_buy_food > 0) {
            $foodBuyPrice = round($localPrices['food']['buy'] * $extra);
            $buyGold -= $foodBuyPrice * $player->auto_buy_food;
            $buyFood += $player->auto_buy_food;
        }
        if ($player->auto_buy_iron > 0) {
            $ironBuyPrice = round($localPrices['iron']['buy'] * $extra);
            $buyGold -= $ironBuyPrice * $player->auto_buy_iron;
            $buyIron += $player->auto_buy_iron;
        }
        if ($player->auto_buy_tools > 0) {
            $toolsBuyPrice = round($localPrices['tools']['buy'] * $extra);
            $buyGold -= $toolsBuyPrice * $player->auto_buy_tools;
            $buyTools += $player->auto_buy_tools;
        }
        if ($player->auto_sell_wood > 0) {
            $woodSellPrice = round($localPrices['wood']['sell'] * (1.0 / $extra));
            $buyGold += $woodSellPrice * $player->auto_sell_wood;
            $buyWood -= $player->auto_sell_wood;
        }
        if ($player->auto_sell_food > 0) {
            $foodSellPrice = round($localPrices['food']['sell'] * (1.0 / $extra));
            $buyGold += $foodSellPrice * $player->auto_sell_food;
            $buyFood -= $player->auto_sell_food;
        }
        if ($player->auto_sell_iron > 0) {
            $ironSellPrice = round($localPrices['iron']['sell'] * (1.0 / $extra));
            $buyGold += $ironSellPrice * $player->auto_sell_iron;
            $buyIron -= $player->auto_sell_iron;
        }
        if ($player->auto_sell_tools > 0) {
            $toolsSellPrice = round($localPrices['tools']['sell'] * (1.0 / $extra));
            $buyGold += $toolsSellPrice * $player->auto_sell_tools;
            $buyTools -= $player->auto_sell_tools;
        }

        // Wall construction
        $toolMakerB = $buildings[7];
        $wallCosts = config('game.wall');
        $wallBuilders_total = $toolMakerB['num_builders'] * $player->tool_maker + 3;
        $bPercent = $player->wall_build_per_turn / 100;
        $wallBuilders = round($wallBuilders_total * $bPercent);
        $wallBuild = intdiv($wallBuilders, 10);

        // GOLD
        $goldMineB = $buildings[6];
        $mageTowerB = $buildings[15];
        $getGold = round($player->gold_mine * ($player->gold_mine_status / 100)) * $goldMineB['production'];
        $goldProduction = $getGold + round($getGold * ($player->research6 / 100));

        $wallGoldUsage = $wallBuild * $wallCosts['gold'];
        $goldConsumption = 0 - (round(($player->mage_tower * ($player->mage_tower_status / 100)) * $mageTowerB['gold_need']) + $wallGoldUsage);

        // Military gold upkeep
        $payGold = 0 - round(
            $player->swordsman * $soldiers[2]['gold_per_turn']
            + $player->archers * $soldiers[1]['gold_per_turn']
            + $player->horseman * $soldiers[3]['gold_per_turn']
            + $player->macemen * $soldiers[6]['gold_per_turn']
            + $player->trained_peasants * $soldiers[7]['gold_per_turn']
            + $player->thieves * $soldiers[8]['gold_per_turn']
            + $player->uunit * $soldiers[9]['gold_per_turn']
        );

        $totalGold = $goldProduction + $goldConsumption + $payGold + $buyGold;

        // FOOD
        $hunterB = $buildings[2];
        $farmerB = $buildings[3];
        $stableB = $buildings[14];

        $canProduce = round($player->hunter * ($player->hunter_status / 100));
        $getFood = $canProduce * $hunterB['production'];
        $hunterProduction = $getFood + round($getFood * ($player->research5 / 100));

        $canProduce = round($player->farmer * ($player->farmer_status / 100));
        $getFood = $canProduce * $farmerB['production'];
        $farmerProduction = $getFood + round($getFood * ($player->research5 / 100));

        $stableConsumption = 0 - round($player->stable * ($player->stable_status / 100)) * $stableB['food_need'];

        // Army food consumption
        $numSoldiers = $player->swordsman + $player->archers + $player->horseman * 2
            + $player->macemen + round($player->trained_peasants * 0.1)
            + $player->thieves * 3 + $player->uunit * 2;
        $eatSoldiersFood = 0 - (int) ceil($numSoldiers / $constants['soldiers_eat_one_food']);

        // People food consumption
        $foodEaten = round($player->people / $constants['people_eat_one_food']);
        switch ($player->food_ratio) {
            case 1: $foodEaten = round($foodEaten * 1.5); break;
            case 2: $foodEaten = round($foodEaten * 2.5); break;
            case 3: $foodEaten = round($foodEaten * 4); break;
            case -1: $foodEaten = round($foodEaten * 0.75); break;
            case -2: $foodEaten = round($foodEaten * 0.45); break;
            case -3: $foodEaten = round($foodEaten * 0.25); break;
        }
        $foodEaten = 0 - $foodEaten;

        $totalFoodSummer = $hunterProduction + $farmerProduction + $stableConsumption + $eatSoldiersFood + $foodEaten + $buyFood;
        $totalFoodWinter = $hunterProduction + $stableConsumption + $eatSoldiersFood + $foodEaten + $buyFood;

        // WOOD
        $woodCutterB = $buildings[1];
        $weaponSmithB = $buildings[8];

        $canProduce = round($player->wood_cutter * ($player->wood_cutter_status / 100));
        $woodProduction = $canProduce * $woodCutterB['production'];

        $toolWoodConsumption = 0 - round($player->tool_maker * ($player->tool_maker_status / 100)) * $toolMakerB['wood_need'];

        $canProduce = round($player->bow_weapon_smith * ($player->weapon_smith_status / 100));
        $bowWoodConsumption = 0 - $canProduce * $weaponSmithB['wood_need'];

        $canProduce = round($player->mace_weapon_smith * ($player->weapon_smith_status / 100));
        $maceWoodConsumption = 0 - $canProduce * $weaponSmithB['mace_wood'];

        $wallWoodConsumption = 0 - $wallBuild * $wallCosts['wood'];

        $catapultWood = 0 - $player->catapults;

        $burnWood = 0 - round($player->people / $constants['people_burn_one_wood']);

        $totalWoodSummer = $woodProduction + $toolWoodConsumption + $bowWoodConsumption
            + $maceWoodConsumption + $buyWood + $catapultWood + $wallWoodConsumption;
        $totalWoodWinter = $totalWoodSummer + $burnWood;

        // IRON
        $ironMineB = $buildings[5];

        $canProduce = round($player->iron_mine * ($player->iron_mine_status / 100));
        $ironProduction = $canProduce * $ironMineB['production'];
        $ironProduction = $ironProduction + round($ironProduction * ($player->research6 / 100));

        $toolIronConsumption = 0 - round($player->tool_maker * ($player->tool_maker_status / 100)) * $toolMakerB['iron_need'];

        $canProduce = round($player->sword_weapon_smith * ($player->weapon_smith_status / 100));
        $swordIronConsumption = 0 - $canProduce * $weaponSmithB['iron_need'];

        $canProduce = round($player->mace_weapon_smith * ($player->weapon_smith_status / 100));
        $maceIronConsumption = 0 - $canProduce * $weaponSmithB['mace_iron'];

        $wallIronConsumption = 0 - $wallBuild * $wallCosts['iron'];

        $catapultIron = 0 - round($player->catapults / 5);

        $totalIron = $ironProduction + $toolIronConsumption + $swordIronConsumption
            + $maceIronConsumption + $buyIron + $catapultIron + $wallIronConsumption;

        return view('pages.status', compact(
            'totalGoods', 'warehouseSpace', 'extraSpace',
            'totalArmy', 'maxArmy', 'armyFreeSpace',
            'houseSpace', 'peopleFreeSpace', 'uunitName',
            // Gold
            'goldProduction', 'goldConsumption', 'payGold', 'buyGold', 'totalGold',
            // Food
            'hunterProduction', 'farmerProduction', 'stableConsumption',
            'eatSoldiersFood', 'foodEaten', 'buyFood',
            'totalFoodSummer', 'totalFoodWinter',
            // Wood
            'woodProduction', 'toolWoodConsumption', 'bowWoodConsumption',
            'maceWoodConsumption', 'wallWoodConsumption', 'catapultWood',
            'burnWood', 'buyWood', 'totalWoodSummer', 'totalWoodWinter',
            // Iron
            'ironProduction', 'toolIronConsumption', 'swordIronConsumption',
            'maceIronConsumption', 'wallIronConsumption', 'catapultIron',
            'buyIron', 'totalIron'
        ));
    }
}
