{{-- Left navigation menu --}}
<div class="panel">
    <div class="panel-header">Menu</div>
    <div class="panel-body" style="padding:0;">
        <ul class="menu-list">
            <li>
                <a href="{{ route('game.main') }}">
                    @if($player->has_main_news)
                        <span class="text-error">&#9888; MAIN</span>
                    @else
                        Main
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('game.messages') }}">
                    @if($player->has_new_messages)
                        <span class="text-error">&#9993; NEW MESSAGE</span>
                    @else
                        Messages
                    @endif
                </a>
            </li>
            <li><a href="{{ route('game.build') }}">Buildings</a></li>
            <li><a href="{{ route('game.wall') }}">Great Wall</a></li>
            <li><a href="{{ route('game.explore') }}">Explore</a></li>
            <li><a href="{{ route('game.research') }}">Research</a></li>
            @if(!$deathmatchMode && gameConfig('alliance_max_members') > 0)
                <li><a href="{{ route('game.aid') }}">Aid</a></li>
            @endif
            <li><a href="{{ route('game.army') }}">Army</a></li>
            <li><a href="{{ route('game.attack') }}">Attack</a></li>
            @if(!$deathmatchMode && gameConfig('alliance_max_members') > 0)
                <li>
                    <a href="{{ route('game.alliance') }}">
                        @if($player->has_alliance_news)
                            <span class="text-error">&#9888; ALLIANCE</span>
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
                <li>
                    <a href="{{ route('game.market', ['type' => 'sell']) }}">Public Market</a>
                </li>
            @endif
            <li><a href="{{ route('game.search') }}">Search</a></li>
            <li><a href="{{ route('game.account') }}">Account Options</a></li>
            <li>
                <form action="{{ route('logout') }}" method="POST" class="inline-form">
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
