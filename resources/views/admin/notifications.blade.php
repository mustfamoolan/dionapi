@extends('partials.Layouts.master')

@section('title', 'الإشعارات | Herozi')
@section('sub-title', 'الإشعارات')
@section('pagetitle', 'إرسال الإشعارات')

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card mb-0 h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">إرسال إشعارات FCM</h5>
            </div>
            <div class="card-body">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="notificationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#single" type="button" role="tab">
                            <i class="ri-user-line me-1"></i> مستخدم واحد
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="multiple-tab" data-bs-toggle="tab" data-bs-target="#multiple" type="button" role="tab">
                            <i class="ri-group-line me-1"></i> عدة مستخدمين
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                            <i class="ri-global-line me-1"></i> جميع المستخدمين
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="notificationTabsContent">
                    <!-- Single User Tab -->
                    <div class="tab-pane fade show active" id="single" role="tabpanel">
                        <form id="send-single-form">
                            @csrf
                            <input type="hidden" name="type" value="single">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="single_user_id" class="form-label">Firebase UID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="single_user_id" name="user_id" placeholder="أدخل Firebase UID للمستخدم" required>
                                    <small class="form-text text-muted">يمكنك الحصول على UID من صفحة العملاء</small>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="single_notification_title" class="form-label">عنوان الإشعار <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="single_notification_title" name="notification_title" placeholder="مثال: تحديث مهم" required maxlength="255">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="single_notification_body" class="form-label">محتوى الإشعار <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="single_notification_body" name="notification_body" rows="3" placeholder="مثال: تم إضافة ميزات جديدة" required maxlength="1000"></textarea>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="single_notification_type" class="form-label">نوع الإشعار <span class="text-danger">*</span></label>
                                    <select class="form-select" id="single_notification_type" name="notification_type" required>
                                        <option value="general">عام</option>
                                        <option value="overdue_debt">دين متأخر</option>
                                        <option value="debt_due_soon">موعد سداد قريب</option>
                                        <option value="low_stock">مخزون منخفض</option>
                                        <option value="subscription_activated">تم تفعيل الاشتراك</option>
                                        <option value="subscription_expired">انتهى الاشتراك</option>
                                        <option value="subscription_expiring_soon">الاشتراك قريب الانتهاء</option>
                                        <option value="account_banned">تم حظر الحساب</option>
                                        <option value="account_pending">الحساب في الانتظار</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="single_data" class="form-label">بيانات إضافية (JSON - اختياري)</label>
                                    <textarea class="form-control font-monospace" id="single_data" name="data" rows="4" placeholder='{"key": "value"}'></textarea>
                                    <small class="form-text text-muted">أدخل بيانات JSON إضافية للإشعار</small>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-send-plane-line me-1"></i> إرسال الإشعار
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Multiple Users Tab -->
                    <div class="tab-pane fade" id="multiple" role="tabpanel">
                        <form id="send-multiple-form">
                            @csrf
                            <input type="hidden" name="type" value="multiple">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="multiple_user_ids" class="form-label">Firebase UIDs <span class="text-danger">*</span></label>
                                    <textarea class="form-control font-monospace" id="multiple_user_ids" name="user_ids" rows="5" placeholder="أدخل Firebase UIDs (واحد في كل سطر)&#10;UTx1JE3TLuTtNJU9vKLzoIHdpAs1&#10;abc123xyz...&#10;def456uvw..." required></textarea>
                                    <small class="form-text text-muted">أدخل Firebase UID لكل مستخدم في سطر منفصل</small>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="multiple_notification_title" class="form-label">عنوان الإشعار <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="multiple_notification_title" name="notification_title" placeholder="مثال: تحديث مهم" required maxlength="255">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="multiple_notification_body" class="form-label">محتوى الإشعار <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="multiple_notification_body" name="notification_body" rows="3" placeholder="مثال: تم إضافة ميزات جديدة" required maxlength="1000"></textarea>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="multiple_notification_type" class="form-label">نوع الإشعار <span class="text-danger">*</span></label>
                                    <select class="form-select" id="multiple_notification_type" name="notification_type" required>
                                        <option value="general">عام</option>
                                        <option value="overdue_debt">دين متأخر</option>
                                        <option value="debt_due_soon">موعد سداد قريب</option>
                                        <option value="low_stock">مخزون منخفض</option>
                                        <option value="subscription_activated">تم تفعيل الاشتراك</option>
                                        <option value="subscription_expired">انتهى الاشتراك</option>
                                        <option value="subscription_expiring_soon">الاشتراك قريب الانتهاء</option>
                                        <option value="account_banned">تم حظر الحساب</option>
                                        <option value="account_pending">الحساب في الانتظار</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="multiple_data" class="form-label">بيانات إضافية (JSON - اختياري)</label>
                                    <textarea class="form-control font-monospace" id="multiple_data" name="data" rows="4" placeholder='{"key": "value"}'></textarea>
                                    <small class="form-text text-muted">أدخل بيانات JSON إضافية للإشعار</small>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-send-plane-line me-1"></i> إرسال الإشعارات
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- All Users Tab -->
                    <div class="tab-pane fade" id="all" role="tabpanel">
                        <form id="send-all-form">
                            @csrf
                            <input type="hidden" name="type" value="all">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="all_filter_status" class="form-label">فلتر حسب الحالة (اختياري)</label>
                                    <select class="form-select" id="all_filter_status" name="filter_status">
                                        <option value="">جميع الحالات</option>
                                        <option value="pending">في الانتظار</option>
                                        <option value="active">مفعل</option>
                                        <option value="banned">محظور</option>
                                        <option value="expired">انتهى الاشتراك</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="all_filter_is_active" class="form-label">فلتر حسب التفعيل (اختياري)</label>
                                    <select class="form-select" id="all_filter_is_active" name="filter_is_active">
                                        <option value="">الكل</option>
                                        <option value="1">مفعل فقط</option>
                                        <option value="0">غير مفعل فقط</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="all_notification_title" class="form-label">عنوان الإشعار <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="all_notification_title" name="notification_title" placeholder="مثال: تحديث مهم" required maxlength="255">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="all_notification_body" class="form-label">محتوى الإشعار <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="all_notification_body" name="notification_body" rows="3" placeholder="مثال: تم إضافة ميزات جديدة" required maxlength="1000"></textarea>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="all_notification_type" class="form-label">نوع الإشعار <span class="text-danger">*</span></label>
                                    <select class="form-select" id="all_notification_type" name="notification_type" required>
                                        <option value="general">عام</option>
                                        <option value="overdue_debt">دين متأخر</option>
                                        <option value="debt_due_soon">موعد سداد قريب</option>
                                        <option value="low_stock">مخزون منخفض</option>
                                        <option value="subscription_activated">تم تفعيل الاشتراك</option>
                                        <option value="subscription_expired">انتهى الاشتراك</option>
                                        <option value="subscription_expiring_soon">الاشتراك قريب الانتهاء</option>
                                        <option value="account_banned">تم حظر الحساب</option>
                                        <option value="account_pending">الحساب في الانتظار</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="all_data" class="form-label">بيانات إضافية (JSON - اختياري)</label>
                                    <textarea class="form-control font-monospace" id="all_data" name="data" rows="4" placeholder='{"key": "value"}'></textarea>
                                    <small class="form-text text-muted">أدخل بيانات JSON إضافية للإشعار</small>
                                </div>
                                <div class="col-md-12">
                                    <div class="alert alert-warning">
                                        <i class="ri-alert-line me-1"></i>
                                        <strong>تحذير:</strong> سيتم إرسال هذا الإشعار لجميع المستخدمين المطابقين للفلتر. تأكد من صحة البيانات قبل الإرسال.
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-send-plane-line me-1"></i> إرسال للجميع
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Handle single user form
    $('#send-single-form').on('submit', function(e) {
        e.preventDefault();
        sendNotification($(this), 'single');
    });

    // Handle multiple users form
    $('#send-multiple-form').on('submit', function(e) {
        e.preventDefault();
        const userIdsText = $('#multiple_user_ids').val().trim();
        if (!userIdsText) {
            Swal.fire('خطأ', 'يرجى إدخال Firebase UIDs', 'error');
            return;
        }
        
        // Convert textarea to array
        const userIds = userIdsText.split('\n').map(id => id.trim()).filter(id => id);
        if (userIds.length === 0) {
            Swal.fire('خطأ', 'يرجى إدخال Firebase UIDs صحيحة', 'error');
            return;
        }
        
        // Build form data with user_ids array
        const formData = [];
        $(this).serializeArray().forEach(function(item) {
            if (item.name !== 'user_ids') {
                formData.push(item);
            }
        });
        // Add each user_id separately
        userIds.forEach(function(userId) {
            formData.push({name: 'user_ids[]', value: userId});
        });
        
        sendNotificationFormData(formData, 'multiple');
    });

    // Handle all users form
    $('#send-all-form').on('submit', function(e) {
        e.preventDefault();
        sendNotification($(this), 'all');
    });

    function sendNotification(form, type) {
        const formData = form.serializeArray();
        sendNotificationFormData(formData, type);
    }

    function sendNotificationFormData(formData, type) {
        // Show confirmation for "all" type
        if (type === 'all') {
            Swal.fire({
                title: 'تأكيد الإرسال',
                text: 'هل أنت متأكد من إرسال الإشعار لجميع المستخدمين؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، أرسل',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    performSend(formData);
                }
            });
        } else {
            performSend(formData);
        }
    }

    function performSend(formData) {
        // Show loading
        Swal.fire({
            title: 'جاري الإرسال...',
            text: 'يرجى الانتظار',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '{{ route("admin.notifications.send") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'نجح الإرسال!',
                        html: `تم إرسال <strong>${response.data.sent}</strong> إشعار بنجاح<br>
                               <small>فشل: ${response.data.failed}</small>`,
                        confirmButtonText: 'حسناً'
                    });
                    
                    // Reset forms
                    $('#send-single-form')[0].reset();
                    $('#send-multiple-form')[0].reset();
                    $('#send-all-form')[0].reset();
                } else {
                    Swal.fire('خطأ', response.message || 'فشل إرسال الإشعار', 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء إرسال الإشعار';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                }
                Swal.fire('خطأ', errorMessage, 'error');
            }
        });
    }
});
</script>
@endsection

