<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table): void {
            $table->id();

            $table->string('title', 50)->nullable();
            $table->string('first_name', 100);
            $table->string('name_addition', 100)->nullable();
            $table->string('last_name', 100);
            $table->date('birth_date');

            $table->string('email', 254)->unique();

            $table->string('phone', 50)->nullable();

            $table->string('street', 150)->nullable();
            $table->string('house_number', 30)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 120)->nullable();
            $table->char('country_code', 2)->default('DE');

            $table->timestamps();

            $table->index([
                'last_name',
                'first_name',
                'birth_date',
            ]);
        });

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

            $table->rememberToken();

            $table
                ->unsignedInteger('session_version')
                ->default(1);

            $table
                ->timestamp('force_password_change_at')
                ->nullable();

            $table
                ->timestamp('last_login_at')
                ->nullable();

            $table
                ->timestamp('anonymized_at')
                ->nullable();

            $table
                ->char('anonymized_ref', 26)
                ->nullable()
                ->unique();

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

            $table
                ->unsignedBigInteger('user_id')
                ->nullable()
                ->index();

            $table
                ->string('ip_address', 45)
                ->nullable();

            $table->text('user_agent')->nullable();
            $table->longText('payload');

            $table
                ->integer('last_activity')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('persons');
    }
};