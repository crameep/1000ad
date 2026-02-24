# CLAUDE.md — AI Assistant Guide for 1000 AD

## Project Overview

**1000 AD** is a turn-based medieval strategy web game built in **ColdFusion (CFML)** with a **Microsoft SQL Server** backend. Players control one of six civilizations, manage resources, build structures, train armies, conduct research, and wage war against other players. The game was developed by Andrew Deren at AderSoftware (2000–2001), current version **1.6.0**.

## Tech Stack

- **Server-side:** ColdFusion 4.5+ (CFML — tag-based scripting language)
- **Database:** Microsoft SQL Server 7.0+
- **Web server:** IIS 5 (or compatible ColdFusion-capable server)
- **Client-side:** Vanilla HTML 4.0, inline CSS, vanilla JavaScript (no frameworks)
- **Search:** Verity full-text search engine (for in-game documentation)
- **No build system** — no npm, no bundler, no package manager. Files are served directly.

## Repository Structure

```
/
├── Application.cfm            # Global app config (DSN, game settings, session setup)
├── Database/
│   └── 1000ad.sql             # Full MSSQL database schema
├── docs/                      # In-game help documentation (CFM pages + image assets)
├── images/                    # Game UI image assets (GIF/JPG)
├── install.txt                # Installation/setup instructions
├── changelog.txt              # Version history (v1.1 through v1.6.0)
├── ai.txt                     # AI player behavior notes
├── *.cfm                      # ~80 ColdFusion template files (see breakdown below)
```

### File Organization by Function

**Core Game Pages** (included from `index.cfm`):
| File | Purpose |
|------|---------|
| `index.cfm` | Main dashboard/router; sets up game state |
| `status.cfm` | Player resource and army status display |
| `army.cfm` | Army management and unit training |
| `attack.cfm` | Attack queue management |
| `build.cfm` | Building construction interface |
| `research.cfm` | Technology research interface |
| `explore.cfm` | Land exploration |
| `alliance.cfm` | Alliance creation and management |
| `localMarket.cfm` / `globalMarket.cfm` | Trading systems |
| `player_messages.cfm` | In-game messaging |
| `wall.cfm` | Great Wall construction |
| `manage.cfm` | Empire management |

**Game Logic (Turn Processing):**
| File | Purpose |
|------|---------|
| `end_turn.cfm` | Main turn processor (~1275 lines): resource production, building, training, upkeep |
| `doAttack.cfm` | Army battle resolution algorithm (~750 lines) |
| `doAttack2.cfm` | Thief attack resolution (~345 lines) |
| `doAttack3.cfm` | Catapult attack resolution (~343 lines) |

**Session & Authentication:**
| File | Purpose |
|------|---------|
| `startSession.cfm` | Session init; defines all building/unit properties as CFScript structs |
| `login.cfm` | Login/logout and account validation |
| `createPlayer.cfm` | New player registration |
| `validate.cfm` | Email validation |

**Event Handlers** (`eflag_*.cfm` — form/action processing):
| File | Purpose |
|------|---------|
| `eflag_main.cfm` | Main page actions |
| `eflag_attack.cfm` | Attack command processing |
| `eflag_build.cfm` | Build queue operations |
| `eflag_army.cfm` | Army training operations |
| `eflag_explore.cfm` | Exploration queue |
| `eflag_research.cfm` | Research operations |
| `eflag_alliance.cfm` | Alliance operations |
| `eflag_aid.cfm` | Resource transfers |
| `eflag_endturn.cfm` | End-turn trigger |
| `eflag_localtrade.cfm` | Local market operations |
| `eflag_globalmarket.cfm` | Global market operations |
| `eflag_wall.cfm` | Wall building operations |
| `eflag_manage.cfm` | General management |
| `eflag_player_messages.cfm` | Message operations |
| `eflag_account.cfm` | Account settings |

**UI Components** (included fragments):
- `left_menu.cfm` — Left sidebar navigation
- `top_menu.cfm` — Top navigation bar
- `main.cfm` — Main page layout
- `utils_menu.cfm` — Utility links
- `forumsMenu.cfm` — Forum navigation
- `news.cfm` — Game news (editable content)

**Scoring & Display:**
- `scores.cfm` / `scores_show.cfm` — Leaderboards
- `rank.cfm` — Player rankings
- `alliance_scores.cfm` / `battle_scores.cfm` — Alliance and battle rankings
- `recent_battles.cfm` — Battle history viewer
- `calc_score.cfm` / `calc_score_all.cfm` — Score calculation logic

**Utility:**
- `chat.cfm` / `forum.cfm` — Social features
- `docs.cfm` — Documentation viewer/renderer
- `search.cfm` / `search_reindex.cfm` — Documentation search
- `logger.cfm` — Logging utility
- `attack_sim.cfm` — Attack simulator
- `ai.cfm` — AI player logic
- `finish_game.cfm` — Game end handling
- `forgotpassword.cfm` — Password recovery

**Test/Dev:**
- `test.cfm` — Basic test page
- `testland.cfm` — Land exploration testing

## Architecture

### Routing

The application uses **page-based routing**. `index.cfm` is the main entry point and acts as a router based on URL parameters:
- The `page` parameter determines which sub-page to include
- The `eflag` parameter triggers server-side actions via `eflag_*.cfm` handlers

### Data Flow

1. `Application.cfm` — Runs on every request; sets global config variables, DSN, game constants
2. `startSession.cfm` — Initializes session with building/unit definitions as ColdFusion structs
3. `index.cfm` — Routes to appropriate page; loads player data from DB into session
4. `eflag_*.cfm` — Processes form submissions and game actions
5. `end_turn.cfm` — Processes accumulated turns (resource production, construction, combat)

### Session Management

- ColdFusion server-managed sessions with 2-hour timeout
- Game state prefixed with `gameCode` (e.g., `session.GTSTplayerID`) to allow multiple game instances
- `cfparam` used for default values: `<cfparam name="session.playerID" default="0">`

### Database Access

- Direct SQL queries using `<cfquery>` tags with inline SQL
- No ORM — all queries are hand-written
- Query results accessed as ColdFusion query objects (e.g., `p.columnName`)
- DSN configured in `Application.cfm` (`dsn`, `dsn_login`, `dsn_pw`)

### Key Database Tables

| Table | Purpose |
|-------|---------|
| `Player` | Player accounts, resources, buildings, army, research levels |
| `Alliance` | Alliance data, war/ally lists |
| `attackQueue` | Scheduled attacks |
| `buildQueue` | Building construction queue |
| `trainQueue` | Unit training queue |
| `transferQueue` | Resource aid transfers |
| `exploreQueue` | Land exploration queue |
| `attackNews` | Battle results history |
| `PlayerMessage` | Direct messages |
| `forumMessage` | Forum posts |
| `aidLog` | Aid transfer records |
| `loginEntry` | Login audit trail |
| `aiPlayer` | AI player markers |

## Game Mechanics

### Turn System
- Real-time turns: 1 turn = 5 real minutes (configurable via `minutesPerTurn`)
- Turn number maps to in-game date: `year = floor(turn / 12) + 1000`, `month = (turn mod 12) + 1`
- Season affects farming (April–October growing season)
- Max stored turns: 500

### Civilizations (6)
1. **Vikings** — Unique unit: Berserker
2. **Franks** — Unique unit: Paladin
3. **Japanese** — Unique unit: Samurai (1.5x research per town center)
4. **Byzantines** — Unique unit: Cataphract
5. **Mongols** — Unique unit: Horse Archer
6. **Incas** — Unique unit: Shaman (no horsemen)

### Resources
Wood, Food, Iron, Gold, Tools, Weapons, Horses, Wine

### Buildings (16 types)
Defined as structs in `startSession.cfm`. Categories: resource production (wood cutters, hunters, farms, mines, wineries), capacity (houses, warehouses), military (forts, town centers, mage towers), economy (markets), defense (wall).

### Military Units (9+ types)
Archers, Swordsmen, Horsemen, Macemen, Peasants, Catapults, Thieves, Mages, plus civilization-unique units.

### Research (12 tech trees)
1. Attack Points, 2. Defense Points, 3. Thief Strength, 4. Military Loss Reduction, 5. Food Production, 6. Mine Production, 7. Weapons/Tools Production, 8. Space Effectiveness, 9. Markets Output, 10. Exploration, 11. Catapult Strength, 12. Wood Production

### Combat
Three attack types with separate resolution files:
- `doAttack.cfm` — Standard army attacks (attack vs defense point comparison)
- `doAttack2.cfm` — Thief operations (steal resources, intel)
- `doAttack3.cfm` — Catapult sieges (destroy buildings)

Attack penalties apply for repeatedly attacking the same target. Score ratio affects army morale (revolt mechanics against much weaker targets).

### Game Modes
- **Standard** — Normal play with alliances (up to 10 members)
- **Deathmatch** — No alliances, free-for-all
- **Tournament** — No alliances, competitive

## Code Conventions

### Language Patterns
- **CFML tag syntax** for most logic: `<cfset>`, `<cfif>`, `<cfquery>`, `<cfloop>`, `<cfinclude>`
- **CFScript blocks** for complex struct initialization in `startSession.cfm`
- **Dynamic variable names**: `setVariable("session.#gameCode#playerID", p.id)`
- **Comments**: `<!--- ColdFusion comment --->` syntax

### Variable Naming
- Query result variables: short names (`p` for player, `aq` for attack queue, `tq` for train queue)
- Resource abbreviations: `fland` (forest), `mland` (mountain), `pland` (plains)
- Building/unit properties defined as numbered array elements (e.g., `session.buildings[1]`)
- No consistent naming convention (mix of camelCase and lowercase)

### HTML/CSS
- Table-based layouts (no CSS grid/flexbox)
- Inline styles and `<font>` tags (HTML 4.0 era)
- DHTML features noted as "IE Only" in some places

### Error Handling
- Minimal — no try/catch blocks, limited input validation
- `cfparam` used for default values on session/form variables

## Configuration

All configuration is in `Application.cfm`:

| Variable | Purpose |
|----------|---------|
| `dsn` | Database DSN name |
| `dsn_login` / `dsn_pw` | Database credentials |
| `filePath` | Local filesystem path to game files |
| `required_host` | Expected hostname (redirects mismatched hosts) |
| `webpath` | Full web URL |
| `gameName` | Display name for login screen |
| `gameCode` | Unique prefix for session variables (multi-game support) |
| `minutesPerTurn` | Real minutes per game turn (default: 5) |
| `maxTurnsStored` | Maximum accumulated turns (default: 500) |
| `startTurns` | Starting turns for new players (default: 100) |
| `allianceMaxMembers` | Max alliance size (default: 10; 0 for deathmatch) |
| `startGameDate` / `endGameDate` | Game period |
| `deathmatchMode` | Enable deathmatch mode |
| `mailserver` / `adminEmail` | Email configuration |

## Installation & Deployment

1. Copy files to a web-accessible directory on a ColdFusion-capable web server
2. Create MSSQL database and run `Database/1000ad.sql`
3. Configure a DSN pointing to the database
4. Edit `Application.cfm` with correct DSN, paths, hostname, and game settings
5. Run `docs/search_reindex.cfm` to index documentation for in-game search
6. No build step required — files are served directly

## Known Issues & Technical Debt

- **SQL injection vulnerability**: Queries use inline string interpolation without parameterized queries (`#playerID#` directly in SQL)
- **No input sanitization**: Limited validation on form inputs
- **No unit tests**: Only ad-hoc test pages (`test.cfm`, `testland.cfm`)
- **No CI/CD**: Manual deployment only
- **Code duplication**: Attack resolution split across three similar files (`doAttack.cfm`, `doAttack2.cfm`, `doAttack3.cfm`)
- **IE-specific features**: Some DHTML features only work in Internet Explorer
- **Hardcoded paths**: File system paths and URLs hardcoded in `Application.cfm`

## Working with This Codebase

### Key Files to Read First
1. `Application.cfm` — Understand global configuration
2. `startSession.cfm` — Understand game data structures (buildings, units)
3. `index.cfm` — Understand routing and page structure
4. `end_turn.cfm` — Understand core game loop
5. `doAttack.cfm` — Understand battle mechanics

### Making Changes
- All game constants (building stats, unit stats) are defined in `startSession.cfm`
- Game balance changes typically involve `startSession.cfm` (stats), `end_turn.cfm` (production/upkeep), and `doAttack*.cfm` (combat)
- UI changes are in the individual page `.cfm` files
- Action/form handlers are in `eflag_*.cfm` files
- Database schema changes require updating `Database/1000ad.sql` and running migrations manually
- There is no build or compilation step — save the file and reload the page

### Testing
- No automated test framework exists
- Test by loading pages in a browser with a running ColdFusion server
- `test.cfm` and `testland.cfm` provide basic manual testing
- `attack_sim.cfm` provides an attack simulation tool
