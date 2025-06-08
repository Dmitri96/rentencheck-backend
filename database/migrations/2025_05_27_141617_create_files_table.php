<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Original filename
            $table->string('filename'); // Stored filename (with UUID)
            $table->string('mime_type');
            $table->string('extension');
            $table->unsignedBigInteger('size'); // File size in bytes
            $table->string('disk')->default('local'); // Storage disk
            $table->string('path'); // Full path to file
            $table->string('type'); // File type category: 'pdf', 'image', 'document', etc.
            $table->text('description')->nullable();
            
            // Polymorphic relationship
            $table->string('fileable_type'); // Model class name
            $table->unsignedBigInteger('fileable_id'); // Model ID
            $table->index(['fileable_type', 'fileable_id']);
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional file metadata
            $table->boolean('is_public')->default(false);
            $table->timestamp('uploaded_at');
            $table->unsignedBigInteger('uploaded_by'); // User who uploaded
            
            $table->timestamps();
            
            // Indexes
            $table->index('type');
            $table->index('uploaded_by');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
