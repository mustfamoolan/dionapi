@extends('partials.layouts.master')

@section('title', 'المستخدمين | Herozi')
@section('sub-title', 'المستخدمين')
@section('pagetitle', 'لوحة التحكم')
@section('buttonTitle', '<i class="ri-add-line me-1"></i> إضافة مستخدم')
@section('modalTarget', 'addUserModal')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/libs/air-datepicker/air-datepicker.css') }}">
@endsection

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card mb-0 h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">قائمة المستخدمين</h5>
            </div>
            <div class="card-body">
                <table class="data-table-users table-hover align-middle table table-nowrap w-100">
                    <thead class="bg-light bg-opacity-30">
                        <tr>
                            <th>الاسم</th>
                            <th>رقم الهاتف</th>
                            <th>الدور</th>
                            <th>تاريخ الإضافة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="addUserModalLabel">إضافة مستخدم جديد</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="user_id" name="user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">يرجى إدخال الاسم.</div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                        <div class="invalid-feedback">يرجى إدخال رقم الهاتف.</div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            كلمة المرور
                            <span class="text-danger" id="password-required">*</span>
                            <small class="text-muted" id="password-hint" style="display: none;">(اتركه فارغاً إذا لم تريد تغييره)</small>
                        </label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="invalid-feedback">يرجى إدخال كلمة مرور (8 أحرف على الأقل).</div>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">الدور <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">اختر الدور</option>
                            <option value="admin">مدير</option>
                            <option value="employee">موظف</option>
                        </select>
                        <div class="invalid-feedback">يرجى اختيار الدور.</div>
                    </div>
                    <div class="mb-3">
                        <label for="avatar" class="form-label">الصورة الشخصية</label>
                        <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                        <div class="invalid-feedback">يرجى اختيار صورة صحيحة.</div>
                        <div id="avatar-preview" class="mt-2" style="display: none;">
                            <img id="avatar-preview-img" src="" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<!-- Datatable js -->
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let usersTable;
    const userForm = document.getElementById('userForm');
    const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
    const modalTitle = document.getElementById('addUserModalLabel');
    const userIdInput = document.getElementById('user_id');
    const passwordInput = document.getElementById('password');
    const passwordRequired = document.getElementById('password-required');
    const passwordHint = document.getElementById('password-hint');
    const avatarPreview = document.getElementById('avatar-preview');
    const avatarPreviewImg = document.getElementById('avatar-preview-img');

    // Initialize DataTable
    const tableElement = document.querySelector('.data-table-users');
    if (tableElement) {
        usersTable = new DataTable(tableElement, {
            ajax: {
                url: '{{ route("admin.users.data") }}',
                dataSrc: 'data'
            },
            columns: [
                {
                    data: 'name',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex gap-3 justify-content-start align-items-center">
                                <div class="avatar avatar-sm">
                                    <img src="${row.avatar}" alt="Avatar" class="avatar-item avatar rounded-circle">
                                </div>
                                <div class="d-flex flex-column">
                                    <p class="mb-0 fw-medium">${data}</p>
                                </div>
                            </div>
                        `;
                    }
                },
                { data: 'phone' },
                {
                    data: 'role_badge',
                    orderable: false
                },
                { data: 'created_at' },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            dom: '<"card-header dt-head d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3"' +
                '<"head-label">' +
                '<"d-flex flex-column flex-sm-row align-items-center justify-content-sm-end gap-3 w-100"f>' +
                '>' +
                '<"table-responsive"t>' +
                '<"card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2"i' +
                '<"d-flex align-items-sm-center justify-content-end gap-4">p' +
                '>',
            language: {
                sLengthMenu: 'عرض _MENU_',
                search: '',
                searchPlaceholder: 'بحث...',
                paginate: {
                    next: '<i class="ri-arrow-left-s-line"></i>',
                    previous: '<i class="ri-arrow-right-s-line"></i>'
                },
                emptyTable: 'لا توجد بيانات',
                info: 'عرض _START_ إلى _END_ من _TOTAL_ مستخدم',
                infoEmpty: 'عرض 0 إلى 0 من 0 مستخدم',
                infoFiltered: '(تم تصفية من _MAX_ مستخدم)'
            },
            lengthMenu: [10, 20, 50],
            pageLength: 10,
            order: [[3, 'desc']]
        });

        // Edit user
        tableElement.querySelector('tbody').addEventListener('click', function(e) {
            if (e.target.closest('.edit-user')) {
                const userId = e.target.closest('.edit-user').dataset.id;
                editUser(userId);
            }

            // Delete user
            if (e.target.closest('.delete-user')) {
                const userId = e.target.closest('.delete-user').dataset.id;
                deleteUser(userId);
            }
        });
    }

    // Avatar preview
    document.getElementById('avatar').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                avatarPreviewImg.src = e.target.result;
                avatarPreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Form submit
    userForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(userForm);
        const userId = userIdInput.value;
        const url = userId
            ? `{{ url('admin/users') }}/${userId}`
            : '{{ route("admin.users.store") }}';
        const method = userId ? 'PUT' : 'POST';

        // Add _method for PUT request
        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }

        // Validate password for new users
        if (!userId && !formData.get('password')) {
            passwordInput.classList.add('is-invalid');
            return;
        }

        // Show loading
        const submitBtn = userForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الحفظ...';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                usersTable.ajax.reload();
                addUserModal.hide();
                userForm.reset();
                avatarPreview.style.display = 'none';
                userIdInput.value = '';
                passwordRequired.style.display = 'inline';
                passwordHint.style.display = 'none';
                passwordInput.required = true;

                // Show success message
                showAlert('success', data.message);
            } else {
                showAlert('danger', data.message || 'حدث خطأ ما');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'حدث خطأ أثناء الحفظ');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    // Edit user function
    function editUser(userId) {
        fetch(`{{ url('admin/users') }}/${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    userIdInput.value = user.id;
                    document.getElementById('name').value = user.name;
                    document.getElementById('phone').value = user.phone;
                    document.getElementById('role').value = user.role;
                    passwordInput.value = '';
                    passwordInput.required = false;
                    passwordRequired.style.display = 'none';
                    passwordHint.style.display = 'inline';

                    if (user.avatar) {
                        avatarPreviewImg.src = user.avatar;
                        avatarPreview.style.display = 'block';
                    } else {
                        avatarPreview.style.display = 'none';
                    }

                    modalTitle.textContent = 'تعديل مستخدم';
                    addUserModal.show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'حدث خطأ أثناء تحميل البيانات');
            });
    }

    // Delete user function
    function deleteUser(userId) {
        if (confirm('هل أنت متأكد من حذف هذا المستخدم؟')) {
            fetch(`{{ url('admin/users') }}/${userId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    usersTable.ajax.reload();
                    showAlert('success', data.message);
                } else {
                    showAlert('danger', data.message || 'حدث خطأ ما');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'حدث خطأ أثناء الحذف');
            });
        }
    }

    // Reset form when modal is closed
    document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function() {
        userForm.reset();
        userIdInput.value = '';
        avatarPreview.style.display = 'none';
        modalTitle.textContent = 'إضافة مستخدم جديد';
        passwordInput.required = true;
        passwordRequired.style.display = 'inline';
        passwordHint.style.display = 'none';
        userForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    });

    // Show alert function
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
});
</script>

<!-- App js -->
<script type="module" src="{{ asset('assets/js/app.js') }}"></script>
@endsection
