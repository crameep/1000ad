<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ReturnsJson;

class GameApiController extends Controller
{
    use ReturnsJson;

    /**
     * Return current player state for resource bar refresh.
     * GET /game/api/state
     */
    public function state()
    {
        $player = player();

        return response()->json([
            'success' => true,
            'state' => $this->playerState($player),
        ]);
    }
}
