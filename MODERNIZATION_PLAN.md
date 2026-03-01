# 1000 AD — Full Modernization Plan

## Executive Summary

This plan migrates **1000 AD** from a ColdFusion 4.5 / MSSQL monolith (circa 2000) to a modern TypeScript full-stack application. The goal is a **complete rewrite** that preserves every game mechanic exactly while gaining: type safety, automated testing, CI/CD, containerized deployment, real-time updates, and a responsive modern UI.

**Target Stack:**

| Layer | Technology | Why |
|-------|-----------|-----|
| Backend Runtime | Node.js 20 LTS | Largest ecosystem, excellent async I/O for game server workloads |
| Backend Language | TypeScript 5.x | Type safety catches formula bugs at compile time; shared types with frontend |
| Backend Framework | Fastify | 2-3x faster than Express, built-in JSON schema validation, plugin architecture |
| ORM / Query Builder | Prisma | Type-safe database access, auto-generated types, excellent migration tooling |
| Database | PostgreSQL 16 | Open-source, free, feature-rich (JSONB for battle logs, CTEs for scoring) |
| Frontend Framework | React 19 + TypeScript | Component model ideal for complex game dashboards, massive ecosystem |
| Frontend Build | Vite 6 | Fast HMR, ESBuild-powered, zero-config TypeScript |
| UI Library | Tailwind CSS 4 + shadcn/ui | Utility-first CSS replaces table layouts; shadcn gives accessible components |
| State Management | TanStack Query + Zustand | Server state (TanStack) + client state (Zustand) separation |
| Real-time | WebSocket (native ws) | Live turn notifications, chat, attack alerts |
| Auth | Passport.js + JWT + bcrypt | Industry-standard auth with proper password hashing |
| Testing | Vitest + Playwright | Unit/integration (Vitest) + E2E (Playwright) |
| CI/CD | GitHub Actions | Automated lint, test, build, deploy pipeline |
| Containerization | Docker + Docker Compose | Reproducible environments, easy deployment |
| Monorepo | Turborepo | Shared types between frontend/backend, unified scripts |

**Estimated Scope:** ~80 ColdFusion files → ~200 TypeScript files across 10 phases.

---

## Phase 1: Project Scaffolding & Monorepo Setup

**Goal:** Set up the development environment, tooling, and project structure before writing any game logic.

### 1.1 Initialize Monorepo

```
1000ad-modern/
├── package.json                  # Root workspace config
├── turbo.json                    # Turborepo pipeline config
├── docker-compose.yml            # PostgreSQL + app services
├── .github/
│   └── workflows/
│       ├── ci.yml                # Lint + test + build on PR
│       └── deploy.yml            # Deploy on merge to main
├── packages/
│   └── shared/                   # Shared types & constants
│       ├── package.json
│       ├── tsconfig.json
│       └── src/
│           ├── types/
│           │   ├── player.ts     # Player, resources, buildings
│           │   ├── combat.ts     # Attack types, battle results
│           │   ├── alliance.ts   # Alliance types
│           │   └── game.ts       # Game config, turns, seasons
│           ├── constants/
│           │   ├── buildings.ts  # All 16 building definitions
│           │   ├── units.ts      # All 9+ unit definitions
│           │   ├── civilizations.ts  # 6 civ definitions
│           │   ├── research.ts   # 12 research trees
│           │   └── market.ts     # Market prices & multipliers
│           └── index.ts
├── apps/
│   ├── server/                   # Fastify backend
│   │   ├── package.json
│   │   ├── tsconfig.json
│   │   ├── prisma/
│   │   │   └── schema.prisma     # Database schema
│   │   └── src/
│   │       ├── index.ts          # Server entry point
│   │       ├── config.ts         # Environment config
│   │       └── ...
│   └── client/                   # React frontend
│       ├── package.json
│       ├── tsconfig.json
│       ├── vite.config.ts
│       ├── index.html
│       └── src/
│           ├── main.tsx
│           └── ...
└── legacy/                       # Original ColdFusion files (reference)
    └── ...                       # Copy of current codebase
```

### 1.2 Steps

1. **Create root `package.json`** with workspaces: `["packages/*", "apps/*"]`
2. **Install Turborepo:** `npx create-turbo@latest` or manual setup
3. **Configure `turbo.json`** with pipelines: `build`, `dev`, `test`, `lint`, `typecheck`
4. **Set up `packages/shared`:**
   - `tsconfig.json` with `"composite": true` for project references
   - Export all shared types and constants
5. **Set up `apps/server`:**
   - `npm init`, install: `fastify`, `@fastify/cors`, `@fastify/jwt`, `@fastify/websocket`, `prisma`, `@prisma/client`, `bcrypt`, `zod`
   - Dev deps: `typescript`, `vitest`, `tsx`, `@types/node`, `@types/bcrypt`
6. **Set up `apps/client`:**
   - `npm create vite@latest client -- --template react-ts`
   - Install: `@tanstack/react-query`, `zustand`, `react-router-dom`, `tailwindcss`, `axios`
7. **Docker Compose** for local PostgreSQL:
   ```yaml
   services:
     db:
       image: postgres:16-alpine
       environment:
         POSTGRES_DB: thousand_ad
         POSTGRES_USER: dev
         POSTGRES_PASSWORD: dev
       ports:
         - "5432:5432"
       volumes:
         - pgdata:/var/lib/postgresql/data
   volumes:
     pgdata:
   ```
8. **ESLint + Prettier** config at root level, shared across workspaces
9. **Copy legacy code** into `legacy/` directory for reference during development

### 1.3 Deliverables

- [ ] Monorepo boots with `npm install` at root
- [ ] `turbo dev` starts both server and client in parallel
- [ ] `turbo build` produces production builds
- [ ] `turbo test` runs (empty) test suites
- [ ] PostgreSQL accessible via Docker Compose
- [ ] TypeScript strict mode enabled everywhere

---

## Phase 2: Shared Types & Game Constants

**Goal:** Encode every game constant, building definition, unit stat, and civilization bonus into type-safe TypeScript. This is the foundation everything else builds on.

### 2.1 Civilizations (`packages/shared/src/constants/civilizations.ts`)

Translate from `startSession.cfm` — each civilization's unique unit, stat modifiers, and restrictions:

```typescript
export enum CivilizationId {
  VIKINGS = 1,
  FRANKS = 2,
  JAPANESE = 3,
  BYZANTINES = 4,
  MONGOLS = 5,
  INCAS = 6,
}

export interface Civilization {
  id: CivilizationId;
  name: string;
  uniqueUnit: {
    name: string;
    attackPt: number;
    defensePt: number;
    trainingTurns: number;
    costGold: number;
    costSwords: number;
    costHorses: number;
    costBows: number;
    costMaces: number;
    costPeople: number;
  };
  bonuses: {
    towerDefense: number;       // base 50; Franks get 65
    archerDefense: number;      // base 12; Franks/Byzantines get 15
    catapultDefense: number;    // base 25; Byzantines 30, Incas 20
    thiefDefense: number;       // base 55; Incas 80
    researchPerTownCenter: number; // Japanese get 1.5x
  };
  restrictions: {
    canTrainHorsemen: boolean;  // Incas cannot
  };
}
```

Define all 6 civilizations with exact values from `startSession.cfm`.

### 2.2 Buildings (`packages/shared/src/constants/buildings.ts`)

Translate all 16 building types:

```typescript
export interface BuildingDefinition {
  id: number;                  // 1-16
  name: string;
  landType: 'forest' | 'mountain' | 'plains';
  sqMeters: number;            // land cost per building
  costWood: number;
  costIron: number;
  costGold: number;
  workers: number;             // people required to operate
  production: number;          // base output per turn
  maxTrainPerBuilding?: number; // for forts/town centers
  maxSoldiersPerBuilding?: number;
  housingCapacity?: number;    // for houses/town centers
  storageCapacity?: number;    // for warehouses/town centers
  defensePoints?: number;      // for towers
}
```

### 2.3 Units (`packages/shared/src/constants/units.ts`)

Translate all 9 unit types:

```typescript
export interface UnitDefinition {
  id: number;                  // 1-9
  name: string;
  attackPt: number;
  defensePt: number;
  trainingTurns: number;
  goldUpkeep: number;          // gold per turn
  foodConsumption: number;     // soldiers per food unit
  costPeople: number;
  costSwords: number;
  costBows: number;
  costHorses: number;
  costMaces: number;
  costGold: number;
  costWood: number;            // catapults
  costIron: number;            // catapults
  scoreWeight: number;         // for calc_score
}
```

### 2.4 Research (`packages/shared/src/constants/research.ts`)

```typescript
export interface ResearchDefinition {
  id: number;                  // 1-12
  name: string;
  description: string;
  maxLevel: number;            // 50 for research4, unlimited for others
  affectedProduction: string;  // which production it boosts
}

// Cost formula: round(totalResearches^3 + 10) research points
export function getResearchCost(totalResearchLevels: number): number {
  return Math.round(Math.pow(totalResearchLevels, 3) + 10);
}
```

### 2.5 Market & Economy Constants

```typescript
export const MARKET_PRICES = {
  buy:  { wood: 32, food: 18, iron: 78, tools: 180 },
  sell: { wood: 30, food: 15, iron: 75, tools: 150 },
};

// Score-based price multipliers
export const SCORE_MULTIPLIERS = [ /* thresholds at 100k, 200k, etc. */ ];

export const FOOD_RATIO_MULTIPLIERS: Record<number, number> = {
  3: 4.0, 2: 2.5, 1: 1.5, 0: 1.0, '-1': 0.75, '-2': 0.45, '-3': 0.25,
};

export const PEOPLE_PER_FOOD = 50;
export const PEOPLE_PER_WOOD_HEAT = 250;
export const SOLDIERS_PER_FOOD = 3;
export const WINTER_MONTHS = [1, 2, 11, 12];
export const GROWING_MONTHS = [4, 5, 6, 7, 8, 9, 10]; // April-October
```

### 2.6 Type Definitions (`packages/shared/src/types/`)

Define TypeScript interfaces for every entity:

- `Player` — all 63 columns from the Player table
- `BuildQueueItem`, `TrainQueueItem`, `AttackQueueItem`, `ExploreQueueItem`, `TransferQueueItem`
- `Alliance` — with ally/war arrays
- `BattleResult` — attack news with full details
- `PlayerMessage`, `ForumMessage`
- `GameConfig` — all Application.cfm settings
- `TurnResult` — structured output from turn processing

### 2.7 Deliverables

- [ ] Every constant from `startSession.cfm` encoded in TypeScript with exact values verified
- [ ] All types exported from `@1000ad/shared`
- [ ] Unit tests validating constants match original values
- [ ] Constants importable by both server and client

---

## Phase 3: Database Schema & Migration

**Goal:** Design a normalized PostgreSQL schema that maps 1:1 to the original MSSQL schema, then create Prisma migrations.

### 3.1 Prisma Schema (`apps/server/prisma/schema.prisma`)

Translate every table from `Database/1000ad.sql`:

```prisma
datasource db {
  provider = "postgresql"
  url      = env("DATABASE_URL")
}

generator client {
  provider = "prisma-client-js"
}

model Player {
  id                Int       @id @default(autoincrement())
  name              String    @db.VarChar(100)
  loginName         String    @unique @db.VarChar(50)
  password          String    @db.VarChar(255)  // bcrypt hash (was plaintext)
  email             String    @db.VarChar(100)
  civ               Int       @db.SmallInt      // 1-6
  score             Int       @default(0)
  militaryScore     Int       @default(0)
  landScore         Int       @default(0)
  goodScore         Int       @default(0)
  turn              Int       @default(0)
  lastTurn          DateTime?
  turnsFree         Int       @default(100)
  people            Int       @default(3000)

  // Buildings (16 types)
  woodCutter        Int       @default(20)
  hunter            Int       @default(50)
  farmer            Int       @default(20)
  house             Int       @default(50)
  ironMine          Int       @default(20)
  goldMine          Int       @default(10)
  toolMaker         Int       @default(10)
  weaponSmith       Int       @default(0)
  fort              Int       @default(0)
  tower             Int       @default(10)
  townCenter        Int       @default(10)
  market            Int       @default(10)
  warehouse         Int       @default(0)
  stable            Int       @default(0)
  mageTower         Int       @default(0)
  winery            Int       @default(0)

  // Land (square meters)
  fLand             Int       @default(1000)
  mLand             Int       @default(500)
  pLand             Int       @default(2500)

  // Resources
  wood              Int       @default(1000)
  food              Int       @default(2500)
  iron              Int       @default(1000)
  gold              Int       @default(100000)
  tools             Int       @default(250)
  swords            Int       @default(0)
  bows              Int       @default(0)
  horses            Int       @default(0)
  maces             Int       @default(0)
  wine              Int       @default(0)

  // Military
  swordsman         Int       @default(3)
  archers           Int       @default(3)
  horseman          Int       @default(3)
  catapults         Int       @default(0)
  macemen           Int       @default(0)
  trainedPeasants   Int       @default(0)
  thieves           Int       @default(0)
  uunit             Int       @default(0)

  // Research (12 trees)
  research1         Int       @default(0)
  research2         Int       @default(0)
  research3         Int       @default(0)
  research4         Int       @default(0)
  research5         Int       @default(0)
  research6         Int       @default(0)
  research7         Int       @default(0)
  research8         Int       @default(0)
  research9         Int       @default(0)
  research10        Int       @default(0)
  research11        Int       @default(0)
  research12        Int       @default(0)
  currentResearch   Int       @default(0)
  researchPoints    Int       @default(0)

  // Building status (0-100%)
  hunterStatus      Int       @default(100)
  farmerStatus      Int       @default(100)
  ironMineStatus    Int       @default(100)
  goldMineStatus    Int       @default(100)
  toolMakerStatus   Int       @default(100)
  weaponSmithStatus Int       @default(100)
  stableStatus      Int       @default(100)
  woodCutterStatus  Int       @default(100)
  wineryStatus      Int       @default(100)
  mageTowerStatus   Int       @default(100)

  // Weapon smith allocation
  bowWeaponSmith    Int       @default(0)
  swordWeaponSmith  Int       @default(0)
  maceWeaponSmith   Int       @default(0)

  // Auto-trade settings
  autoBuyWood       Int       @default(0)
  autoSellWood      Int       @default(0)
  autoBuyFood       Int       @default(0)
  autoSellFood      Int       @default(0)
  autoBuyIron       Int       @default(0)
  autoSellIron      Int       @default(0)
  autoBuyTools      Int       @default(0)
  autoSellTools     Int       @default(0)

  // Wall
  wall              Int       @default(0)
  wallBuildPerTurn  Int       @default(0)

  // Status
  foodRatio         Int       @default(1)
  killedBy          Int       @default(0)
  killedByName      String?   @db.VarChar(100)
  numAttacks        Int       @default(0)
  lastAttack        DateTime?
  hasNewMessages    Int       @default(0)
  hasMainNews       Int       @default(0)
  hasAllianceNews   Int       @default(0)
  message           String?   @db.Text
  tradesThisTurn    Int       @default(0)

  // Metadata
  createdOn         DateTime  @default(now())
  lastLoad          DateTime?
  validationCode    String?   @db.VarChar(50)
  isAdmin           Boolean   @default(false)

  // Relations
  allianceId        Int?
  allianceMemberType Int      @default(0)
  alliance          Alliance? @relation(fields: [allianceId], references: [id])

  buildQueue        BuildQueue[]
  trainQueue        TrainQueue[]
  attacksLaunched   AttackQueue[]   @relation("attacker")
  attacksReceived   AttackQueue[]   @relation("defender")
  exploreQueue      ExploreQueue[]
  sentTransfers     TransferQueue[] @relation("sender")
  receivedTransfers TransferQueue[] @relation("receiver")
  attackNews        AttackNews[]    @relation("attackNewsDefender")
  sentMessages      PlayerMessage[] @relation("sender")
  receivedMessages  PlayerMessage[] @relation("receiver")
  loginEntries      LoginEntry[]

  @@index([allianceId])
  @@index([score(sort: Desc)])
  @@index([loginName])
}

model Alliance {
  id        Int      @id @default(autoincrement())
  tag       String   @unique @db.VarChar(20)
  password  String   @db.VarChar(20)
  leaderId  Int
  news      String?  @db.Text
  ally1     Int      @default(0)
  ally2     Int      @default(0)
  ally3     Int      @default(0)
  ally4     Int      @default(0)
  ally5     Int      @default(0)
  war1      Int      @default(0)
  war2      Int      @default(0)
  war3      Int      @default(0)
  war4      Int      @default(0)
  war5      Int      @default(0)
  members   Player[]
}

model BuildQueue {
  id          Int    @id @default(autoincrement())
  playerId    Int
  buildingNo  Int    @db.SmallInt
  mission     Int    @db.SmallInt   // 0=build, 1=demolish
  qty         Int
  timeNeeded  Int
  pos         Int    @default(0)
  player      Player @relation(fields: [playerId], references: [id])

  @@index([playerId])
}

model TrainQueue {
  id              Int    @id @default(autoincrement())
  playerId        Int
  soldierType     Int    @db.SmallInt   // 1-9
  turnsRemaining  Int
  qty             Int
  player          Player @relation(fields: [playerId], references: [id])

  @@index([playerId])
}

model AttackQueue {
  id              Int    @id @default(autoincrement())
  playerId        Int
  attackPlayerId  Int
  // Attacking army units
  swordsman       Int    @default(0)
  archers         Int    @default(0)
  horseman        Int    @default(0)
  catapults       Int    @default(0)
  macemen         Int    @default(0)
  trainedPeasants Int    @default(0)
  thieves         Int    @default(0)
  uunit           Int    @default(0)
  wine            Int    @default(0)
  status          Int    @default(0)   // 0-6
  attackType      Int    @db.SmallInt   // 0-3 army, 10-12 catapult, 20-25 thief
  turnsRemaining  Int    @default(3)
  createdOn       DateTime @default(now())

  attacker        Player @relation("attacker", fields: [playerId], references: [id])
  defender        Player @relation("defender", fields: [attackPlayerId], references: [id])

  @@index([playerId])
  @@index([attackPlayerId])
}

model ExploreQueue {
  id        Int    @id @default(autoincrement())
  playerId  Int
  turn      Int              // turns remaining
  people    Int              // explorers sent
  mLand     Int    @default(0)
  fLand     Int    @default(0)
  pLand     Int    @default(0)
  seekLand  Int    @default(0)  // 0=balanced, 1=mountain, 2=forest, 3=plains
  player    Player @relation(fields: [playerId], references: [id])

  @@index([playerId])
}

model TransferQueue {
  id              Int    @id @default(autoincrement())
  fromPlayerId    Int
  toPlayerId      Int
  wood            Int    @default(0)
  food            Int    @default(0)
  iron            Int    @default(0)
  gold            Int    @default(0)
  tools           Int    @default(0)
  turnsRemaining  Int
  transferType    Int    @db.SmallInt  // 0=aid sent, 1=aid incoming, 2=market
  sender          Player @relation("sender", fields: [fromPlayerId], references: [id])
  receiver        Player @relation("receiver", fields: [toPlayerId], references: [id])

  @@index([fromPlayerId])
  @@index([toPlayerId])
}

model AttackNews {
  id           Int      @id @default(autoincrement())
  attackId     Int
  defenseId    Int
  attackerWins Boolean
  details      Json     // battle log as structured JSON (replaces ntext blob)
  createdOn    DateTime @default(now())
  defender     Player   @relation("attackNewsDefender", fields: [defenseId], references: [id])

  @@index([defenseId])
  @@index([attackId])
  @@index([createdOn(sort: Desc)])
}

model PlayerMessage {
  id           Int      @id @default(autoincrement())
  fromPlayerId Int
  toPlayerId   Int
  message      String   @db.Text
  viewed       Boolean  @default(false)
  messageType  Int      @default(0)
  createdOn    DateTime @default(now())
  sender       Player   @relation("sender", fields: [fromPlayerId], references: [id])
  receiver     Player   @relation("receiver", fields: [toPlayerId], references: [id])

  @@index([toPlayerId])
  @@index([fromPlayerId])
}

model ForumMessage {
  id        Int      @id @default(autoincrement())
  playerId  Int
  subject   String   @db.VarChar(200)
  message   String   @db.Text
  createdOn DateTime @default(now())

  @@index([createdOn(sort: Desc)])
}

model LoginEntry {
  id        Int      @id @default(autoincrement())
  playerId  Int
  createdOn DateTime @default(now())
  ipAddress String   @db.VarChar(45)
  userAgent String?  @db.VarChar(500)
  player    Player   @relation(fields: [playerId], references: [id])

  @@index([playerId])
}

model AiPlayer {
  id       Int @id @default(autoincrement())
  playerId Int @unique
}

model GameConfig {
  id               Int      @id @default(1)
  gameName         String   @db.VarChar(100)
  minutesPerTurn   Int      @default(5)
  maxTurnsStored   Int      @default(500)
  startTurns       Int      @default(100)
  allianceMaxMembers Int    @default(10)
  deathmatchMode   Boolean  @default(false)
  startGameDate    DateTime
  endGameDate      DateTime?
}
```

### 3.2 Migration Steps

1. Run `npx prisma migrate dev --name init` to create initial PostgreSQL schema
2. Write a one-time **data migration script** (`scripts/migrate-mssql-to-pg.ts`):
   - Connect to both MSSQL (source) and PostgreSQL (target)
   - Copy all rows with column mapping
   - Hash all plaintext passwords with bcrypt during migration
   - Convert `ntext` battle details to structured JSON
   - Validate row counts match after migration
3. Create seed script (`prisma/seed.ts`) for development with sample data

### 3.3 Schema Improvements Over Original

| Change | Reason |
|--------|--------|
| `password` stored as bcrypt hash | Original stored plaintext passwords |
| `AttackNews.details` as JSONB | Original was unstructured ntext blob |
| Foreign key constraints | Original had no referential integrity |
| Proper indexes on query patterns | Original had minimal indexing |
| `GameConfig` table | Replaces hardcoded `Application.cfm` variables |
| `DateTime` types | Replaces string-based date handling |

### 3.4 Deliverables

- [ ] Prisma schema covers all 12+ tables with all columns
- [ ] `prisma migrate dev` creates clean schema
- [ ] Seed script creates a playable test game
- [ ] Migration script handles MSSQL → PostgreSQL data transfer
- [ ] All foreign keys and indexes defined

---

## Phase 4: Core Game Engine

**Goal:** Implement all game logic as pure, testable TypeScript functions with zero I/O dependencies. This is the heart of the modernization.

### 4.1 Architecture

```
apps/server/src/engine/
├── turn/
│   ├── processTurn.ts           # Main turn orchestrator (replaces end_turn.cfm)
│   ├── production.ts            # Resource production (8 types)
│   ├── consumption.ts           # Food/wood consumption & population
│   ├── population.ts            # Growth, decline, housing
│   ├── research.ts              # Research point accumulation & completion
│   ├── wall.ts                  # Wall construction & decay
│   ├── building.ts              # Build queue processing
│   ├── training.ts              # Train queue processing
│   ├── exploration.ts           # Explorer queue processing
│   ├── military.ts              # Soldier overflow, payment, desertion
│   ├── storage.ts               # Supply capacity & theft
│   ├── autoTrade.ts             # Auto buy/sell market
│   ├── toolWear.ts              # Biannual tool degradation
│   └── transfers.ts             # Aid & market transfer processing
├── combat/
│   ├── armyCombat.ts            # Army vs army (replaces doAttack.cfm)
│   ├── catapultCombat.ts        # Catapult siege (replaces doAttack2.cfm)
│   ├── thiefCombat.ts           # Espionage operations (replaces doAttack3.cfm)
│   ├── penalties.ts             # Weak opponent & repeated attack penalties
│   ├── casualties.ts            # Kill/heal calculation
│   └── validation.ts            # Attack eligibility checks
├── scoring/
│   └── calculateScore.ts        # Score calculation (replaces calc_score.cfm)
└── index.ts                     # Public API
```

### 4.2 Turn Processing (`processTurn.ts`)

The monolithic `end_turn.cfm` (1275 lines) is decomposed into ~15 focused functions. Each function is **pure**: takes a player state object, returns a modified state object + a list of events/messages.

```typescript
export interface TurnInput {
  player: PlayerState;
  buildQueue: BuildQueueItem[];
  trainQueue: TrainQueueItem[];
  attackQueue: AttackQueueItem[];
  exploreQueue: ExploreQueueItem[];
  transferQueue: TransferQueueItem[];
  config: GameConfig;
}

export interface TurnResult {
  player: PlayerState;           // Updated player state
  buildQueue: BuildQueueItem[];  // Updated queues
  trainQueue: TrainQueueItem[];
  attackQueue: AttackQueueItem[];
  exploreQueue: ExploreQueueItem[];
  transferQueue: TransferQueueItem[];
  messages: string[];            // Turn report messages
  events: GameEvent[];           // Structured events for logging
  combatResults: CombatResult[]; // Any battles resolved this turn
}

export function processTurn(input: TurnInput): TurnResult {
  let state = { ...input };

  // Execute in exact same order as end_turn.cfm:
  state = processTransfers(state);          // 1. Aid & market deliveries
  state = validateBuilders(state);          // 2. Builder assignment
  state = produceResources(state);          // 3. All 8 resource types
  state = consumeWinterWood(state);         // 4. Winter heating
  state = consumeFood(state);              // 5. Food consumption
  state = updatePopulation(state);          // 6. Growth/decline
  state = processResearch(state);          // 7. Research completion
  state = processWallConstruction(state);  // 8. Wall building & decay
  state = processBuildQueue(state);        // 9. Building completion
  state = processTrainQueue(state);        // 10. Unit training
  state = processExploreQueue(state);      // 11. Explorer return
  state = processAttackQueue(state);       // 12. Combat resolution
  state = handleSoldierOverflow(state);    // 13. Excess soldiers flee
  state = paySoldiers(state);             // 14. Military payroll
  state = enforceUnitCaps(state);         // 15. Thief/catapult/uunit caps
  state = enforceStorageCaps(state);      // 16. Warehouse capacity
  state = processAutoTrade(state);        // 17. Auto buy/sell
  state = processToolWear(state);         // 18. Biannual tool loss
  state = advanceTurn(state);             // 19. Increment turn counter

  return state;
}
```

### 4.3 Resource Production (`production.ts`)

Each resource producer follows the same pattern. Example for hunters:

```typescript
export function produceHunterFood(player: PlayerState): ProductionResult {
  if (player.hunterStatus <= 0) return { produced: 0, workersUsed: 0 };

  const hunterDef = BUILDINGS.hunter;
  const statusFactor = player.hunterStatus / 100;
  const researchBonus = 1 + (player.research5 / 100);

  let workersNeeded = player.hunter * hunterDef.workers;
  let canOperate = player.hunter;

  // If insufficient people, reduce production
  if (workersNeeded > player.availableWorkers) {
    canOperate = Math.floor(player.availableWorkers / hunterDef.workers);
    workersNeeded = canOperate * hunterDef.workers;
  }

  const produced = Math.round(
    canOperate * statusFactor * hunterDef.production * researchBonus
  );

  return { produced, workersUsed: workersNeeded };
}
```

Repeat for all 8 resource types with their specific formulas:
- **Hunters** → food (research5 bonus)
- **Farmers** → food (research5 bonus, growing season only months 4-10)
- **Woodcutters** → wood (research12 bonus)
- **Gold mines** → gold (research6 bonus)
- **Iron mines** → iron (research6 bonus)
- **Tool makers** → tools, costs 2 wood + 2 iron (research7 bonus)
- **Weapon smiths** → swords/bows/maces, variable costs (research7 bonus)
- **Stables** → horses, costs 100 food
- **Wineries** → wine, costs 10 gold
- **Mage towers** → research points, costs 100 gold

### 4.4 Combat System (`combat/`)

Each of the 3 attack types becomes its own module with pure functions:

```typescript
// armyCombat.ts
export interface ArmyCombatInput {
  attacker: CombatantState;
  defender: CombatantState;
  attackType: 0 | 1 | 2 | 3;  // conquer, raid, rob, slaughter
  deathmatchMode: boolean;
  recentAttackHistory: AttackHistory[];
}

export interface ArmyCombatResult {
  attackerWins: boolean;
  attackerLosses: UnitCasualties;
  defenderLosses: UnitCasualties;
  landTaken?: LandTransfer;      // type 0
  buildingsDestroyed?: number;    // type 1
  resourcesStolen?: Resources;   // type 2
  peoplekilled?: number;         // type 3
  victoryPoints: number;
  battleLog: string;
}

export function resolveArmyCombat(input: ArmyCombatInput): ArmyCombatResult {
  // 1. Calculate weak opponent penalty
  const { runPercent, victoryPoints } = calculateWeakPenalty(input);

  // 2. Calculate attack points with wine bonus
  let attackPoints = calculateAttackPoints(input.attacker);

  // 3. Calculate defense points with wall bonus
  let defensePoints = calculateDefensePoints(input.defender);

  // 4. Apply repeated attack penalty
  const repeatPenalty = calculateRepeatPenalty(input.recentAttackHistory);
  attackPoints *= repeatPenalty.attackMultiplier;

  // 5. Apply ±10% randomization
  attackPoints = randomizePoints(attackPoints);
  defensePoints = randomizePoints(defensePoints);

  // 6. Determine winner
  const attackerWins = attackPoints > defensePoints;

  // 7. Calculate casualties with research4 healing
  // 8. Apply attack-type-specific effects
  // 9. Return structured result
}
```

### 4.5 Score Calculation (`scoring/calculateScore.ts`)

```typescript
export function calculateScore(player: PlayerState): ScoreBreakdown {
  const militaryScore =
    Math.round(player.swordsman * 2) +
    Math.round(player.archers * 1.8) +
    Math.round(player.horseman * 3) +
    Math.round(player.people * 0.25) +
    Math.round(player.macemen * 1) +
    Math.round(player.trainedPeasants * 0.3) +
    Math.round(player.catapults * 6) +
    Math.round(player.thieves * 4) +
    Math.round(player.uunit * getUniqueUnitScoreWeight(player.civ));

  const landScore =
    player.mLand * 5 + player.fLand * 4 + player.pLand * 3;

  const goodScore = /* ... exact formula ... */;

  const totalScore = militaryScore + landScore + goodScore;

  return {
    total: totalScore,
    military: militaryScore,
    land: landScore,
    goods: goodScore,
    militaryPercent: Math.round((militaryScore / totalScore) * 100),
    landPercent: Math.round((landScore / totalScore) * 100),
    goodsPercent: Math.round((goodScore / totalScore) * 100),
  };
}
```

### 4.6 Testing Strategy for Game Engine

Every formula gets a test. This is the critical quality gate:

```typescript
// __tests__/engine/production.test.ts
describe('Hunter food production', () => {
  it('produces 3 food per hunter at 100% status', () => {
    const result = produceHunterFood({
      hunter: 50, hunterStatus: 100, research5: 0, availableWorkers: 1000
    });
    expect(result.produced).toBe(150); // 50 * 1.0 * 3
  });

  it('applies research5 bonus', () => {
    const result = produceHunterFood({
      hunter: 50, hunterStatus: 100, research5: 50, availableWorkers: 1000
    });
    expect(result.produced).toBe(225); // 150 * 1.5
  });

  it('reduces production when insufficient workers', () => {
    const result = produceHunterFood({
      hunter: 50, hunterStatus: 100, research5: 0, availableWorkers: 30
    });
    // 30 workers / 6 per hunter = 5 hunters can operate
    expect(result.produced).toBe(15); // 5 * 3
  });
});

// __tests__/engine/combat.test.ts
describe('Army combat resolution', () => {
  it('applies weak opponent penalty at 10x score difference', () => {
    const penalty = calculateWeakPenalty({ attackerScore: 100000, defenderScore: 10000 });
    expect(penalty.runPercent).toBe(0.80);
    expect(penalty.victoryPoints).toBe(0.01);
  });

  it('applies repeated attack penalty after 15 attacks', () => {
    const penalty = calculateRepeatPenalty(15);
    expect(penalty.victoryMultiplier).toBe(0.01);
    expect(penalty.attackMultiplier).toBe(0.25);
  });
});
```

### 4.7 Deliverables

- [ ] All 15+ turn processing functions implemented with exact original formulas
- [ ] All 3 combat resolution systems implemented
- [ ] Score calculation matches original exactly
- [ ] 200+ unit tests covering every formula, edge case, and boundary condition
- [ ] Zero I/O in engine code — pure functions only
- [ ] 100% test pass rate

---

## Phase 5: Backend API Layer

**Goal:** Build a RESTful + WebSocket API that exposes all game actions, replacing the ColdFusion `eflag_*.cfm` handlers.

### 5.1 API Structure

```
apps/server/src/
├── routes/
│   ├── auth.ts              # POST /auth/register, /auth/login, /auth/logout
│   ├── player.ts            # GET /player/status, PATCH /player/settings
│   ├── buildings.ts         # POST /buildings/build, /buildings/demolish
│   ├── army.ts              # POST /army/train, /army/disband
│   ├── attack.ts            # POST /attack/launch, GET /attack/queue
│   ├── research.ts          # POST /research/start, PATCH /research/change
│   ├── explore.ts           # POST /explore/send
│   ├── alliance.ts          # CRUD /alliance/*
│   ├── market.ts            # POST /market/buy, /market/sell
│   ├── messages.ts          # CRUD /messages/*
│   ├── scores.ts            # GET /scores/players, /scores/alliances
│   ├── turns.ts             # POST /turns/end, /turns/end-multiple
│   ├── manage.ts            # PATCH /manage/* (food ratio, building status, etc.)
│   └── admin.ts             # Admin-only endpoints
├── middleware/
│   ├── auth.ts              # JWT verification middleware
│   ├── gameState.ts         # Load player state into request context
│   ├── validation.ts        # Zod schema validation
│   └── rateLimit.ts         # Rate limiting
├── services/
│   ├── playerService.ts     # Player CRUD + state management
│   ├── turnService.ts       # Orchestrates turn processing with DB
│   ├── combatService.ts     # Orchestrates combat with DB writes
│   ├── allianceService.ts   # Alliance business logic
│   ├── marketService.ts     # Market transactions
│   └── messageService.ts    # Messaging
├── websocket/
│   ├── handler.ts           # WebSocket connection manager
│   └── events.ts            # Real-time event types
└── index.ts                 # Fastify app setup
```

### 5.2 Authentication Routes

```typescript
// routes/auth.ts
import { FastifyInstance } from 'fastify';
import { z } from 'zod';
import bcrypt from 'bcrypt';

const registerSchema = z.object({
  loginName: z.string().min(3).max(50),
  password: z.string().min(6).max(100),
  name: z.string().min(1).max(100),    // empire name
  email: z.string().email(),
  civ: z.number().int().min(1).max(6),
});

const loginSchema = z.object({
  loginName: z.string(),
  password: z.string(),
});

export async function authRoutes(app: FastifyInstance) {
  app.post('/auth/register', async (request, reply) => {
    const body = registerSchema.parse(request.body);
    const hashedPassword = await bcrypt.hash(body.password, 12);

    // Create player with starting values (mirrors createPlayer.cfm)
    const player = await prisma.player.create({
      data: {
        loginName: body.loginName,
        password: hashedPassword,
        name: body.name,
        email: body.email,
        civ: body.civ,
        // All defaults from Prisma schema match original starting values
        turnsFree: calculateStartingTurns(config),
      }
    });

    const token = app.jwt.sign({ playerId: player.id, civ: player.civ });
    return { token, playerId: player.id };
  });

  app.post('/auth/login', async (request, reply) => {
    const body = loginSchema.parse(request.body);
    const player = await prisma.player.findUnique({
      where: { loginName: body.loginName }
    });

    if (!player || !(await bcrypt.compare(body.password, player.password))) {
      return reply.status(401).send({ error: 'Invalid login name or password' });
    }

    // Log login (replaces loginEntry insert)
    await prisma.loginEntry.create({
      data: {
        playerId: player.id,
        ipAddress: request.ip,
        userAgent: request.headers['user-agent'] ?? null,
      }
    });

    const token = app.jwt.sign({ playerId: player.id, civ: player.civ });
    return { token, playerId: player.id };
  });
}
```

### 5.3 Game Action Routes

Each `eflag_*.cfm` becomes a route with proper validation:

```typescript
// routes/buildings.ts
const buildSchema = z.object({
  buildingNo: z.number().int().min(1).max(16),
  quantity: z.number().int().min(1).max(10_000_000),
});

app.post('/buildings/build', { preHandler: [requireAuth] }, async (req) => {
  const { buildingNo, quantity } = buildSchema.parse(req.body);
  const player = await loadPlayer(req.playerId);
  const building = BUILDINGS[buildingNo];

  // Validation (mirrors eflag_build.cfm checks)
  validateSufficientGold(player, quantity * building.costGold);
  validateSufficientWood(player, quantity * building.costWood);
  validateSufficientIron(player, quantity * building.costIron);
  validateSufficientLand(player, quantity, building);

  // Deduct resources & queue build
  const timeNeeded = (quantity * building.costWood) + (quantity * building.costIron);

  await prisma.$transaction([
    prisma.player.update({
      where: { id: req.playerId },
      data: {
        gold: { decrement: quantity * building.costGold },
        wood: { decrement: quantity * building.costWood },
        iron: { decrement: quantity * building.costIron },
      }
    }),
    prisma.buildQueue.create({
      data: { playerId: req.playerId, buildingNo, mission: 0, qty: quantity, timeNeeded }
    }),
  ]);

  return { success: true, timeNeeded };
});
```

### 5.4 Turn Processing Route

```typescript
// routes/turns.ts
app.post('/turns/end', { preHandler: [requireAuth] }, async (req) => {
  const count = z.number().int().min(1).max(500).parse(req.body?.count ?? 1);
  const results: TurnResult[] = [];

  for (let i = 0; i < count; i++) {
    // Load full state from DB
    const input = await loadTurnInput(req.playerId);

    if (input.player.turnsFree <= 0) break;
    if (input.player.killedBy !== 0) break;

    // Process turn (pure function)
    const result = processTurn(input);

    // Persist result to DB
    await saveTurnResult(req.playerId, result);

    results.push(result);

    // Notify via WebSocket
    ws.notifyPlayer(req.playerId, { type: 'turn_completed', turn: result.player.turn });
  }

  return { turnsProcessed: results.length, finalState: results.at(-1)?.player };
});
```

### 5.5 WebSocket Events

```typescript
// websocket/events.ts
export type ServerEvent =
  | { type: 'turn_completed'; turn: number }
  | { type: 'attack_incoming'; attackerName: string }
  | { type: 'attack_resolved'; result: BattleResult }
  | { type: 'message_received'; fromName: string }
  | { type: 'alliance_news'; content: string }
  | { type: 'chat_message'; from: string; message: string };
```

### 5.6 Input Validation

Replace all the scattered `cfparam` and manual checks with Zod schemas:

```typescript
// Every route gets a Zod schema
// Every database write uses a transaction
// Every error returns a structured JSON response
// No raw SQL — Prisma handles parameterized queries (eliminates SQL injection)
```

### 5.7 Deliverables

- [ ] All ~15 eflag handlers converted to REST endpoints
- [ ] JWT authentication with bcrypt password hashing
- [ ] Zod validation on every endpoint
- [ ] Prisma transactions for all multi-step operations
- [ ] WebSocket server for real-time events
- [ ] Rate limiting on sensitive endpoints
- [ ] API integration tests for every endpoint
- [ ] Zero SQL injection vectors (Prisma parameterized queries)

---

## Phase 6: Frontend Application

**Goal:** Build a responsive, modern React UI that replaces all ~15 ColdFusion page templates.

### 6.1 Application Structure

```
apps/client/src/
├── main.tsx                     # App entry point
├── App.tsx                      # Router + providers
├── api/
│   ├── client.ts                # Axios instance with JWT interceptor
│   ├── queries/                 # TanStack Query hooks
│   │   ├── usePlayer.ts
│   │   ├── useScores.ts
│   │   ├── useAlliance.ts
│   │   └── ...
│   └── mutations/               # TanStack Mutation hooks
│       ├── useBuild.ts
│       ├── useTrain.ts
│       ├── useAttack.ts
│       └── ...
├── stores/
│   ├── authStore.ts             # JWT token + user info (Zustand)
│   ├── gameStore.ts             # Current game state
│   └── wsStore.ts               # WebSocket connection state
├── components/
│   ├── layout/
│   │   ├── AppLayout.tsx        # Main layout (replaces index.cfm structure)
│   │   ├── Sidebar.tsx          # Navigation (replaces left_menu.cfm)
│   │   ├── TopBar.tsx           # Header (replaces top_menu.cfm)
│   │   └── ResourceBar.tsx      # Always-visible resource display
│   ├── common/
│   │   ├── ResourceIcon.tsx     # Icon + value for each resource type
│   │   ├── QueueList.tsx        # Reusable queue display (build/train/attack)
│   │   ├── ConfirmDialog.tsx
│   │   └── DataTable.tsx        # Sortable data table component
│   └── game/
│       ├── BuildingCard.tsx     # Building type with costs & actions
│       ├── UnitCard.tsx         # Military unit with stats
│       ├── AttackForm.tsx       # Attack configuration UI
│       ├── BattleReport.tsx     # Battle result display
│       ├── ResearchTree.tsx     # Research tree visualization
│       ├── AllianceBadge.tsx    # Alliance tag display
│       └── ScoreChart.tsx       # Score breakdown pie chart
├── pages/
│   ├── LoginPage.tsx            # Login/register (replaces login.cfm)
│   ├── DashboardPage.tsx        # Main status (replaces status.cfm / main.cfm)
│   ├── BuildPage.tsx            # Building management (replaces build.cfm)
│   ├── ArmyPage.tsx             # Army management (replaces army.cfm)
│   ├── AttackPage.tsx           # Attack management (replaces attack.cfm)
│   ├── ResearchPage.tsx         # Research tree (replaces research.cfm)
│   ├── ExplorePage.tsx          # Exploration (replaces explore.cfm)
│   ├── AlliancePage.tsx         # Alliance management (replaces alliance.cfm)
│   ├── MarketPage.tsx           # Local + global market (replaces localMarket.cfm / globalMarket.cfm)
│   ├── MessagesPage.tsx         # In-game messaging (replaces player_messages.cfm)
│   ├── ScoresPage.tsx           # Leaderboards (replaces scores.cfm)
│   ├── ManagePage.tsx           # Empire management (replaces manage.cfm)
│   ├── WallPage.tsx             # Wall construction (replaces wall.cfm)
│   ├── BattleHistoryPage.tsx    # Recent battles (replaces recent_battles.cfm)
│   ├── ChatPage.tsx             # Chat (replaces chat.cfm)
│   ├── ForumPage.tsx            # Forum (replaces forum.cfm)
│   └── DocsPage.tsx             # Help docs (replaces docs.cfm)
├── hooks/
│   ├── useWebSocket.ts          # WebSocket connection management
│   ├── useTurnTimer.ts          # Countdown to next available turn
│   └── useNotifications.ts      # Toast notifications for events
└── utils/
    ├── formatters.ts            # Number/date/resource formatting
    ├── turnCalc.ts              # Turn → year/month conversion
    └── civData.ts               # Civilization display data (colors, icons)
```

### 6.2 Routing

```typescript
// App.tsx
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route element={<RequireAuth><AppLayout /></RequireAuth>}>
            <Route index element={<Navigate to="/dashboard" />} />
            <Route path="/dashboard" element={<DashboardPage />} />
            <Route path="/build" element={<BuildPage />} />
            <Route path="/army" element={<ArmyPage />} />
            <Route path="/attack" element={<AttackPage />} />
            <Route path="/research" element={<ResearchPage />} />
            <Route path="/explore" element={<ExplorePage />} />
            <Route path="/alliance" element={<AlliancePage />} />
            <Route path="/market" element={<MarketPage />} />
            <Route path="/messages" element={<MessagesPage />} />
            <Route path="/scores" element={<ScoresPage />} />
            <Route path="/manage" element={<ManagePage />} />
            <Route path="/wall" element={<WallPage />} />
            <Route path="/battles" element={<BattleHistoryPage />} />
            <Route path="/chat" element={<ChatPage />} />
            <Route path="/forum" element={<ForumPage />} />
            <Route path="/docs" element={<DocsPage />} />
          </Route>
        </Routes>
      </BrowserRouter>
    </QueryClientProvider>
  );
}
```

### 6.3 Key Page: Dashboard (replaces `status.cfm`)

The dashboard shows the player's empire at a glance:

```
┌──────────────────────────────────────────────────────────────┐
│  [SIDEBAR]  │  RESOURCE BAR: 🪵 1000  🌾 2500  ⛏ 1000  💰 100k  │
│             │─────────────────────────────────────────────────│
│  Dashboard  │  Empire: [Name]     Civ: Vikings               │
│  Build      │  Turn: 42           Date: April, 1003 AD       │
│  Army       │  Score: 12,450      Rank: #3                   │
│  Attack     │  Turns Available: 87                           │
│  Research   │──────────────────────────────────────────────── │
│  Explore    │  RESOURCES           PRODUCTION/TURN            │
│  Alliance   │  Wood:    1,000      +120                       │
│  Market     │  Food:    2,500      +340 (growing season)      │
│  Messages   │  Iron:    1,000      +60                        │
│  Scores     │  Gold:    100,000    +800                       │
│  Manage     │  Tools:   250        +8                         │
│  Wall       │──────────────────────────────────────────────── │
│  Battles    │  MILITARY            QUEUES                     │
│  Chat       │  Swordsmen: 3       [Build] 2 items             │
│  Forum      │  Archers:   3       [Train] 1 batch             │
│  Docs       │  Horsemen:  3       [Attack] none               │
│             │  ...                 [Explore] 1 mission         │
│             │──────────────────────────────────────────────── │
│             │  TURN MESSAGES                                  │
│  [END TURN] │  "Hunters produced 150 food..."                 │
│  [END 10]   │  "3 swordsmen trained..."                       │
└──────────────────────────────────────────────────────────────┘
```

### 6.4 Key Page: Build (replaces `build.cfm`)

```
┌─────────────────────────────────────────────────────────────┐
│  BUILDINGS                        AVAILABLE LAND            │
│                                   Forest: 1,000 sqm         │
│  ┌─────────────────────────────┐  Mountain: 500 sqm         │
│  │  🪵 Woodcutter       [20]  │  Plains: 2,500 sqm         │
│  │  Cost: 2 wood              │                             │
│  │  Land: Forest (10 sqm)     │  BUILD QUEUE                │
│  │  Workers: 6 | Prod: 4 wood │  ┌───────────────────────┐  │
│  │  [Build +1] [+5] [+10]     │  │ 5x Farm — 3 turns     │  │
│  │  [Demolish]                │  │ 2x Fort — 8 turns     │  │
│  └─────────────────────────────┘  │ [Cancel]              │  │
│  ┌─────────────────────────────┐  └───────────────────────┘  │
│  │  🏠 House            [50]  │                             │
│  │  Cost: 4 wood              │                             │
│  │  ...                       │                             │
│  └─────────────────────────────┘                             │
└─────────────────────────────────────────────────────────────┘
```

### 6.5 Key Page: Attack (replaces `attack.cfm`)

Interactive attack form with target selection, unit allocation, and attack type picker.

### 6.6 Responsive Design

- **Desktop:** Sidebar + main content (as shown above)
- **Tablet:** Collapsible sidebar, stacked layouts
- **Mobile:** Bottom navigation bar, card-based layouts, swipeable panels

### 6.7 Deliverables

- [ ] All 17 pages implemented as React components
- [ ] TanStack Query hooks for all API endpoints
- [ ] WebSocket integration for real-time updates
- [ ] Responsive design (desktop, tablet, mobile)
- [ ] Dark mode support via Tailwind
- [ ] Accessibility: keyboard navigation, ARIA labels, screen reader support
- [ ] Loading states, error boundaries, empty states
- [ ] Client-side form validation matching server schemas

---

## Phase 7: Real-Time Features

**Goal:** Add live updates that the original ColdFusion app couldn't support.

### 7.1 WebSocket Integration

```typescript
// Server-side: websocket/handler.ts
const connections = new Map<number, WebSocket>(); // playerId → ws

app.register(fastifyWebsocket);
app.get('/ws', { websocket: true }, (socket, req) => {
  const playerId = verifyToken(req);
  connections.set(playerId, socket);

  socket.on('message', (raw) => {
    const msg = JSON.parse(raw.toString());
    if (msg.type === 'chat') {
      broadcastChat(playerId, msg.content);
    }
  });

  socket.on('close', () => connections.delete(playerId));
});

export function notifyPlayer(playerId: number, event: ServerEvent) {
  connections.get(playerId)?.send(JSON.stringify(event));
}
```

### 7.2 Features

| Feature | Description |
|---------|-------------|
| Turn notifications | "Turn processed" toast when turn completes |
| Attack alerts | Real-time notification when you're attacked |
| Message notifications | Badge count + toast for new messages |
| Live chat | Replace polling-based chat with WebSocket |
| Score updates | Live leaderboard updates |
| Alliance news | Real-time alliance event feed |

### 7.3 Deliverables

- [ ] WebSocket server with JWT authentication
- [ ] Client-side connection manager with auto-reconnect
- [ ] Toast notification system
- [ ] Live chat replacing `chat.cfm`
- [ ] Attack alert system

---

## Phase 8: Testing & Quality Assurance

**Goal:** Comprehensive test coverage to ensure game mechanics are preserved exactly.

### 8.1 Test Structure

```
apps/server/src/__tests__/
├── engine/
│   ├── production.test.ts       # All 10 resource production formulas
│   ├── consumption.test.ts      # Food/wood consumption
│   ├── population.test.ts       # Growth/decline mechanics
│   ├── research.test.ts         # Research cost curve & completion
│   ├── combat/
│   │   ├── armyCombat.test.ts   # Army attack types 0-3
│   │   ├── catapult.test.ts     # Catapult attack types 10-12
│   │   ├── thief.test.ts        # Thief attack types 20-25
│   │   ├── penalties.test.ts    # Weak/repeat penalties
│   │   └── validation.test.ts   # Attack eligibility
│   ├── scoring.test.ts          # Score calculation
│   ├── building.test.ts         # Build queue processing
│   ├── training.test.ts         # Train queue processing
│   ├── exploration.test.ts      # Explorer mechanics
│   ├── military.test.ts         # Overflow, payment, desertion
│   └── integration/
│       └── fullTurn.test.ts     # End-to-end turn processing
├── routes/
│   ├── auth.test.ts
│   ├── buildings.test.ts
│   ├── army.test.ts
│   ├── attack.test.ts
│   └── ...
└── services/
    ├── playerService.test.ts
    └── ...

apps/client/src/__tests__/
├── pages/
│   ├── Dashboard.test.tsx
│   └── ...
└── components/
    └── ...

e2e/
├── login.spec.ts
├── buildAndTrain.spec.ts
├── attackFlow.spec.ts
├── allianceManagement.spec.ts
└── fullGameLoop.spec.ts
```

### 8.2 Test Categories

| Category | Tool | Count (est.) | Purpose |
|----------|------|-------------|---------|
| Engine unit tests | Vitest | ~250 | Verify every formula matches original |
| API integration tests | Vitest + supertest | ~80 | Verify routes, validation, auth |
| Component tests | Vitest + Testing Library | ~60 | Verify UI rendering & interactions |
| E2E tests | Playwright | ~15 | Full user flows across the app |

### 8.3 Formula Verification Process

For each formula in the original ColdFusion code:
1. Extract the exact formula from the `.cfm` file
2. Write a test with known inputs and manually calculated expected outputs
3. Verify edge cases (zero values, max values, negative results)
4. Cross-reference with `attack_sim.cfm` for combat formulas

### 8.4 Deliverables

- [ ] 250+ engine unit tests passing
- [ ] 80+ API integration tests passing
- [ ] 60+ component tests passing
- [ ] 15+ E2E tests passing
- [ ] Code coverage > 90% for engine code
- [ ] All tests run in CI on every PR

---

## Phase 9: CI/CD Pipeline

**Goal:** Automated testing, building, and deployment.

### 9.1 GitHub Actions CI (`.github/workflows/ci.yml`)

```yaml
name: CI
on:
  pull_request:
    branches: [main]
  push:
    branches: [main]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: 20 }
      - run: npm ci
      - run: npx turbo lint

  typecheck:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: 20 }
      - run: npm ci
      - run: npx turbo typecheck

  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16-alpine
        env:
          POSTGRES_DB: thousand_ad_test
          POSTGRES_USER: test
          POSTGRES_PASSWORD: test
        ports: ['5432:5432']
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: 20 }
      - run: npm ci
      - run: npx prisma migrate deploy
        env: { DATABASE_URL: 'postgresql://test:test@localhost:5432/thousand_ad_test' }
      - run: npx turbo test
        env: { DATABASE_URL: 'postgresql://test:test@localhost:5432/thousand_ad_test' }

  e2e:
    runs-on: ubuntu-latest
    needs: [test]
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: 20 }
      - run: npm ci
      - run: npx playwright install --with-deps
      - run: npx turbo build
      - run: npx playwright test

  build:
    runs-on: ubuntu-latest
    needs: [lint, typecheck, test]
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: 20 }
      - run: npm ci
      - run: npx turbo build
```

### 9.2 Deployment Pipeline (`.github/workflows/deploy.yml`)

```yaml
name: Deploy
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    needs: [ci]
    steps:
      - uses: actions/checkout@v4
      - name: Build Docker images
        run: docker compose -f docker-compose.prod.yml build
      - name: Push to registry
        run: docker compose -f docker-compose.prod.yml push
      - name: Deploy
        run: # SSH deploy or cloud provider CLI
```

### 9.3 Docker Production Setup

```dockerfile
# apps/server/Dockerfile
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
COPY packages/shared ./packages/shared
COPY apps/server ./apps/server
RUN npm ci && npx turbo build --filter=server

FROM node:20-alpine
WORKDIR /app
COPY --from=builder /app/apps/server/dist ./dist
COPY --from=builder /app/node_modules ./node_modules
EXPOSE 3000
CMD ["node", "dist/index.js"]
```

```dockerfile
# apps/client/Dockerfile
FROM node:20-alpine AS builder
WORKDIR /app
COPY . .
RUN npm ci && npx turbo build --filter=client

FROM nginx:alpine
COPY --from=builder /app/apps/client/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
```

### 9.4 Deliverables

- [ ] CI pipeline runs lint, typecheck, test, e2e, build
- [ ] Deploy pipeline builds and pushes Docker images
- [ ] Production Docker Compose with PostgreSQL, server, client, nginx
- [ ] Environment variable management (`.env.example`, secrets in CI)
- [ ] Health check endpoints

---

## Phase 10: Data Migration & Launch

**Goal:** Migrate existing game data and cut over from ColdFusion to the new stack.

### 10.1 Data Migration Script

```typescript
// scripts/migrate-data.ts
// 1. Connect to MSSQL source
// 2. For each table:
//    a. Read all rows
//    b. Transform data (hash passwords, convert dates, restructure JSON)
//    c. Insert into PostgreSQL via Prisma
// 3. Validate row counts
// 4. Verify referential integrity
// 5. Run score recalculation on all players
// 6. Generate migration report
```

### 10.2 Migration Checklist

| Step | Action | Rollback Plan |
|------|--------|--------------|
| 1 | Announce maintenance window to players | — |
| 2 | Set ColdFusion game to read-only mode | Re-enable writes |
| 3 | Run MSSQL → PostgreSQL data migration | Keep MSSQL running |
| 4 | Verify data integrity (row counts, checksums) | Abort, revert to MSSQL |
| 5 | Run full test suite against migrated data | Abort, revert to MSSQL |
| 6 | Deploy new application | Revert DNS to old server |
| 7 | Update DNS to point to new server | Revert DNS |
| 8 | Monitor for 24 hours | Revert DNS + restore MSSQL |
| 9 | Decommission ColdFusion server | — |

### 10.3 Post-Launch Monitoring

- Application error tracking (Sentry or similar)
- Database performance monitoring (pg_stat_statements)
- API response time alerts
- WebSocket connection health
- Player feedback channel

### 10.4 Deliverables

- [ ] Data migration script tested on copy of production data
- [ ] Migration completes in < 1 hour
- [ ] All player accounts accessible with new password hashes
- [ ] Game state identical post-migration (scores, buildings, queues)
- [ ] Monitoring dashboards operational

---

## Implementation Timeline

| Phase | Description | Dependencies | Estimated Effort |
|-------|-------------|-------------|-----------------|
| 1 | Project Scaffolding | None | Foundation |
| 2 | Shared Types & Constants | Phase 1 | Foundation |
| 3 | Database Schema | Phase 1 | Foundation |
| 4 | Core Game Engine | Phases 2, 3 | Largest phase — all game logic |
| 5 | Backend API | Phases 3, 4 | Connects engine to HTTP |
| 6 | Frontend Application | Phases 2, 5 | All UI work |
| 7 | Real-Time Features | Phases 5, 6 | WebSocket layer |
| 8 | Testing & QA | Phases 4, 5, 6 | Ongoing, peaks here |
| 9 | CI/CD Pipeline | Phase 1 (can start early) | Infrastructure |
| 10 | Data Migration & Launch | All phases | Final cutover |

**Parallelism opportunities:**
- Phases 2 + 3 can run in parallel
- Phase 9 (CI/CD) can start during Phase 1 and evolve
- Frontend (Phase 6) can start with mock data while Phase 5 is in progress
- Testing (Phase 8) should be continuous from Phase 4 onward

---

## File Count Comparison

| Original (ColdFusion) | Modern (TypeScript) |
|----------------------|-------------------|
| ~80 `.cfm` files | ~50 server files |
| 1 `.sql` schema file | 1 Prisma schema + migrations |
| 0 test files | ~100 test files |
| 0 config files | ~15 config files (TS, Docker, CI, ESLint) |
| **~81 total files** | **~200 total files** |

The file count increases because the modern codebase separates concerns (engine, routes, services, components, tests) rather than mixing logic and presentation in monolithic templates.

---

## Security Improvements

| Vulnerability | Original | Modern |
|--------------|----------|--------|
| SQL Injection | Inline `#variable#` in SQL | Prisma parameterized queries |
| Password Storage | Plaintext in DB | bcrypt with salt rounds=12 |
| Authentication | Session cookie, no expiry control | JWT with configurable expiry |
| Input Validation | Minimal `cfparam` defaults | Zod schemas on every endpoint |
| XSS | No output encoding | React auto-escapes by default |
| CSRF | No protection | SameSite cookies + CSRF tokens |
| Rate Limiting | None | Fastify rate-limit plugin |
| CORS | Not applicable (same-origin) | Explicit CORS configuration |

---

## Summary

This plan transforms 1000 AD from a 25-year-old ColdFusion monolith into a modern TypeScript application while preserving every game mechanic exactly as designed. The key principles are:

1. **Game logic as pure functions** — every formula is testable in isolation
2. **Type safety everywhere** — TypeScript catches bugs at compile time
3. **Separation of concerns** — engine, API, and UI are independent layers
4. **Comprehensive testing** — 400+ tests ensure nothing is lost in translation
5. **Modern infrastructure** — Docker, CI/CD, monitoring from day one
6. **Progressive enhancement** — WebSocket features enhance but don't replace core gameplay
