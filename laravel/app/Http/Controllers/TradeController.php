<?php

namespace App\Http\Controllers;

use App\Http\Traits\ReturnsJson;
use App\Models\Player;
use App\Models\PlayerMessage;
use App\Models\TransferQueue;
use App\Services\GameAdvisorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Trade Controller
 *
 * Handles local trading and global market operations.
 * Ported from localtrade.cfm, eflag_localtrade.cfm, globalMarket.cfm, eflag_globalmarket.cfm
 *
 * Original: Andrew Deren, (C) AderSoftware 2000, 2001
 */
class TradeController extends Controller
{
    use ReturnsJson;

    protected GameAdvisorService $advisorService;

    public function __construct(GameAdvisorService $advisorService)
    {
        $this->advisorService = $advisorService;
    }

    /**
     * Show the local trade page.
     * Ported from localtrade.cfm
     *
     * Route: GET /localtrade -> game.localtrade
     */
    public function local()
    {
        $player = player();
        $buildings = session('buildings');

        // Calculate max trades
        $maxTrades = self::calculateMaxTrades($player, $buildings);
        $tradesRemaining = $maxTrades - $player->trades_this_turn;

        // Calculate price multiplier based on score
        $extra = $this->calculatePriceMultiplier($player->score);

        // Get base prices from config
        $localPrices = gameConfig('local_prices');

        $woodBuyPrice = round($localPrices['wood']['buy'] * $extra);
        $foodBuyPrice = round($localPrices['food']['buy'] * $extra);
        $ironBuyPrice = round($localPrices['iron']['buy'] * $extra);
        $toolBuyPrice = round($localPrices['tools']['buy'] * $extra);

        $woodSellPrice = round($localPrices['wood']['sell'] * (1.0 / $extra));
        $foodSellPrice = round($localPrices['food']['sell'] * (1.0 / $extra));
        $ironSellPrice = round($localPrices['iron']['sell'] * (1.0 / $extra));
        $toolSellPrice = round($localPrices['tools']['sell'] * (1.0 / $extra));

        // Calculate auto-trade gold usage
        $autoTradeGoldUsed = $player->auto_buy_wood * $woodBuyPrice
            + $player->auto_buy_food * $foodBuyPrice
            + $player->auto_buy_iron * $ironBuyPrice
            + $player->auto_buy_tools * $toolBuyPrice;

        $autoTradeGoldEarned = $player->auto_sell_wood * $woodSellPrice
            + $player->auto_sell_food * $foodSellPrice
            + $player->auto_sell_iron * $ironSellPrice
            + $player->auto_sell_tools * $toolSellPrice;

        $totalAutoTrade = $player->auto_buy_wood + $player->auto_buy_food
            + $player->auto_buy_iron + $player->auto_buy_tools
            + $player->auto_sell_wood + $player->auto_sell_food
            + $player->auto_sell_iron + $player->auto_sell_tools;

        $remAutoTrade = $maxTrades - $totalAutoTrade;

        // Advisor tips
        $advisorTips = $this->advisorService->getTradeTips($player);

        return view('pages.trade.local', [
            'maxTrades' => $maxTrades,
            'tradesRemaining' => $tradesRemaining,
            'woodBuyPrice' => $woodBuyPrice,
            'foodBuyPrice' => $foodBuyPrice,
            'ironBuyPrice' => $ironBuyPrice,
            'toolBuyPrice' => $toolBuyPrice,
            'woodSellPrice' => $woodSellPrice,
            'foodSellPrice' => $foodSellPrice,
            'ironSellPrice' => $ironSellPrice,
            'toolSellPrice' => $toolSellPrice,
            'totalAutoTrade' => $totalAutoTrade,
            'remAutoTrade' => $remAutoTrade,
            'autoTradeGoldUsed' => $autoTradeGoldUsed,
            'autoTradeGoldEarned' => $autoTradeGoldEarned,
            'advisorTips' => $advisorTips,
        ]);
    }

    /**
     * Buy resources locally.
     * Ported from eflag_localtrade.cfm eflag=localbuy
     *
     * Route: POST /localtrade/buy -> game.localtrade.buy
     */
    public function localBuy(Request $request)
    {
        $player = player();
        $buildings = session('buildings');

        $maxTrades = self::calculateMaxTrades($player, $buildings);
        $tradesRemaining = $maxTrades - $player->trades_this_turn;
        $extra = $this->calculatePriceMultiplier($player->score);

        $localPrices = gameConfig('local_prices');
        $woodPrice = round($localPrices['wood']['buy'] * $extra);
        $foodPrice = round($localPrices['food']['buy'] * $extra);
        $ironPrice = round($localPrices['iron']['buy'] * $extra);
        $toolPrice = round($localPrices['tools']['buy'] * $extra);

        $buyWood = max(0, (int) str_replace(',', '', $request->input('buy_wood', 0)));
        $buyFood = max(0, (int) str_replace(',', '', $request->input('buy_food', 0)));
        $buyIron = max(0, (int) str_replace(',', '', $request->input('buy_iron', 0)));
        $buyTools = max(0, (int) str_replace(',', '', $request->input('buy_tools', 0)));

        $totalNewTrades = $buyWood + $buyFood + $buyIron + $buyTools;
        $needGold = $buyWood * $woodPrice + $buyFood * $foodPrice
            + $buyIron * $ironPrice + $buyTools * $toolPrice;

        if ($buyWood < 0 || $buyIron < 0 || $buyFood < 0 || $buyTools < 0) {
            if ($request->expectsJson()) {
                return $this->jsonError('Cannot buy negative amounts.');
            }
            session()->flash('game_message', 'Cannot buy negative amounts.');
            return redirect()->route('game.localtrade');
        }

        if ($totalNewTrades > $tradesRemaining) {
            if ($request->expectsJson()) {
                return $this->jsonError('You can only trade ' . number_format($tradesRemaining) . ' more goods this month.');
            }
            session()->flash('game_message', 'You can only trade ' . number_format($tradesRemaining) . ' more goods this month.');
            return redirect()->route('game.localtrade');
        }

        if ($needGold > $player->gold) {
            if ($request->expectsJson()) {
                return $this->jsonError('You do not have enough gold to buy those goods (you need ' . number_format($needGold) . ' gold).');
            }
            session()->flash('game_message', 'You do not have enough gold to buy those goods (you need ' . number_format($needGold) . ' gold).');
            return redirect()->route('game.localtrade');
        }

        // Execute the purchase
        $player->update([
            'wood' => $player->wood + $buyWood,
            'food' => $player->food + $buyFood,
            'iron' => $player->iron + $buyIron,
            'tools' => $player->tools + $buyTools,
            'gold' => $player->gold - $needGold,
            'trades_this_turn' => $player->trades_this_turn + $totalNewTrades,
        ]);

        $message = '';
        if ($buyWood > 0) {
            $message .= number_format($buyWood) . ' wood bought for ' . number_format($buyWood * $woodPrice) . ' gold.<br>';
        }
        if ($buyFood > 0) {
            $message .= number_format($buyFood) . ' food bought for ' . number_format($buyFood * $foodPrice) . ' gold.<br>';
        }
        if ($buyIron > 0) {
            $message .= number_format($buyIron) . ' iron bought for ' . number_format($buyIron * $ironPrice) . ' gold.<br>';
        }
        if ($buyTools > 0) {
            $message .= number_format($buyTools) . ' tools bought for ' . number_format($buyTools * $toolPrice) . ' gold.<br>';
        }
        $message .= 'You spent a total of ' . number_format($needGold) . ' gold.';

        if ($request->expectsJson()) {
            $newRemaining = $maxTrades - $player->trades_this_turn;
            return $this->jsonSuccess($player, $message, [
                'tradesRemaining' => $newRemaining,
                'maxTrades' => $maxTrades,
            ]);
        }

        session()->flash('game_message', $message);
        return redirect()->route('game.localtrade');
    }

    /**
     * Sell resources locally.
     * Ported from eflag_localtrade.cfm eflag=localsell
     *
     * Route: POST /localtrade/sell -> game.localtrade.sell
     */
    public function localSell(Request $request)
    {
        $player = player();
        $buildings = session('buildings');

        $maxTrades = self::calculateMaxTrades($player, $buildings);
        $tradesRemaining = $maxTrades - $player->trades_this_turn;
        $extra = $this->calculatePriceMultiplier($player->score);

        $localPrices = gameConfig('local_prices');
        $woodPrice = round($localPrices['wood']['sell'] * (1.0 / $extra));
        $foodPrice = round($localPrices['food']['sell'] * (1.0 / $extra));
        $ironPrice = round($localPrices['iron']['sell'] * (1.0 / $extra));
        $toolPrice = round($localPrices['tools']['sell'] * (1.0 / $extra));

        $sellWood = max(0, (int) str_replace(',', '', $request->input('sell_wood', 0)));
        $sellFood = max(0, (int) str_replace(',', '', $request->input('sell_food', 0)));
        $sellIron = max(0, (int) str_replace(',', '', $request->input('sell_iron', 0)));
        $sellTools = max(0, (int) str_replace(',', '', $request->input('sell_tools', 0)));

        $totalNewTrades = $sellWood + $sellFood + $sellIron + $sellTools;

        if ($sellWood < 0 || $sellIron < 0 || $sellFood < 0 || $sellTools < 0) {
            if ($request->expectsJson()) {
                return $this->jsonError('Cannot sell negative amounts.');
            }
            session()->flash('game_message', 'Cannot sell negative amounts.');
            return redirect()->route('game.localtrade');
        }

        if ($totalNewTrades > $tradesRemaining) {
            if ($request->expectsJson()) {
                return $this->jsonError('You can only trade ' . number_format($tradesRemaining) . ' more goods this turn.');
            }
            session()->flash('game_message', 'You can only trade ' . number_format($tradesRemaining) . ' more goods this turn.');
            return redirect()->route('game.localtrade');
        }

        if ($sellWood > $player->wood) {
            if ($request->expectsJson()) {
                return $this->jsonError('You do not have that much wood to sell.');
            }
            session()->flash('game_message', 'You do not have that much wood to sell.');
            return redirect()->route('game.localtrade');
        }
        if ($sellFood > $player->food) {
            if ($request->expectsJson()) {
                return $this->jsonError('You do not have that much food to sell.');
            }
            session()->flash('game_message', 'You do not have that much food to sell.');
            return redirect()->route('game.localtrade');
        }
        if ($sellIron > $player->iron) {
            if ($request->expectsJson()) {
                return $this->jsonError('You do not have that much iron to sell.');
            }
            session()->flash('game_message', 'You do not have that much iron to sell.');
            return redirect()->route('game.localtrade');
        }
        if ($sellTools > $player->tools) {
            if ($request->expectsJson()) {
                return $this->jsonError('You do not have that many tools to sell.');
            }
            session()->flash('game_message', 'You do not have that many tools to sell.');
            return redirect()->route('game.localtrade');
        }

        $getGold = $sellWood * $woodPrice + $sellFood * $foodPrice
            + $sellIron * $ironPrice + $sellTools * $toolPrice;

        $player->update([
            'wood' => $player->wood - $sellWood,
            'food' => $player->food - $sellFood,
            'iron' => $player->iron - $sellIron,
            'tools' => $player->tools - $sellTools,
            'gold' => $player->gold + $getGold,
            'trades_this_turn' => $player->trades_this_turn + $totalNewTrades,
        ]);

        $message = '';
        if ($sellWood > 0) {
            $message .= number_format($sellWood) . ' wood sold for ' . number_format($sellWood * $woodPrice) . ' gold.<br>';
        }
        if ($sellFood > 0) {
            $message .= number_format($sellFood) . ' food sold for ' . number_format($sellFood * $foodPrice) . ' gold.<br>';
        }
        if ($sellIron > 0) {
            $message .= number_format($sellIron) . ' iron sold for ' . number_format($sellIron * $ironPrice) . ' gold.<br>';
        }
        if ($sellTools > 0) {
            $message .= number_format($sellTools) . ' tools sold for ' . number_format($sellTools * $toolPrice) . ' gold.<br>';
        }
        $message .= 'You made a total of ' . number_format($getGold) . ' gold.';

        if ($request->expectsJson()) {
            $newRemaining = $maxTrades - $player->trades_this_turn;
            return $this->jsonSuccess($player, $message, [
                'tradesRemaining' => $newRemaining,
                'maxTrades' => $maxTrades,
            ]);
        }

        session()->flash('game_message', $message);
        return redirect()->route('game.localtrade');
    }

    /**
     * Update auto-trade settings.
     * Ported from eflag_localtrade.cfm eflag=updateautotrade
     *
     * Route: POST /localtrade/autotrade -> game.localtrade.autotrade
     */
    public function updateAutoTrade(Request $request)
    {
        $player = player();
        $buildings = session('buildings');

        $maxTrades = self::calculateMaxTrades($player, $buildings);

        $bWood = max(0, (int) str_replace(',', '', $request->input('auto_buy_wood', 0)));
        $bFood = max(0, (int) str_replace(',', '', $request->input('auto_buy_food', 0)));
        $bIron = max(0, (int) str_replace(',', '', $request->input('auto_buy_iron', 0)));
        $bTools = max(0, (int) str_replace(',', '', $request->input('auto_buy_tools', 0)));
        $sWood = max(0, (int) str_replace(',', '', $request->input('auto_sell_wood', 0)));
        $sFood = max(0, (int) str_replace(',', '', $request->input('auto_sell_food', 0)));
        $sIron = max(0, (int) str_replace(',', '', $request->input('auto_sell_iron', 0)));
        $sTools = max(0, (int) str_replace(',', '', $request->input('auto_sell_tools', 0)));

        if ($bFood < 0 || $bWood < 0 || $bIron < 0 || $bTools < 0
            || $sFood < 0 || $sWood < 0 || $sIron < 0 || $sTools < 0) {
            if ($request->expectsJson()) {
                return $this->jsonError('Cannot sell or buy negative numbers.');
            }
            session()->flash('game_message', 'Cannot sell or buy negative numbers.');
            return redirect()->route('game.localtrade');
        }

        $totalAutoTrade = $bFood + $bIron + $bWood + $bTools + $sFood + $sWood + $sIron + $sTools;

        if ($totalAutoTrade > $maxTrades) {
            if ($request->expectsJson()) {
                return $this->jsonError('You can only trade up to ' . number_format($maxTrades) . ' goods each month.');
            }
            session()->flash('game_message', 'You can only trade up to ' . number_format($maxTrades) . ' goods each month.');
            return redirect()->route('game.localtrade');
        }

        $player->update([
            'auto_buy_wood' => $bWood,
            'auto_sell_wood' => $sWood,
            'auto_buy_iron' => $bIron,
            'auto_sell_iron' => $sIron,
            'auto_buy_food' => $bFood,
            'auto_sell_food' => $sFood,
            'auto_buy_tools' => $bTools,
            'auto_sell_tools' => $sTools,
        ]);

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Auto-trade settings updated.');
        }

        return redirect()->route('game.localtrade');
    }

    /**
     * Show the global market page.
     * Ported from globalMarket.cfm
     *
     * Route: GET /market/{type?} -> game.market
     */
    public function global(Request $request, ?string $type = null)
    {
        $player = player();
        $buildings = session('buildings');

        // Block in deathmatch mode
        if (gameConfig('deathmatch_mode')) {
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        $mType = $type ?? $request->input('mtype', 'sell');
        $maxTrades = self::calculateMaxTrades($player, $buildings);
        $tradesRemaining = $maxTrades - $player->trades_this_turn;

        $tradePrices = gameConfig('trade_prices');

        // Advisor tips
        $advisorTips = $this->advisorService->getTradeTips($player);

        if ($mType === 'sell') {
            // Show sell form + dispatched caravans
            $caravans = TransferQueue::where('from_player_id', $player->id)
                ->where('transfer_type', 0)
                ->orderBy('turns_remaining', 'desc')
                ->orderBy('id')
                ->get();

            return view('pages.trade.global', [
                'mType' => 'sell',
                'maxTrades' => $maxTrades,
                'tradesRemaining' => $tradesRemaining,
                'tradePrices' => $tradePrices,
                'caravans' => $caravans,
                'advisorTips' => $advisorTips,
            ]);
        }

        // Buy mode - show available offers for each good type
        $goodTypes = ['wood', 'food', 'iron', 'tools', 'maces', 'swords', 'bows', 'horses'];
        $marketOffers = [];

        // Get all player IDs belonging to this user in this game (multi-empire guard)
        $userPlayerIds = Player::where('user_id', Auth::id())
            ->pluck('id')
            ->toArray();

        foreach ($goodTypes as $good) {
            $offers = TransferQueue::whereNotIn('from_player_id', $userPlayerIds)
                ->where('transfer_type', 0)
                ->where('turns_remaining', 0)
                ->where("{$good}_price", '>', 0)
                ->where($good, '>', 0)
                ->orderBy("{$good}_price")
                ->get()
                ->map(function ($offer) use ($good, $player) {
                    $offer->stuff = $offer->{$good};
                    $offer->stuff_price = $offer->{"{$good}_price"};
                    $canAfford = (int) floor($player->gold / max(1, $offer->stuff_price));
                    $offer->can_afford = min($canAfford, $offer->stuff);
                    return $offer;
                });
            $marketOffers[$good] = $offers;
        }

        // Incoming caravans (purchased goods in transit)
        $incomingCaravans = TransferQueue::where('to_player_id', $player->id)
            ->where('transfer_type', 2)
            ->orderBy('turns_remaining', 'desc')
            ->orderBy('id')
            ->get();

        return view('pages.trade.global', [
            'mType' => 'buy',
            'maxTrades' => $maxTrades,
            'tradesRemaining' => $tradesRemaining,
            'tradePrices' => $tradePrices,
            'marketOffers' => $marketOffers,
            'goodTypes' => $goodTypes,
            'incomingCaravans' => $incomingCaravans,
            'advisorTips' => $advisorTips,
        ]);
    }

    /**
     * Sell goods on the global market.
     * Ported from eflag_globalmarket.cfm eflag=sellOnPubMarket
     *
     * Route: POST /market/sell -> game.market.sell
     */
    public function sellOnMarket(Request $request)
    {
        $player = player();
        $buildings = session('buildings');

        if (gameConfig('deathmatch_mode')) {
            if ($request->expectsJson()) {
                return $this->jsonError('Cannot view this page in deathmatch game.');
            }
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        $tradePrices = gameConfig('trade_prices');
        $goodTypes = ['wood', 'food', 'iron', 'tools', 'maces', 'swords', 'bows', 'horses'];

        $sellQty = [];
        $sellPrice = [];
        $sendOK = true;
        $message = '';

        foreach ($goodTypes as $good) {
            $sellQty[$good] = max(0, (int) str_replace(',', '', $request->input("sell_{$good}", 0)));
            $sellPrice[$good] = max(0, (int) str_replace(',', '', $request->input("price_{$good}", 0)));

            $playerQty = $player->{$good};
            $minPrice = $tradePrices[$good]['min'] ?? 0;
            $maxPrice = $tradePrices[$good]['max'] ?? 99999;

            if ($sellQty[$good] < 0) {
                $message .= "Cannot sell negative {$good}.<br>";
                $sendOK = false;
            } elseif ($sellQty[$good] > 0 && $sellQty[$good] > $playerQty) {
                $message .= "You don't have that many {$good}. You only have {$playerQty}.<br>";
                $sendOK = false;
            } elseif ($sellQty[$good] > 0 && $sellPrice[$good] < $minPrice) {
                $message .= "The minimum sell price for {$good} is {$minPrice} gold.<br>";
                $sendOK = false;
            } elseif ($sellQty[$good] > 0 && $sellPrice[$good] > $maxPrice) {
                $message .= "The maximum sell price for {$good} is {$maxPrice} gold.<br>";
                $sendOK = false;
            }
        }

        if (!$sendOK) {
            if ($request->expectsJson()) {
                return $this->jsonError($message);
            }
            session()->flash('game_message', $message);
            return redirect()->route('game.market', ['type' => 'sell']);
        }

        $totalSell = array_sum($sellQty);
        $maxTrades = self::calculateMaxTrades($player, $buildings);
        $tradesRemaining = $maxTrades - $player->trades_this_turn;

        if ($totalSell == 0) {
            if ($request->expectsJson()) {
                return $this->jsonError('Cannot sell 0 goods.');
            }
            session()->flash('game_message', 'Cannot sell 0 goods.');
            return redirect()->route('game.market', ['type' => 'sell']);
        }

        if ($totalSell > $tradesRemaining) {
            if ($request->expectsJson()) {
                return $this->jsonError('You can sell only ' . $tradesRemaining . ' more goods this month.');
            }
            session()->flash('game_message', 'You can sell only ' . $tradesRemaining . ' more goods this month.');
            return redirect()->route('game.market', ['type' => 'sell']);
        }

        // Deduct goods from player
        $player->update([
            'wood' => $player->wood - $sellQty['wood'],
            'food' => $player->food - $sellQty['food'],
            'iron' => $player->iron - $sellQty['iron'],
            'tools' => $player->tools - $sellQty['tools'],
            'maces' => $player->maces - $sellQty['maces'],
            'swords' => $player->swords - $sellQty['swords'],
            'bows' => $player->bows - $sellQty['bows'],
            'horses' => $player->horses - $sellQty['horses'],
            'trades_this_turn' => $player->trades_this_turn + $totalSell,
        ]);

        // Create transfer queue entry
        TransferQueue::create([
            'from_player_id' => $player->id,
            'to_player_id' => 0,
            'wood' => $sellQty['wood'],
            'food' => $sellQty['food'],
            'iron' => $sellQty['iron'],
            'tools' => $sellQty['tools'],
            'maces' => $sellQty['maces'],
            'swords' => $sellQty['swords'],
            'bows' => $sellQty['bows'],
            'horses' => $sellQty['horses'],
            'transfer_type' => 0,
            'turns_remaining' => 3,
            'wood_price' => $sellPrice['wood'],
            'food_price' => $sellPrice['food'],
            'iron_price' => $sellPrice['iron'],
            'tools_price' => $sellPrice['tools'],
            'maces_price' => $sellPrice['maces'],
            'swords_price' => $sellPrice['swords'],
            'bows_price' => $sellPrice['bows'],
            'horses_price' => $sellPrice['horses'],
        ]);

        // Calculate total value
        $totalValue = 0;
        foreach ($goodTypes as $good) {
            $totalValue += $sellQty[$good] * $sellPrice[$good];
        }

        $message = 'Goods have been sent to the public market. They will reach the market in 3 months.<br>';
        $message .= 'Total value of the transport is ' . number_format($totalValue) . '.';

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, $message);
        }

        session()->flash('game_message', $message);
        return redirect()->route('game.market', ['type' => 'sell']);
    }

    /**
     * Buy goods from the global market.
     * Ported from eflag_globalmarket.cfm eflag=buyFromPubMarket
     *
     * Route: POST /market/buy -> game.market.buy
     */
    public function buyFromMarket(Request $request)
    {
        $player = player();

        if (gameConfig('deathmatch_mode')) {
            if ($request->expectsJson()) {
                return $this->jsonError('Cannot view this page in deathmatch game.');
            }
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        $good = $request->input('good', '');
        $validGoods = ['wood', 'food', 'iron', 'tools', 'maces', 'swords', 'bows', 'horses'];

        if (!in_array($good, $validGoods)) {
            if ($request->expectsJson()) {
                return $this->jsonError('Invalid good type.');
            }
            return redirect()->route('game.market', ['type' => 'buy']);
        }

        // Get available offers for this good type (exclude all user's empires)
        $userPlayerIds = Player::where('user_id', Auth::id())
            ->pluck('id')
            ->toArray();

        $offers = TransferQueue::whereNotIn('from_player_id', $userPlayerIds)
            ->where('transfer_type', 0)
            ->where('turns_remaining', 0)
            ->where("{$good}_price", '>', 0)
            ->where($good, '>', 0)
            ->orderBy("{$good}_price")
            ->get();

        $iHaveGold = $player->gold;
        $message = '';

        foreach ($offers as $offer) {
            $qty = max(0, (int) str_replace(',', '', $request->input("qty{$offer->id}", 0)));

            if ($qty <= 0) {
                continue;
            }

            $available = $offer->{$good};
            $price = $offer->{"{$good}_price"};

            if ($qty > $available) {
                $message .= "You tried to buy {$qty} {$good}, but there are only {$available} available.<br>";
                continue;
            }

            $cost = $qty * $price;
            if ($cost > $iHaveGold) {
                $message .= "You do not have enough gold to buy {$qty} {$good}. You need " . number_format($cost) . " gold.<br>";
                continue;
            }

            // Execute the purchase
            $message .= number_format($qty) . " {$good} bought for " . number_format($cost) . ". The caravans with {$good} will reach your empire in 3 months.<br>";

            // Deduct gold from buyer
            $player->update(['gold' => $player->gold - $cost]);
            $player->refresh();
            $iHaveGold -= $cost;

            // Update or remove the offer
            $remainGoods = $offer->wood + $offer->food + $offer->iron + $offer->tools
                + $offer->maces + $offer->swords + $offer->bows + $offer->horses - $qty;

            if ($remainGoods > 0) {
                $offer->update([$good => $offer->{$good} - $qty]);
            } else {
                $offer->delete();
            }

            // Give gold to seller (5% fee)
            $sellerGold = round($cost * 0.95);
            Player::where('id', $offer->from_player_id)
                ->update([
                    'gold' => DB::raw("gold + {$sellerGold}"),
                    'has_main_news' => true,
                ]);

            // Notify seller via news message
            $gameDate = $this->getGameDate($player);
            PlayerMessage::create([
                'from_player_id' => $player->id,
                'to_player_id' => $offer->from_player_id,
                'from_player_name' => $player->name,
                'to_player_name' => '',
                'message' => "On {$gameDate} you sold " . number_format($qty) . " {$good} for " . number_format($sellerGold),
                'viewed' => 0,
                'created_on' => now(),
                'message_type' => 1,
            ]);

            // Add to existing incoming caravan or create new one
            $existingCaravan = TransferQueue::where('to_player_id', $player->id)
                ->where('transfer_type', 2)
                ->where('turns_remaining', 3)
                ->first();

            if ($existingCaravan) {
                $existingCaravan->update([
                    $good => $existingCaravan->{$good} + $qty,
                ]);
            } else {
                TransferQueue::create([
                    'to_player_id' => $player->id,
                    'from_player_id' => 0,
                    $good => $qty,
                    'transfer_type' => 2,
                    'turns_remaining' => 3,
                ]);
            }
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, $message ?: 'No purchases made.');
        }

        if (!empty($message)) {
            session()->flash('game_message', $message);
        }

        return redirect()->route('game.market', ['type' => 'buy']);
    }

    /**
     * Withdraw goods from the global market.
     * Ported from eflag_globalmarket.cfm eflag=takeFromPubMarket
     *
     * Route: POST /market/withdraw/{id} -> game.market.withdraw
     */
    public function withdrawFromMarket(Request $request, int $id)
    {
        $player = player();

        if (gameConfig('deathmatch_mode')) {
            if ($request->expectsJson()) {
                return $this->jsonError('Cannot view this page in deathmatch game.');
            }
            session()->flash('game_message', 'Cannot view this page in deathmatch game.');
            return redirect()->route('game.main');
        }

        $transfer = TransferQueue::where('from_player_id', $player->id)
            ->where('id', $id)
            ->first();

        if (!$transfer) {
            if ($request->expectsJson()) {
                return $this->jsonError('Transfer not found.');
            }
            return redirect()->route('game.market', ['type' => 'sell']);
        }

        // Return goods with 10% withdrawal fee
        $player->update([
            'wood' => $player->wood + round($transfer->wood * 0.9),
            'iron' => $player->iron + round($transfer->iron * 0.9),
            'food' => $player->food + round($transfer->food * 0.9),
            'tools' => $player->tools + round($transfer->tools * 0.9),
            'swords' => $player->swords + round($transfer->swords * 0.9),
            'bows' => $player->bows + round($transfer->bows * 0.9),
            'horses' => $player->horses + round($transfer->horses * 0.9),
            'maces' => $player->maces + round($transfer->maces * 0.9),
        ]);

        $transfer->delete();

        if ($request->expectsJson()) {
            return $this->jsonSuccess($player, 'Goods withdrawn from market with a 10% fee.');
        }

        session()->flash('game_message', 'Goods withdrawn from market with a 10% fee.');
        return redirect()->route('game.market', ['type' => 'sell']);
    }

    /**
     * Calculate max trades for a player.
     * Common formula used across local trade, global market, and aid.
     */
    public static function calculateMaxTrades(Player $player, array $buildings): int
    {
        $maxTrades = $player->market * ($buildings[12]['max_trades'] ?? 50);
        $researchBonus = $player->research9 * 10;
        $maxTrades = (int) round($maxTrades + $maxTrades * ($researchBonus / 100));

        if ($maxTrades == 0) {
            $maxTrades = $buildings[11]['max_local_trades'] ?? 100;
        }

        return $maxTrades;
    }

    /**
     * Calculate price multiplier based on player score.
     * Higher scores mean higher buy prices and lower sell prices.
     */
    protected function calculatePriceMultiplier(int $score): float
    {
        $extra = 1.0;
        $s = $score;
        $localTradeMulti = gameConfig('local_trade_multiplier');

        while ($s > 100000) {
            $extra += $localTradeMulti;
            $s /= 2;
        }

        return $extra;
    }

    /**
     * Get in-game date string.
     */
    protected function getGameDate(Player $player): string
    {
        $month = ($player->turn % 12) + 1;
        $year = intdiv($player->turn, 12) + 1000;
        return date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year;
    }
}
