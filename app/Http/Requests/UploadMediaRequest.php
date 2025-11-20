<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Authorization happens at the controller level (admin middleware),
     * so we always return true here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get max upload size from config (in MB), default to 200MB
        $maxSizeMB = config('media.max_upload_size_mb', 200);
        $maxSizeKB = $maxSizeMB * 1024;

        return [
            // Multiple file uploads
            'files' => 'required|array|min:1|max:20',
            'files.*' => [
                'required',
                'file',
                "max:{$maxSizeKB}",
                'mimes:' . $this->getAllowedMimeTypes(),
            ],
            
            // Optional metadata
            'alt_text' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get the allowed MIME types for upload.
     * 
     * Configurable via config/media.php
     */
    protected function getAllowedMimeTypes(): string
    {
        $mimeTypes = config('media.allowed_mime_types', [
            // Audio formats
            'mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma',
            // Video formats
            'mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv',
            // Image formats
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff',
            // Document formats
            'pdf', 'doc', 'docx', 'txt', 'rtf',
            // Transcript/subtitle formats
            'vtt', 'srt',
            // Archive formats (for batch uploads)
            'zip',
        ]);

        return implode(',', $mimeTypes);
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        $maxSizeMB = config('media.max_upload_size_mb', 200);
        
        return [
            'files.required' => 'Please select at least one file to upload.',
            'files.min' => 'Please select at least one file to upload.',
            'files.*.required' => 'Each selected file is required.',
            'files.*.file' => 'Each uploaded item must be a valid file.',
            'files.*.max' => "Each file size must not exceed {$maxSizeMB}MB.",
            'files.*.mimes' => 'One or more file types are not supported. Please upload audio, video, image, or document files.',
            'files.max' => 'You can upload a maximum of 20 files at once.',
        ];
    }

    /**
     * Get custom attribute names for validation.
     */
    public function attributes(): array
    {
        return [
            'files.*' => 'file',
        ];
    }

    /**
     * Handle a failed validation attempt for HTMX requests.
     * 
     * Returns a 422 response with validation errors instead of redirecting,
     * which prevents HTMX from displaying the full page.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->header('HX-Request')) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
