{{-- Resource bar - ported from index.cfm resource display --}}
<div class="resource-bar">
    <span>Score: {{ number_format($player->score) }}</span>
    <span>Population: {{ number_format($player->people) }}</span>
    <span>Gold: {{ number_format($player->gold) }}</span>
    <span>
        <form action="{{ route('game.end-turn') }}" method="POST" style="display:inline;">
            @csrf
            <a href="#" onclick="this.closest('form').submit(); return false;">END TURN</a>
        </form>
    </span>
</div>

{{-- Land and resources --}}
<table style="width:100%; margin: 2px 0;">
<tr>
    <td>
        <table style="border-collapse:collapse;">
        <tr>
            <td style="background:#633; padding:2px 4px;"><b>Total:</b></td>
            <td style="background:#633; padding:2px 4px; white-space:nowrap;">
                <img src="/images/mland.gif" alt="Mountain" style="vertical-align:middle;">{{ number_format($player->mland) }}
            </td>
            <td style="background:#633; padding:2px 4px; white-space:nowrap;">
                <img src="/images/fland.gif" alt="Forest" style="vertical-align:middle;">{{ number_format($player->fland) }}
            </td>
            <td style="background:#633; padding:2px 4px; white-space:nowrap;">
                <img src="/images/pland.gif" alt="Plains" style="vertical-align:middle;">{{ number_format($player->pland) }}
            </td>
        </tr>
        <tr>
            <td style="background:#363; padding:2px 4px;"><b>Free:</b></td>
            <td style="background:#363; padding:2px 4px; white-space:nowrap;">
                <img src="/images/mland_free.gif" alt="Free Mountain" style="vertical-align:middle;">{{ number_format($freeM) }}
            </td>
            <td style="background:#363; padding:2px 4px; white-space:nowrap;">
                <img src="/images/fland_free.gif" alt="Free Forest" style="vertical-align:middle;">{{ number_format($freeF) }}
            </td>
            <td style="background:#363; padding:2px 4px; white-space:nowrap;">
                <img src="/images/pland_free.gif" alt="Free Plains" style="vertical-align:middle;">{{ number_format($freeP) }}
            </td>
        </tr>
        </table>
    </td>
    <td style="text-align:right;">
        <table style="border-collapse:collapse;">
        <tr>
            <td style="white-space:nowrap;" title="You have {{ number_format($player->wood) }} wood available">
                <img src="/images/wood.gif" alt="Wood" style="vertical-align:middle;">{{ number_format($player->wood) }}
            </td>
            <td style="width:10px;">&nbsp;</td>
            <td style="white-space:nowrap;" title="You have {{ number_format($player->iron) }} iron available">
                <img src="/images/iron.gif" alt="Iron" style="vertical-align:middle;">{{ number_format($player->iron) }}
            </td>
        </tr>
        <tr>
            <td style="white-space:nowrap;" title="You have {{ number_format($player->food) }} food available">
                <img src="/images/food.gif" alt="Food" style="vertical-align:middle;">{{ number_format($player->food) }}
            </td>
            <td style="width:10px;">&nbsp;</td>
            <td style="white-space:nowrap;" title="You have {{ number_format($player->tools) }} tools available">
                <img src="/images/tools.gif" alt="Tools" style="vertical-align:middle;">{{ number_format($player->tools) }}
            </td>
        </tr>
        </table>
    </td>
</tr>
</table>
