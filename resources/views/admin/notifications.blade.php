@extends('partials.Layouts.master')

@section('title', 'الإشعارات | Herozi')
@section('sub-title', 'الإشعارات')
@section('pagetitle', 'إرسال الإشعارات')

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
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
                                    <label for="single_user_id" class="form-label">اختر المستخدم <span class="text-danger">*</span></label>
                                    <select class="form-select" id="single_user_id" name="user_id" required style="width: 100%;">
                                        <option value="">-- اختر مستخدم --</option>
                                    </select>
                                    <small class="form-text text-muted">ابحث عن المستخدم بالاسم أو البريد الإلكتروني</small>
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
                                    <label for="multiple_user_ids" class="form-label">اختر المستخدمين <span class="text-danger">*</span></label>
                                    <select class="form-select" id="multiple_user_ids" name="user_ids[]" multiple required style="width: 100%;">
                                    </select>
                                    <small class="form-text text-muted">يمكنك اختيار عدة مستخدمين. ابحث عن المستخدمين بالاسم أو البريد الإلكتروني</small>
                                    <div class="mt-2">
                                        <small class="text-muted" id="multiple_selected_count">لم يتم اختيار أي مستخدم</small>
                                    </div>
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
                                    <div class="alert alert-info" id="all_users_count_alert" style="display: none;">
                                        <i class="ri-information-line me-1"></i>
                                        <strong>عدد المستخدمين:</strong> <span id="all_users_count">0</span> مستخدم سيتم إرسال الإشعار لهم
                                    </div>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 for single user dropdown
    let clientsList = [];
    
    // Load clients list
    $.ajax({
        url: '{{ route("admin.clients.list") }}',
        method: 'GET',
        success: function(response) {
            if (response.success && response.data) {
                clientsList = response.data;
                
                // Populate single user select
                $('#single_user_id').empty().append('<option value="">-- اختر مستخدم --</option>');
                
                // Populate multiple users select
                $('#multiple_user_ids').empty();
                
                response.data.forEach(function(client) {
                    const displayText = client.name + (client.email ? ' (' + client.email + ')' : '');
                    
                    // Add to single select
                    $('#single_user_id').append(
                        $('<option></option>')
                            .attr('value', client.firebase_uid)
                            .text(displayText)
                    );
                    
                    // Add to multiple select
                    $('#multiple_user_ids').append(
                        $('<option></option>')
                            .attr('value', client.firebase_uid)
                            .text(displayText)
                    );
                });
            }
        },
        error: function() {
            console.error('Failed to load clients list');
        }
    });
    
    // Initialize Select2 for single user
    $('#single_user_id').select2({
        placeholder: 'ابحث عن مستخدم...',
        allowClear: true,
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            },
            searching: function() {
                return "جاري البحث...";
            }
        }
    });
    
    // Initialize Select2 for multiple users
    $('#multiple_user_ids').select2({
        placeholder: 'ابحث واختر عدة مستخدمين...',
        allowClear: true,
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            },
            searching: function() {
                return "جاري البحث...";
            }
        }
    });
    
    // Update selected count for multiple users
    $('#multiple_user_ids').on('change', function() {
        const selectedCount = $(this).val() ? $(this).val().length : 0;
        if (selectedCount > 0) {
            $('#multiple_selected_count').text('تم اختيار ' + selectedCount + ' مستخدم');
        } else {
            $('#multiple_selected_count').text('لم يتم اختيار أي مستخدم');
        }
    });
    
    // Update users count when filters change in "all" tab
    function updateUsersCount() {
        const filterStatus = $('#all_filter_status').val();
        const filterIsActive = $('#all_filter_is_active').val();
        
        $.ajax({
            url: '{{ route("admin.clients.count") }}',
            method: 'GET',
            data: {
                filter_status: filterStatus || '',
                filter_is_active: filterIsActive || ''
            },
            success: function(response) {
                if (response.success) {
                    const count = response.count || 0;
                    $('#all_users_count').text(count);
                    if (count > 0) {
                        $('#all_users_count_alert').show();
                    } else {
                        $('#all_users_count_alert').hide();
                    }
                }
            },
            error: function() {
                $('#all_users_count_alert').hide();
            }
        });
    }
    
    // Update count when filters change
    $('#all_filter_status, #all_filter_is_active').on('change', function() {
        updateUsersCount();
    });
    
    // Update count when "all" tab is shown
    $('#all-tab').on('shown.bs.tab', function() {
        updateUsersCount();
    });
    
    // Handle single user form
    $('#send-single-form').on('submit', function(e) {
        e.preventDefault();
        sendNotification($(this), 'single');
    });

    // Handle multiple users form
    $('#send-multiple-form').on('submit', function(e) {
        e.preventDefault();
        const userIds = $('#multiple_user_ids').val();
        if (!userIds || userIds.length === 0) {
            Swal.fire('خطأ', 'يرجى اختيار مستخدم واحد على الأقل', 'error');
            return;
        }
        
        sendNotification($(this), 'multiple');
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
                    let message = `تم إرسال <strong>${response.data.sent}</strong> إشعار بنجاح`;
                    if (response.data.failed > 0) {
                        message += `<br><small>فشل: ${response.data.failed}</small>`;
                    }
                    if (response.data.users_without_tokens > 0) {
                        message += `<br><small class="text-warning">${response.data.users_without_tokens} مستخدم ليس لديه tokens</small>`;
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'نجح الإرسال!',
                        html: message,
                        confirmButtonText: 'حسناً'
                    });
                    
                    // Reset forms
                    $('#send-single-form')[0].reset();
                    $('#single_user_id').val(null).trigger('change');
                    $('#send-multiple-form')[0].reset();
                    $('#multiple_user_ids').val(null).trigger('change');
                    $('#multiple_selected_count').text('لم يتم اختيار أي مستخدم');
                    $('#send-all-form')[0].reset();
                    updateUsersCount();
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

