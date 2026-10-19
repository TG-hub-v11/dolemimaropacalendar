<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('timezone')->default('Asia/Manila');
            $t->timestamps();
        });

        Schema::create('offices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->restrictOnDelete();
            $t->foreignId('parent_office_id')->nullable()->constrained('offices')->restrictOnDelete();
            $t->string('code', 32)->unique();
            $t->string('name');
            $t->enum('level', ['regional', 'provincial', 'field'])->default('field');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 64)->unique();
            $t->string('name');
            $t->timestamps();
        });

        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 96)->unique();
            $t->string('description')->nullable();
            $t->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $t->primary(['role_id', 'permission_id']);
        });

        // Users: no public registration; accounts are created by administrators.
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('primary_office_id')->nullable()->after('id')->constrained('offices')->restrictOnDelete();
            $t->string('position_title')->nullable();
            $t->boolean('is_active')->default(true)->index();
            $t->timestamp('deactivated_at')->nullable();
            $t->timestamp('password_changed_at')->nullable();
        });

        // A user has exactly one primary office (users.primary_office_id) and any number of
        // additional memberships. Every membership carries its own role, so a person can be
        // Office Administrator in one office and Personnel in another. Regional visibility
        // comes only from a role holding the `scope.regional` permission.
        Schema::create('office_memberships', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('office_id')->constrained()->cascadeOnDelete();
            $t->foreignId('role_id')->constrained()->restrictOnDelete();
            $t->boolean('is_primary')->default(false);
            $t->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'office_id']);
            $t->index(['office_id', 'role_id']);
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 64);
            $t->string('subject_type', 64)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $t->json('changes')->nullable(); // never store passwords, tokens or confidential bodies
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['subject_type', 'subject_id']);
            $t->index(['user_id', 'created_at']);
        });

        // Retry protection for mobile sync: one stored response per (user, operation id).
        Schema::create('idempotency_keys', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->uuid('operation_id');
            $t->string('method', 8);
            $t->string('path');
            $t->unsignedSmallInteger('response_status');
            $t->json('response_body')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'operation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('office_memberships');
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('primary_office_id');
            $t->dropColumn(['position_title', 'is_active', 'deactivated_at', 'password_changed_at']);
        });
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('offices');
        Schema::dropIfExists('organizations');
    }
};
