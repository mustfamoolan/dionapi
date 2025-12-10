<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
        return [
            'firebase_uid' => 'required|string|unique:clients,firebase_uid',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'photo_url' => 'nullable|url',
            'provider' => 'required|string|in:google,facebook,apple',
            'provider_id' => 'nullable|string',
            'device_token' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'firebase_uid.required' => 'معرف Firebase مطلوب',
            'firebase_uid.unique' => 'هذا المعرف مستخدم مسبقاً',
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً',
            'provider.required' => 'مزود OAuth مطلوب',
            'provider.in' => 'مزود OAuth غير مدعوم',
        ];
    }
}
