<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SendToUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|string',
            'notification' => 'required|array',
            'notification.title' => 'required|string|max:255',
            'notification.body' => 'required|string|max:1000',
            'data' => 'nullable|array',
            'data.type' => 'nullable|string|in:overdue_debt,debt_due_soon,low_stock,subscription_activated,subscription_expired,subscription_expiring_soon,account_banned,account_pending,general',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'معرف المستخدم مطلوب',
            'notification.required' => 'بيانات الإشعار مطلوبة',
            'notification.array' => 'بيانات الإشعار يجب أن تكون مصفوفة',
            'notification.title.required' => 'عنوان الإشعار مطلوب',
            'notification.title.string' => 'عنوان الإشعار يجب أن يكون نص',
            'notification.title.max' => 'عنوان الإشعار يجب ألا يتجاوز 255 حرف',
            'notification.body.required' => 'محتوى الإشعار مطلوب',
            'notification.body.string' => 'محتوى الإشعار يجب أن يكون نص',
            'notification.body.max' => 'محتوى الإشعار يجب ألا يتجاوز 1000 حرف',
            'data.array' => 'البيانات الإضافية يجب أن تكون مصفوفة',
            'data.type.in' => 'نوع الإشعار غير صحيح',
        ];
    }
}

