<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AidController;
use App\Http\Controllers\AllianceController;
use App\Http\Controllers\ArmyController;
use App\Http\Controllers\AttackController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LobbyController;
use App\Http\Controllers\ManageController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ResearchController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\WallController;
use App\Http\Controllers\Api\GameApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes (no middleware)
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.forgot.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public rankings (accessible from login page)
Route::get('/rankings/{type?}', [ScoreController::class, 'publicRankings'])->name('rankings');

// Game documentation (accessible without login)
Route::get('/game/docs/{page?}', [DocsController::class, 'show'])->name('docs');

/*
|--------------------------------------------------------------------------
| Lobby Routes (auth required, no game context)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/lobby', [LobbyController::class, 'index'])->name('lobby');
    Route::post('/lobby/join/{game}', [LobbyController::class, 'join'])->name('lobby.join');
    Route::post('/lobby/switch/{game}', [LobbyController::class, 'switchGame'])->name('lobby.switch');
    Route::post('/lobby/switch-empire/{player}', [LobbyController::class, 'switchEmpire'])->name('lobby.switch-empire');

    // Stripe purchase routes
    Route::get('/purchase/empire-slot/{game}', [StripeController::class, 'checkout'])->name('stripe.checkout');
    Route::get('/purchase/success', [StripeController::class, 'success'])->name('stripe.success');
});

// Stripe webhook (no auth, no CSRF)
Route::post('/stripe/webhook', [StripeController::class, 'webhook'])->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Admin Routes (auth + admin middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('games', \App\Http\Controllers\Admin\GameManagementController::class);
    Route::post('games/{game}/duplicate', [\App\Http\Controllers\Admin\GameManagementController::class, 'duplicate'])->name('games.duplicate');
    // Finance
    Route::get('finance', [\App\Http\Controllers\Admin\FinanceController::class, 'index'])->name('finance.index');
    Route::post('finance/payout/{payout}/mark-paid', [\App\Http\Controllers\Admin\FinanceController::class, 'markPaid'])->name('finance.mark-paid');
    Route::post('finance/payout/{payout}/cancel', [\App\Http\Controllers\Admin\FinanceController::class, 'cancelPayout'])->name('finance.cancel-payout');

    Route::get('players', [\App\Http\Controllers\Admin\PlayerManagementController::class, 'index'])->name('players.index');
    Route::get('players/{user}', [\App\Http\Controllers\Admin\PlayerManagementController::class, 'show'])->name('players.show');
    Route::get('players/{player}/edit', [\App\Http\Controllers\Admin\PlayerManagementController::class, 'editPlayer'])->name('players.edit');
    Route::put('players/{player}', [\App\Http\Controllers\Admin\PlayerManagementController::class, 'updatePlayer'])->name('players.update');
    Route::post('players/{player}/grant-turns', [\App\Http\Controllers\Admin\PlayerManagementController::class, 'grantTurns'])->name('players.grant-turns');
});

/*
|--------------------------------------------------------------------------
| Game Routes (auth + game session middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'game.session'])->prefix('game')->group(function () {

    // Main game page
    Route::get('/', [GameController::class, 'index'])->name('game');
    Route::get('/main', [GameController::class, 'main'])->name('game.main');
    Route::post('/main/delete-news/{id}', [GameController::class, 'deleteNews'])->name('game.main.delete-news');
    Route::post('/main/delete-all-news', [GameController::class, 'deleteAllNews'])->name('game.main.delete-all-news');

    // Turn processing
    Route::post('/end-turn', [GameController::class, 'endTurn'])->name('game.end-turn');
    Route::post('/end-turns', [GameController::class, 'endMultipleTurns'])->name('game.end-turns');

    // Buildings
    Route::get('/build', [BuildingController::class, 'index'])->name('game.build');
    Route::post('/build', [BuildingController::class, 'build'])->name('game.build.submit');
    Route::post('/build/demolish', [BuildingController::class, 'demolish'])->name('game.build.demolish');
    Route::post('/build/cancel', [BuildingController::class, 'cancel'])->name('game.build.cancel');
    Route::post('/build/cancel-all', [BuildingController::class, 'cancelAll'])->name('game.build.cancel-all');
    Route::post('/build/move-top', [BuildingController::class, 'moveToTop'])->name('game.build.move-top');
    Route::post('/build/move-bottom', [BuildingController::class, 'moveToBottom'])->name('game.build.move-bottom');
    Route::post('/build/status', [BuildingController::class, 'updateStatus'])->name('game.build.status');

    // Army
    Route::get('/army', [ArmyController::class, 'index'])->name('game.army');
    Route::post('/army/train', [ArmyController::class, 'train'])->name('game.army.train');
    Route::post('/army/disband', [ArmyController::class, 'disband'])->name('game.army.disband');
    Route::post('/army/cancel', [ArmyController::class, 'cancelTraining'])->name('game.army.cancel');

    // Attack
    Route::get('/attack', [AttackController::class, 'index'])->name('game.attack');
    Route::post('/attack/launch', [AttackController::class, 'launch'])->name('game.attack.launch');
    Route::post('/attack/cancel', [AttackController::class, 'cancel'])->name('game.attack.cancel');

    // Explore
    Route::get('/explore', [ExploreController::class, 'index'])->name('game.explore');
    Route::post('/explore/send', [ExploreController::class, 'sendExplorers'])->name('game.explore.send');
    Route::post('/explore/cancel', [ExploreController::class, 'cancelExplore'])->name('game.explore.cancel');

    // Research
    Route::get('/research', [ResearchController::class, 'index'])->name('game.research');
    Route::post('/research', [ResearchController::class, 'setResearch'])->name('game.research.change');

    // Alliance
    Route::get('/alliance', [AllianceController::class, 'index'])->name('game.alliance');
    Route::post('/alliance/create', [AllianceController::class, 'createAlliance'])->name('game.alliance.create');
    Route::post('/alliance/join', [AllianceController::class, 'joinAlliance'])->name('game.alliance.join');
    Route::post('/alliance/leave', [AllianceController::class, 'leaveAlliance'])->name('game.alliance.leave');
    Route::post('/alliance/relations', [AllianceController::class, 'changeRelations'])->name('game.alliance.relations');
    Route::post('/alliance/news', [AllianceController::class, 'changeNews'])->name('game.alliance.news');
    Route::post('/alliance/password', [AllianceController::class, 'changePassword'])->name('game.alliance.password');
    Route::post('/alliance/disband', [AllianceController::class, 'disbandAlliance'])->name('game.alliance.disband');
    Route::post('/alliance/remove/{id}', [AllianceController::class, 'removeMember'])->name('game.alliance.remove');
    Route::post('/alliance/toggle-status/{id}', [AllianceController::class, 'toggleMemberStatus'])->name('game.alliance.toggle-status');
    Route::post('/alliance/give-leadership/{id}', [AllianceController::class, 'giveLeadership'])->name('game.alliance.give-leadership');

    // Aid
    Route::get('/aid', [AidController::class, 'index'])->name('game.aid');
    Route::post('/aid/send', [AidController::class, 'sendAid'])->name('game.aid.send');
    Route::post('/aid/cancel', [AidController::class, 'cancelAid'])->name('game.aid.cancel');

    // Local Trade
    Route::get('/localtrade', [TradeController::class, 'local'])->name('game.localtrade');
    Route::post('/localtrade/buy', [TradeController::class, 'localBuy'])->name('game.localtrade.buy');
    Route::post('/localtrade/sell', [TradeController::class, 'localSell'])->name('game.localtrade.sell');
    Route::post('/localtrade/autotrade', [TradeController::class, 'updateAutoTrade'])->name('game.localtrade.autotrade');

    // Global Market
    Route::get('/market/{type?}', [TradeController::class, 'global'])->name('game.market');
    Route::post('/market/sell', [TradeController::class, 'sellOnMarket'])->name('game.market.sell');
    Route::post('/market/buy', [TradeController::class, 'buyFromMarket'])->name('game.market.buy');
    Route::post('/market/withdraw/{id}', [TradeController::class, 'withdrawFromMarket'])->name('game.market.withdraw');

    // Messages
    Route::get('/messages/{folder?}', [MessageController::class, 'index'])->name('game.messages');
    Route::post('/messages/send', [MessageController::class, 'sendMessage'])->name('game.messages.send');
    Route::post('/messages/delete/{id}', [MessageController::class, 'deleteMessage'])->name('game.messages.delete');
    Route::post('/messages/delete-all', [MessageController::class, 'deleteAllMessages'])->name('game.messages.delete-all');
    Route::post('/messages/save/{id}', [MessageController::class, 'saveMessage'])->name('game.messages.save');
    Route::post('/messages/delete-all-saved', [MessageController::class, 'deleteAllSaved'])->name('game.messages.delete-all-saved');
    Route::post('/messages/block/{id}', [MessageController::class, 'addBlock'])->name('game.messages.block');
    Route::post('/messages/unblock/{id}', [MessageController::class, 'unblock'])->name('game.messages.unblock');
    Route::get('/messages/view/{id}', [MessageController::class, 'viewMessage'])->name('game.messages.view');

    // Management
    Route::get('/manage', [ManageController::class, 'index'])->name('game.manage');
    Route::post('/manage/weapons', [ManageController::class, 'changeWeaponProduction'])->name('game.manage.weapons');
    Route::post('/manage/food-ratio', [ManageController::class, 'changeFoodRatio'])->name('game.manage.food-ratio');
    Route::post('/manage/land', [ManageController::class, 'changeLand'])->name('game.manage.land');

    // Wall
    Route::get('/wall', [WallController::class, 'index'])->name('game.wall');
    Route::post('/wall', [WallController::class, 'updateWall'])->name('game.wall.update');

    // Status
    Route::get('/status', [StatusController::class, 'index'])->name('game.status');

    // Scores
    Route::get('/scores', [ScoreController::class, 'index'])->name('game.scores');
    Route::get('/battle-scores', [BattleController::class, 'battleScores'])->name('game.battle-scores');
    Route::get('/alliance-scores', [BattleController::class, 'allianceScores'])->name('game.alliance-scores');

    // Recent Battles
    Route::get('/recent-battles', [BattleController::class, 'index'])->name('game.recent-battles');
    Route::post('/recent-battles', [BattleController::class, 'search'])->name('game.recent-battles.search');
    Route::get('/recent-battles/{id}', [BattleController::class, 'viewDetail'])->name('game.recent-battles.detail');

    // Search
    Route::get('/search', [SearchController::class, 'index'])->name('game.search');
    Route::post('/search', [SearchController::class, 'search'])->name('game.search.submit');

    // Account
    Route::get('/account', [AccountController::class, 'index'])->name('game.account');
    Route::post('/account/login', [AccountController::class, 'changeLogin'])->name('game.account.login');
    Route::post('/account/password', [AccountController::class, 'changePassword'])->name('game.account.password');
    Route::post('/account/delete', [AccountController::class, 'deleteEmpire'])->name('game.account.delete');

    // Chat
    Route::get('/chat', [ChatController::class, 'index'])->name('game.chat');
    Route::post('/chat', [ChatController::class, 'postMessage'])->name('game.chat.post');

    // API endpoints (AJAX)
    Route::get('/api/state', [GameApiController::class, 'state'])->name('game.api.state');
});
