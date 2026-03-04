# 1000 A.D.

A turn-based medieval strategy game — originally built in ColdFusion (circa 2000), now rewritten in Laravel 12.

Build your empire, train armies, research technologies, forge alliances, and conquer rivals across 120 months of medieval warfare.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Database:** SQLite (zero config)
- **Frontend:** Pure CSS + vanilla JS (no npm/Vite)
- **Icons:** AI-generated heraldic emblems via DALL-E/Midjourney

## Features

- 16 building types with production chains (wood, iron, gold, wine)
- 8 soldier types + 6 civilization-unique units (Vikings, Franks, Japanese, Byzantines, Mongols, Incas)
- Army attacks, catapult sieges, and thief espionage
- Alliance system with shared forums and aid
- Real-time turn timer with AJAX refresh
- Multi-game lobby — host multiple concurrent games
- Admin panel for game/player management
- Responsive dark fantasy UI (desktop + mobile)
- AI-generated icon system with fallback placeholders

## Quick Start (Local Development)

```bash
# Clone
git clone https://github.com/crameep/1000ad.git
cd 1000ad/laravel

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Create database and run migrations
touch database/database.sqlite
php artisan migrate

# Start the dev server
php artisan serve --host=127.0.0.1 --port=8000
```

Visit `http://127.0.0.1:8000` — register an account and start playing.

**Default admin:** `admin` / `admin`

> **Note:** Use `127.0.0.1` not `localhost` (cookie domain issue).

## Docker Deployment (Unraid / Portainer)

### Option 1: Portainer Stack (recommended)

1. In Portainer, go to **Stacks** > **Add Stack** > **Repository**
2. **Repository URL:** `https://github.com/crameep/1000ad`
3. **Compose path:** `laravel/docker-compose.yml`
4. Set the `APP_URL` environment variable to your server IP
5. Deploy

### Option 2: Docker Compose (CLI)

```bash
cd 1000ad/laravel

# Edit APP_URL in docker-compose.yml to your server IP
# e.g. APP_URL=http://192.168.1.100:8080

docker compose up -d --build
```

The game will be available at `http://your-server-ip:8080`.

### What the Docker setup does

- **PHP 8.3 + Apache** in a single container
- **Auto-generates** APP_KEY on first run
- **Auto-runs migrations** on every startup
- **Persists data** via Docker volumes:
  - `game-data` — SQLite database (survives rebuilds)
  - `game-sessions` — player sessions

### Rebuilding after code changes

```bash
docker compose up -d --build
```

Database is preserved in the volume. Only code/assets are rebuilt.

## Project Structure

```
laravel/
  app/
    Controllers/       # Game logic (Build, Army, Attack, etc.)
    Controllers/Admin/ # Admin panel (games, players)
    Helpers/game.php   # buildingIcon(), soldierIcon() helpers
    Models/            # Player, Game, User, queues, etc.
    Services/          # TurnService, GameDataService
  config/game.php      # Building/soldier/research definitions
  database/migrations/  # SQLite schema
  public/
    css/game.css       # All styles (no build step)
    js/game.js         # AJAX turns, toasts, timer
    images/icons/      # AI-generated building & soldier icons
    tools/             # Icon prompt generator
  resources/views/
    pages/             # Game pages (build, army, attack, etc.)
    layouts/           # Game, lobby, admin layouts
    components/        # game-icon Blade component
  routes/web.php       # All routes
```

## Icon System

Icons are AI-generated using prompts from `public/tools/icon-generator.html`.

Open that file in a browser, copy a prompt, paste into DALL-E or Midjourney, and save the result as a PNG to the appropriate `public/images/icons/` directory.

The prompts are designed for **32px readability** — flat heraldic emblems with bold silhouettes and 2-3 saturated colors, not detailed illustrations.

Missing icons automatically fall back to a placeholder SVG.

## License

MIT
