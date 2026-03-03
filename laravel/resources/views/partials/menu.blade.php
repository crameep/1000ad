{{-- Left navigation menu - ported from left_menu.cfm --}}
<div class="panel">
    <div class="panel-header">Menu</div>
    <div class="panel-body">
        <form action="{{ route('game.end-turns') }}" method="POST" id="endTurnForm">
            @csrf
            End <input type="number" name="turns" value="1" min="1" max="12" size="3" style="width:40px"> Turn(s)
            <input type="submit" value="   Go   ">
        </form>
        <br>

        <ul class="menu-list">
            <li>
                <a href="{{ route('game.main') }}">
                    @if($player->has_main_news)
                        <span style="color:red">MAIN</span>
                    @else
                        Main
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('game.messages') }}">
                    @if($player->has_new_messages)
                        <span style="color:red">NEW MESSAGE</span>
                    @else
                        Messages
                    @endif
                </a>
            </li>
            <li><a href="{{ route('game.build') }}">Buildings</a></li>
            <li><a href="{{ route('game.wall') }}">Great Wall</a></li>
            <li><a href="{{ route('game.explore') }}">Explore</a></li>
            <li><a href="{{ route('game.research') }}">Research</a></li>
            @if(!$deathmatchMode && config('game.alliance_max_members') > 0)
                <li><a href="{{ route('game.aid') }}">Aid</a></li>
            @endif
            <li><a href="{{ route('game.army') }}">Army</a></li>
            <li><a href="{{ route('game.attack') }}">Attack</a></li>
            @if(!$deathmatchMode && config('game.alliance_max_members') > 0)
                <li>
                    <a href="{{ route('game.alliance') }}">
                        @if($player->has_alliance_news)
                            <span style="color:red">ALLIANCE</span>
                        @else
                            Alliance
                        @endif
                    </a>
                </li>
            @endif
            <li><a href="{{ route('game.recent-battles') }}">Recent Battles</a></li>
            <li><a href="{{ route('game.manage') }}">Management</a></li>
            <li><a href="{{ route('game.status') }}">Status</a></li>
            <li><a href="{{ route('game.scores') }}">Scores</a></li>
            <li><a href="{{ route('game.localtrade') }}">Local Trade</a></li>
            @if(!$deathmatchMode)
                <li>Public Market<br>
                    &nbsp;&nbsp;&nbsp;
                    <a href="{{ route('game.market', ['type' => 'sell']) }}">Sell</a> |
                    <a href="{{ route('game.market', ['type' => 'buy']) }}">Buy</a>
                </li>
            @endif
            <br>
            <li><a href="{{ route('game.search') }}">Search</a></li>
            <li><a href="{{ route('game.account') }}">Account Options</a></li>
            <li>
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <a href="#" onclick="this.closest('form').submit(); return false;">Logout</a>
                </form>
            </li>
        </ul>
    </div>
</div>

<div class="panel">
    <div class="panel-header">Documentation</div>
    <div class="panel-body">
        <a href="javascript:openHelp('home')">Game Help / Docs</a>
    </div>
</div>
