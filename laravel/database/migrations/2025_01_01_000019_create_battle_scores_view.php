<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creates the battle_scores view used by BattleController.
 * Aggregates attack/defense wins from attack_news table per player.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE VIEW battle_scores AS
            SELECT
                p.id,
                p.name,
                COALESCE(attacks.num_attacks, 0) AS num_attacks,
                COALESCE(attacks.num_attack_wins, 0) AS num_attack_wins,
                COALESCE(defenses.num_defenses, 0) AS num_defenses,
                COALESCE(defenses.num_defense_wins, 0) AS num_defense_wins
            FROM players p
            LEFT JOIN (
                SELECT
                    attack_id,
                    COUNT(*) AS num_attacks,
                    SUM(CASE WHEN attacker_wins = 1 THEN 1 ELSE 0 END) AS num_attack_wins
                FROM attack_news
                GROUP BY attack_id
            ) attacks ON p.id = attacks.attack_id
            LEFT JOIN (
                SELECT
                    defense_id,
                    COUNT(*) AS num_defenses,
                    SUM(CASE WHEN attacker_wins = 0 THEN 1 ELSE 0 END) AS num_defense_wins
                FROM attack_news
                GROUP BY defense_id
            ) defenses ON p.id = defenses.defense_id
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS battle_scores");
    }
};
