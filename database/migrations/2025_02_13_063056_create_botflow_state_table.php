<?php


use Botflow\Contracts\FlowStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('botflow_state', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('class')->nullable(false);
            $table->string('status')->nullable(false)->default(FlowStatus::QUEUED->value);

            $table->boolean('monopolizing')->nullable(false)->default(false);

            $table->jsonb('params')->nullable(false)->default('[]');
            $table->jsonb('data')->nullable(false)->default('[]');
            $table->jsonb('messages')->nullable(false)->default('[]');

            $table->bigInteger('telegram_user_id')->nullable();
            $table->bigInteger('telegram_chat_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('botflow_state');
    }
};
