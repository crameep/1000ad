<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('login_name', 50)->unique();
            $table->string('password', 255);
            $table->string('email', 50);
            $table->bigInteger('score')->default(0);
            $table->integer('civ')->default(1);
            $table->integer('is_admin')->default(0);
            $table->integer('turn')->default(0);
            $table->timestamp('last_turn')->nullable();
            $table->integer('turns_free')->default(0);
            $table->timestamp('last_load')->nullable();
            $table->integer('food_ratio')->default(0);
            $table->integer('killed_by')->default(0);
            $table->string('killed_by_name', 50)->nullable();
            $table->boolean('has_new_messages')->default(false);
            $table->string('validation_code', 50)->nullable();
            $table->timestamp('created_on')->nullable();
            $table->text('message')->nullable();

            // Resources
            $table->bigInteger('wood')->default(0);
            $table->bigInteger('food')->default(0);
            $table->bigInteger('iron')->default(0);
            $table->bigInteger('gold')->default(0);
            $table->integer('tools')->default(0);
            $table->integer('swords')->default(0);
            $table->integer('bows')->default(0);
            $table->integer('horses')->default(0);
            $table->integer('maces')->default(0);
            $table->integer('wine')->default(0);
            $table->integer('people')->default(0);

            // Land
            $table->integer('fland')->default(0);
            $table->integer('mland')->default(0);
            $table->integer('pland')->default(0);

            // Buildings
            $table->integer('wood_cutter')->default(0);
            $table->integer('hunter')->default(0);
            $table->integer('farmer')->default(0);
            $table->integer('house')->default(0);
            $table->integer('iron_mine')->default(0);
            $table->integer('gold_mine')->default(0);
            $table->integer('tool_maker')->default(0);
            $table->integer('weapon_smith')->default(0);
            $table->integer('fort')->default(0);
            $table->integer('tower')->default(0);
            $table->integer('town_center')->default(0);
            $table->integer('market')->default(0);
            $table->integer('warehouse')->default(0);
            $table->integer('stable')->default(0);
            $table->integer('mage_tower')->default(0);
            $table->integer('winery')->default(0);
            $table->integer('wall')->default(0);
            $table->integer('wall_build_per_turn')->default(0);

            // Building statuses (0-100)
            $table->integer('hunter_status')->default(0);
            $table->integer('farmer_status')->default(0);
            $table->integer('iron_mine_status')->default(0);
            $table->integer('gold_mine_status')->default(0);
            $table->integer('tool_maker_status')->default(0);
            $table->integer('weapon_smith_status')->default(0);
            $table->integer('stable_status')->default(0);
            $table->integer('wood_cutter_status')->default(0);
            $table->integer('mage_tower_status')->default(0);
            $table->integer('winery_status')->default(0);

            // Weapon smith allocation
            $table->integer('bow_weapon_smith')->default(0);
            $table->integer('sword_weapon_smith')->default(0);
            $table->integer('mace_weaponsmith')->default(0);
            $table->integer('builder')->default(0);

            // Military
            $table->integer('swordsman')->default(0);
            $table->integer('archers')->default(0);
            $table->integer('horseman')->default(0);
            $table->integer('catapults')->default(0);
            $table->integer('macemen')->default(0);
            $table->integer('trained_peasants')->default(0);
            $table->integer('thieves')->default(0);
            $table->integer('uunit')->default(0);
            $table->integer('num_attacks')->default(0);
            $table->timestamp('last_attack')->nullable();

            // Scores
            $table->integer('military_score')->default(0);
            $table->integer('land_score')->default(0);
            $table->integer('good_score')->default(0);

            // Auto trade
            $table->integer('auto_sell_wood')->default(0);
            $table->integer('auto_buy_wood')->default(0);
            $table->integer('auto_sell_food')->default(0);
            $table->integer('auto_buy_food')->default(0);
            $table->integer('auto_sell_iron')->default(0);
            $table->integer('auto_buy_iron')->default(0);
            $table->integer('auto_sell_tools')->default(0);
            $table->integer('auto_buy_tools')->default(0);

            // Alliance
            $table->integer('alliance_id')->default(0);
            $table->integer('has_alliance_news')->default(0);
            $table->integer('alliance_member_type')->default(0);
            $table->integer('has_main_news')->default(0);
            $table->integer('trades_this_turn')->default(0);

            // Research
            $table->integer('research1')->default(0);
            $table->integer('research2')->default(0);
            $table->integer('research3')->default(0);
            $table->integer('research4')->default(0);
            $table->integer('research5')->default(0);
            $table->integer('research6')->default(0);
            $table->integer('research7')->default(0);
            $table->integer('research8')->default(0);
            $table->integer('research9')->default(0);
            $table->integer('research10')->default(0);
            $table->integer('research11')->default(0);
            $table->integer('research12')->default(0);
            $table->integer('current_research')->default(0);
            $table->bigInteger('research_points')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
