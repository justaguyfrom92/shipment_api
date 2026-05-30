<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
        {
                if (Schema::hasTable('shipments'))
                {
                        Schema::table('shipments', function (Blueprint $table)
                        {
                                // Check if column doesn't already exist
                                if (!Schema::hasColumn('shipments', 'filename'))
                                {
					$table->string('filename')->nullable();
                                }
                        });
                }
        }

        public function down(): void
        {
                if (Schema::hasTable('shipments'))
                {
                        Schema::table('shipments', function (Blueprint $table)
                        {
                                if (Schema::hasColumn('shipments', 'filename'))
                                {
					$table->dropColumn('filename');
                                }
                        });
                }
        }
};
