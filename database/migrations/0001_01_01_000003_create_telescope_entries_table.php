<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        return Config::get('telescope.storage.database.connection');
    }

    /**
     * Get the database connection driver.
     */
    protected function driver(): string
    {
        return DB::connection($this->getConnection())->getDriverName();
    }

    /**
     * Determine if the migration should run.
     */
    public function shouldRun(): bool
    {
        if (in_array($this->driver(), ['mysql', 'pgsql'])) {
            return true;
        }

        if (! App::environment('testing')) {
            throw new RuntimeException("Telescope does not support the [{$this->driver()}] database driver.");
        }

        if (Config::get('telescope.enabled')) {
            throw new RuntimeException("Telescope does not support the [{$this->driver()}] database driver. You can disable Telescope in your testsuite by adding `<env name=\"TELESCOPE_ENABLED\" value=\"false\"/>` to your project's `phpunit.xml` file.");
        }

        return false;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! $this->shouldRun()) {
            return;
        }

        $schema = Schema::connection($this->getConnection());

        if (! $schema->hasTable('telescope_entries')) {
            $schema->create('telescope_entries', function (Blueprint $table) {
                $table->bigIncrements('sequence');
                $table->uuid('uuid');
                $table->uuid('batch_id');
                $table->string('family_hash')->nullable();
                $table->boolean('should_display_on_index')->default(true);
                $table->string('type', 20);
                $table->longText('content');
                $table->dateTime('created_at')->nullable();

                $table->unique('uuid');
                $table->index('batch_id');
                $table->index('family_hash');
                $table->index('created_at');
                $table->index(['type', 'should_display_on_index']);
            });
        }

        if (! $schema->hasTable('telescope_entries_tags')) {
            $schema->create('telescope_entries_tags', function (Blueprint $table) {
                $table->uuid('entry_uuid');
                $table->string('tag');

                $table->primary(['entry_uuid', 'tag']);
                $table->index('tag');

                $table->foreign('entry_uuid')
                    ->references('uuid')
                    ->on('telescope_entries')
                    ->onDelete('cascade');
            });
        }

        if (! $schema->hasTable('telescope_monitoring')) {
            $schema->create('telescope_monitoring', function (Blueprint $table) {
                $table->string('tag')->primary();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->shouldRun()) {
            return;
        }

        $schema = Schema::connection($this->getConnection());

        $schema->dropIfExists('telescope_entries_tags');
        $schema->dropIfExists('telescope_entries');
        $schema->dropIfExists('telescope_monitoring');
    }
};
