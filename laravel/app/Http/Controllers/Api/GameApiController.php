<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ReturnsJson;
use Illuminate\Support\Facades\Auth;

class GameApiController extends Controller
{
    use ReturnsJson;

    /**
     * Return current player state for resource bar refresh.
     * GET /game/api/state
     */
    public function state()
    {
        $player = Auth::user();

        return response()->json([
            'success' => true,
            'state' => $this->playerState($player),
        ]);
    }
}
