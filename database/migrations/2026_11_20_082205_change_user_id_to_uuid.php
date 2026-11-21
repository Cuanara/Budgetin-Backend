<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Tabel yang punya user_id tapi memang TIDAK pakai foreign key.
     */
    private array $withoutForeignKey = ['sessions'];

    /**
     * Run the migrations: users.id bigint -> uuid.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $this->convert(toUuid: true);

        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', fn (Blueprint $t) => $t->softDeletes());
        }
    }

    /**
     * Reverse the migrations: users.id uuid -> bigint auto increment.
     */
    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropSoftDeletes());
        }

        $this->convert(toUuid: false);
    }

    /**
     * Ganti tipe users.id beserta semua kolom yang merujuk ke sana,
     * dengan tetap mempertahankan semua baris dan relasinya.
     */
    private function convert(bool $toUuid): void
    {
        $this->cleanupFailedRun();

        $column = fn (Blueprint $t, string $name) => $toUuid
            ? $t->uuid($name)
            : $t->unsignedBigInteger($name);

        $refs = $this->userReferences();

        // 1. users: buat kolom id baru sementara dan isi untuk setiap user yang sudah ada
        Schema::table('users', fn (Blueprint $t) => $column($t, 'new_id')->nullable()->after('id'));
        $this->fillNewUserIds($toUuid);

        // 2. Tabel anak: salin relasi ke kolom sementara, lalu lepas foreign key & index gabungan
        $grammar = DB::getQueryGrammar();
        $users = $grammar->wrapTable('users');

        foreach ($refs as &$ref) {
            ['table' => $table, 'column' => $col] = $ref;
            $tmp = "new_{$col}";

            $ref['nullable'] = $this->isNullable($table, $col);
            $ref['indexes'] = $this->compositeIndexes($table, $col);

            Schema::table($table, fn (Blueprint $t) => $column($t, $tmp)->nullable()->after($col));

            $child = $grammar->wrapTable($table);
            DB::statement(
                "UPDATE {$child} SET {$grammar->wrap($tmp)} = "
                ."(SELECT new_id FROM {$users} WHERE {$users}.id = {$child}.{$grammar->wrap($col)})"
            );

            if ($ref['fk']) {
                Schema::table($table, fn (Blueprint $t) => $t->dropForeign($ref['fk']['name']));
            }

            foreach ($ref['indexes'] as $index) {
                Schema::table($table, fn (Blueprint $t) => $index['unique']
                    ? $t->dropUnique($index['name'])
                    : $t->dropIndex($index['name']));
            }
        }
        unset($ref);

        // 3. users: ganti primary key
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('id'));
        Schema::table('users', fn (Blueprint $t) => $t->renameColumn('new_id', 'id'));
        Schema::table('users', fn (Blueprint $t) => $column($t, 'id')->nullable(false)->change());
        Schema::table('users', fn (Blueprint $t) => $t->primary('id'));

        if (! $toUuid) {
            // Khusus rollback: kembalikan skema awal (id angka berurutan).
            // MySQL otomatis melanjutkan dari MAX(id) + 1.
            Schema::table('users', fn (Blueprint $t) => $t->unsignedBigInteger('id')->autoIncrement()->change());
        }

        // 4. Tabel anak: ganti kolom, pasang lagi index & foreign key
        foreach ($refs as $ref) {
            ['table' => $table, 'column' => $col] = $ref;
            $tmp = "new_{$col}";

            Schema::table($table, fn (Blueprint $t) => $t->dropColumn($col));
            Schema::table($table, fn (Blueprint $t) => $t->renameColumn($tmp, $col));
            Schema::table($table, fn (Blueprint $t) => $column($t, $col)->nullable($ref['nullable'])->change());

            Schema::table($table, function (Blueprint $t) use ($ref, $table, $col) {
                $t->index($col);

                foreach ($ref['indexes'] as $index) {
                    $index['unique']
                        ? $t->unique($index['columns'], $index['name'])
                        : $t->index($index['columns'], $index['name']);
                }

                // Sesuai SOP: relasi tanpa aturan ON DELETE / ON UPDATE
                if ($ref['fk'] || ! in_array($table, $this->withoutForeignKey, true)) {
                    $t->foreign($col)->references('id')->on('users');
                }
            });
        }
    }

    /**
     * Cari semua kolom yang merujuk ke users.id, langsung dari database:
     * - kolom yang punya foreign key ke users.id
     * - kolom user_id yang foreign key-nya tidak ada (sessions, atau sisa run yang gagal)
     */
    private function userReferences(): array
    {
        $database = DB::connection()->getDatabaseName();
        $refs = [];

        foreach (Schema::getTables() as $info) {
            $table = $info['name'];

            if ($table === 'users' || (isset($info['schema']) && $info['schema'] !== $database)) {
                continue;
            }

            $found = [];

            foreach (Schema::getForeignKeys($table) as $fk) {
                if ($fk['foreign_table'] === 'users'
                    && $fk['foreign_columns'] === ['id']
                    && count($fk['columns']) === 1) {
                    $found[$fk['columns'][0]] = $fk;
                }
            }

            if (! isset($found['user_id']) && Schema::hasColumn($table, 'user_id')) {
                $found['user_id'] = null;
            }

            foreach ($found as $col => $fk) {
                $refs[] = ['table' => $table, 'column' => $col, 'fk' => $fk];
            }
        }

        return $refs;
    }

    /**
     * Hapus kolom sementara peninggalan migration yang gagal sebelumnya,
     * supaya migration ini aman dijalankan ulang.
     */
    private function cleanupFailedRun(): void
    {
        if (Schema::hasColumn('users', 'new_id')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('new_id'));
        }

        foreach (Schema::getTables() as $info) {
            foreach (Schema::getColumns($info['name']) as $col) {
                if (str_starts_with($col['name'], 'new_') && $this->isLeftover($info['name'], $col['name'])) {
                    Schema::table($info['name'], fn (Blueprint $t) => $t->dropColumn($col['name']));
                }
            }
        }
    }

    /**
     * new_xxx dianggap sisa run gagal kalau xxx adalah user_id
     * atau kolom lain yang punya foreign key ke users.id.
     */
    private function isLeftover(string $table, string $tmp): bool
    {
        $original = substr($tmp, 4);

        if ($table === 'users' || ! Schema::hasColumn($table, $original)) {
            return false;
        }

        if ($original === 'user_id') {
            return true;
        }

        foreach (Schema::getForeignKeys($table) as $fk) {
            if ($fk['foreign_table'] === 'users' && $fk['columns'] === [$original]) {
                return true;
            }
        }

        return false;
    }

    /**
     * up:   setiap user dapat UUID baru (urut sesuai created_at).
     * down: setiap user dapat angka 1, 2, 3, ... (urut sesuai created_at).
     */
    private function fillNewUserIds(bool $toUuid): void
    {
        $ids = DB::table('users')->orderBy('created_at')->orderBy('id')->pluck('id');

        foreach ($ids as $i => $id) {
            DB::table('users')->where('id', $id)->update([
                'new_id' => $toUuid ? (string) Str::orderedUuid() : $i + 1,
            ]);
        }
    }

    private function isNullable(string $table, string $column): bool
    {
        foreach (Schema::getColumns($table) as $col) {
            if ($col['name'] === $column) {
                return (bool) $col['nullable'];
            }
        }

        return false;
    }

    /**
     * Index gabungan yang memuat kolom ini, misalnya unique(user_id, name).
     */
    private function compositeIndexes(string $table, string $column): array
    {
        return array_values(array_filter(
            Schema::getIndexes($table),
            fn (array $i) => ! $i['primary']
                && count($i['columns']) > 1
                && in_array($column, $i['columns'], true),
        ));
    }
};