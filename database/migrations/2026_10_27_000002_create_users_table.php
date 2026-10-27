<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Modul: Access.
     *
     * Login-Konto. status und preferred_two_factor_method sind bewusst
     * string statt MySQL-ENUM (einfachere Erweiterbarkeit). anonymized_at
     * / anonymized_ref tragen die weiche Loeschung aus dem Offboarding-
     * Modul (harte Loeschung ist nicht vorgesehen).
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('person_id')
                ->nullable()
                ->unique()
                ->constrained('persons')
                ->restrictOnDelete();

            $table->string('email', 254)->unique();
            $table->string('password');

            $table->string('status', 32);

            $table->timestamp('email_verified_at')->nullable();
            $table->string('preferred_two_factor_method', 32)->nullable();

            $table->rememberToken();

            $table->unsignedInteger('session_version')->default(1);

            $table->timestamp('force_password_change_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->string('avatar_path', 512)->nullable();

            $table->timestamp('anonymized_at')->nullable();
            $table->char('anonymized_ref', 26)->nullable()->unique();

            $table->timestamps();
        });

        Schema::create(
            'password_reset_tokens',
            function (Blueprint $table): void {
                $table->string('email', 254)->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            },
        );

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
