<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Выполняем прямую команду смены типа данных
        // Это автоматически сбросит констреинт JSON_VALID в MariaDB/MySQL
        DB::statement('ALTER TABLE users MODIFY COLUMN data TEXT NULL');
        DB::statement('ALTER TABLE agreement_parties MODIFY COLUMN attorney_data TEXT NULL');
        DB::statement('ALTER TABLE mediation_contract_parties MODIFY COLUMN attorney_data TEXT NULL');
    }

    public function down(): void
    {
        // Возвращаем тип JSON обратно, если потребуется откат
        DB::statement('ALTER TABLE users MODIFY COLUMN data JSON NULL');
        DB::statement('ALTER TABLE agreement_parties MODIFY COLUMN attorney_data JSON NULL');
        DB::statement('ALTER TABLE mediation_contract_parties MODIFY COLUMN attorney_data JSON NULL');
    }
};
