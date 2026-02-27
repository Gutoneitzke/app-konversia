<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => 'nullable|string|max:4096',
            'files' => 'nullable|array|max:20',
            'files.*' => 'file|max:15360|mimes:jpg,jpeg,png,gif,mp4,mp3,wav,pdf,doc,docx,txt,zip',
            // Manter compatibilidade com 'file' único para versões anteriores
            'file' => 'nullable|file|max:15360|mimes:jpg,jpeg,png,gif,mp4,mp3,wav,pdf,doc,docx,txt,zip',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.string' => 'O conteúdo deve ser um texto válido.',
            'content.max' => 'O conteúdo não pode ter mais que 4096 caracteres.',
            'files.array' => 'Os arquivos devem ser enviados como uma lista.',
            'files.max' => 'Não é possível enviar mais que 20 arquivos por vez.',
            'files.*.file' => 'Cada item deve ser um arquivo válido.',
            'files.*.max' => 'Cada arquivo não pode ser maior que 15MB.',
            'files.*.mimes' => 'Tipo de arquivo não permitido. Use apenas: JPG, PNG, GIF, MP4, MP3, WAV, PDF, DOC, DOCX, TXT, ZIP.',
            'file.file' => 'O arquivo deve ser válido.',
            'file.max' => 'O arquivo não pode ser maior que 15MB.',
            'file.mimes' => 'Tipo de arquivo não permitido. Use apenas: JPG, PNG, GIF, MP4, MP3, WAV, PDF, DOC, DOCX, TXT, ZIP.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Verificar arquivos separadamente - múltiplas estratégias
            $uploadedFiles = $this->getUploadedFiles();

            // Validação customizada: deve ter pelo menos conteúdo OU pelo menos um arquivo
            $hasContent = !empty(trim($this->input('content', '')));
            $hasFiles = count($uploadedFiles) > 0;

            if (!$hasContent && !$hasFiles) {
                $validator->errors()->add('content', 'Digite uma mensagem ou selecione pelo menos um arquivo para enviar.');
            }
        });
    }

    /**
     * Get uploaded files using multiple strategies
     */
    private function getUploadedFiles(): array
    {

        $uploadedFiles = [];

        // Estratégia 1: files[] como array direto
        $filesArray = $this->file('files');
        if ($filesArray && is_array($filesArray)) {
            $uploadedFiles = array_filter($filesArray, function($file) {
                return $file instanceof \Illuminate\Http\UploadedFile;
            });
        }

        // Estratégia 2: se não funcionou, tentar acessar de outras formas
        if (empty($uploadedFiles)) {
            $allFiles = $this->files->all();
            if (isset($allFiles['files']) && is_array($allFiles['files'])) {
                $uploadedFiles = array_filter($allFiles['files'], function($file) {
                    return $file instanceof \Illuminate\Http\UploadedFile;
                });
            }
        }

        // Estratégia 3: verificar se é um único arquivo em vez de array
        $singleFile = $this->file('file');
        if (empty($uploadedFiles) && $singleFile) {
            $uploadedFiles = [$singleFile];
        }


        return $uploadedFiles;
    }

    /**
     * Get the uploaded files (public method for controller access)
     */
    public function getValidatedFiles(): array
    {
        return $this->getUploadedFiles();
    }
}
