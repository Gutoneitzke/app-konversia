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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->enum('direction', ['inbound', 'outbound']);

            // Tipo de mensagem
            $table->enum('type', [
                'text',           // texto simples
                'image',          // imagem
                'video',          // vídeo
                'audio',          // áudio (voz ou arquivo)
                'document',       // documento (PDF, DOC, etc.)
                'sticker',        // sticker
                'location',       // localização
                'contact',        // contato
                'link'            // link preview
            ])->default('text');

            // Conteúdo da mensagem
            $table->text('content')->nullable(); // texto da mensagem ou caption

            // Arquivo associado (storage local)
            $table->string('file_path')->nullable();        // caminho relativo: whatsapp/{company_id}/{conversation_id}/...
            $table->string('file_name')->nullable();        // nome original do arquivo
            $table->string('file_mime_type')->nullable();   // tipo MIME
            $table->unsignedBigInteger('file_size')->nullable(); // tamanho em bytes

            // Metadados específicos por tipo
            $table->json('media_metadata')->nullable();     // {
            //     image: {width, height}
            //     video: {width, height, duration}
            //     audio: {duration}
            //     document: {page_count}
            //     location: {latitude, longitude, address}
            //     contact: {name, phone}
            //     link: {title, description, url}
            // }

            // Status de entrega
            $table->timestamp('sent_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->enum('delivery_status', ['sent', 'delivered', 'read', 'failed'])->default('sent');

            // Relacionamentos
            $table->foreignId('reply_to_message_id')->nullable()->constrained('messages');

            // WhatsApp specific
            $table->string('whatsapp_message_id')->nullable(); // ID único do WhatsApp
            $table->json('whatsapp_metadata')->nullable();     // metadados específicos do WhatsApp

            $table->timestamps();

            // Índices para performance
            $table->index(['conversation_id', 'created_at']);
            $table->index(['whatsapp_message_id']);
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
