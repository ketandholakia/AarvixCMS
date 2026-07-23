<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_runs', function (Blueprint $table) {
            $table->json('policy_snapshot')->nullable()->after('prompt_key');
            $table->json('budget_snapshot')->nullable()->after('policy_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_runs', function (Blueprint $table) {
            $table->dropColumn(['policy_snapshot', 'budget_snapshot']);
        });
    }
};
