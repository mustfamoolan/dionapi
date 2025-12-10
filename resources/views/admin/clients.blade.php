@extends('partials.Layouts.master')

@section('title', 'العملاء | Herozi')
@section('sub-title', 'العملاء')
@section('pagetitle', 'العملاء')

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card mb-0 h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">قائمة العملاء</h5>
            </div>
            <div class="card-body">
                <table class="data-table-basic table-hover align-middle table table-nowrap w-100" id="clients-table">
                    <thead class="bg-light bg-opacity-30">
                        <tr>
                            <th>الاسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>رقم الهاتف</th>
                            <th>المزود</th>
                            <th>الحالة</th>
                            <th>تاريخ انتهاء التفعيل</th>
                            <th>آخر تسجيل دخول</th>
                            <th>تاريخ التسجيل</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $client)
                        <tr data-id="{{ $client->id }}">
                            <td>
                                <div class="d-flex gap-3 justify-content-start align-items-center">
                                    <div class="avatar avatar-sm">
                                        <img src="{{ $client->photo_url ?? asset('assets/images/avatar/dummy-avatar.jpg') }}" alt="Avatar" class="avatar-item avatar rounded-circle">
                                    </div>
                                    <div class="d-flex flex-column">
                                        <p class="mb-0 fw-medium">{{ $client->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $client->email }}</td>
                            <td>{{ $client->phone ?? '-' }}</td>
                            <td>
                                @if($client->provider == 'google')
                                    <span class="badge bg-danger">Google</span>
                                @elseif($client->provider == 'facebook')
                                    <span class="badge bg-primary">Facebook</span>
                                @elseif($client->provider == 'apple')
                                    <span class="badge bg-dark">Apple</span>
                                @else
                                    <span class="badge bg-secondary">{{ $client->provider }}</span>
                                @endif
                            </td>
                            <td>
                                @if($client->status == 'active')
                                    <span class="badge bg-success">مفعل</span>
                                @elseif($client->status == 'pending')
                                    <span class="badge bg-warning">في الانتظار</span>
                                @elseif($client->status == 'banned')
                                    <span class="badge bg-danger">محظور</span>
                                @else
                                    <span class="badge bg-secondary">{{ $client->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($client->activation_expires_at)
                                    {{ $client->activation_expires_at->format('Y-m-d') }}
                                    @if($client->activation_expires_at->isPast())
                                        <span class="badge bg-danger ms-1">منتهي</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $client->last_login_at ? $client->last_login_at->format('Y-m-d H:i') : '-' }}</td>
                            <td>{{ $client->created_at->format('Y-m-d') }}</td>
                            <td>
                                <div class="hstack gap-2">
                                    @if($client->status != 'active')
                                    <button type="button" class="btn btn-sm btn-success activate-client" data-id="{{ $client->id }}" data-name="{{ $client->name }}" title="تفعيل">
                                        <i class="ri-check-line"></i>
                                    </button>
                                    @endif
                                    @if($client->status != 'banned')
                                    <button type="button" class="btn btn-sm btn-danger ban-client" data-id="{{ $client->id }}" data-name="{{ $client->name }}" title="حظر">
                                        <i class="ri-close-line"></i>
                                    </button>
                                    @endif
                                    @if($client->status != 'pending')
                                    <button type="button" class="btn btn-sm btn-warning pending-client" data-id="{{ $client->id }}" data-name="{{ $client->name }}" title="وضع في الانتظار">
                                        <i class="ri-time-line"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Activate Client Modal -->
<div class="modal fade" id="activateClientModal" tabindex="-1" aria-labelledby="activateClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="activateClientModalLabel">تفعيل العميل</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="activate-client-form">
                @csrf
                <input type="hidden" id="activate_client_id" name="client_id">
                <div class="modal-body">
                    <p id="activate_client_name" class="mb-3"></p>
                    <div class="mb-3">
                        <label for="activation_months" class="form-label">مدة التفعيل (بالأشهر) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="activation_months" name="months" min="1" max="120" value="1" required>
                        <small class="form-text text-muted">اختر عدد الأشهر من 1 إلى 120</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success">تفعيل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirm Status Change Modal -->
<div class="modal fade" id="confirmStatusModal" tabindex="-1" aria-labelledby="confirmStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="confirmStatusModalLabel">تأكيد تغيير الحالة</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="confirm-status-form">
                @csrf
                <input type="hidden" id="confirm_client_id" name="client_id">
                <input type="hidden" id="confirm_status" name="status">
                <div class="modal-body">
                    <p id="confirm_message"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="confirm_btn">تأكيد</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableElement = document.getElementById('clients-table');
        const activateModal = new bootstrap.Modal(document.getElementById('activateClientModal'));
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmStatusModal'));

        // Initialize DataTable
        const dataTable = new DataTable(tableElement, {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
            },
            order: [[7, 'desc']], // Order by created_at descending
            pageLength: 10,
            responsive: true,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        });

        // Activate Client
        tableElement.addEventListener('click', function(e) {
            if (e.target.closest('.activate-client')) {
                const btn = e.target.closest('.activate-client');
                const clientId = btn.dataset.id;
                const clientName = btn.dataset.name;
                
                document.getElementById('activate_client_id').value = clientId;
                document.getElementById('activate_client_name').textContent = `هل تريد تفعيل العميل: ${clientName}؟`;
                document.getElementById('activation_months').value = 1;
                activateModal.show();
            }

            // Ban Client
            if (e.target.closest('.ban-client')) {
                const btn = e.target.closest('.ban-client');
                const clientId = btn.dataset.id;
                const clientName = btn.dataset.name;
                
                document.getElementById('confirm_client_id').value = clientId;
                document.getElementById('confirm_status').value = 'banned';
                document.getElementById('confirm_message').textContent = `هل أنت متأكد من حظر العميل: ${clientName}؟`;
                document.getElementById('confirm_btn').className = 'btn btn-danger';
                document.getElementById('confirm_btn').textContent = 'حظر';
                confirmModal.show();
            }

            // Set Pending
            if (e.target.closest('.pending-client')) {
                const btn = e.target.closest('.pending-client');
                const clientId = btn.dataset.id;
                const clientName = btn.dataset.name;
                
                document.getElementById('confirm_client_id').value = clientId;
                document.getElementById('confirm_status').value = 'pending';
                document.getElementById('confirm_message').textContent = `هل تريد وضع العميل: ${clientName} في قائمة الانتظار؟`;
                document.getElementById('confirm_btn').className = 'btn btn-warning';
                document.getElementById('confirm_btn').textContent = 'وضع في الانتظار';
                confirmModal.show();
            }
        });

        // Activate Form Submit
        document.getElementById('activate-client-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const clientId = document.getElementById('activate_client_id').value;
            const months = document.getElementById('activation_months').value;
            
            fetch(`/admin/clients/${clientId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: 'active',
                    months: parseInt(months)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('نجاح!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('خطأ!', data.message || 'حدث خطأ ما', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('خطأ!', 'حدث خطأ أثناء التفعيل', 'error');
            })
            .finally(() => {
                activateModal.hide();
            });
        });

        // Confirm Status Form Submit
        document.getElementById('confirm-status-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const clientId = document.getElementById('confirm_client_id').value;
            const status = document.getElementById('confirm_status').value;
            
            fetch(`/admin/clients/${clientId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('نجاح!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('خطأ!', data.message || 'حدث خطأ ما', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('خطأ!', 'حدث خطأ أثناء تغيير الحالة', 'error');
            })
            .finally(() => {
                confirmModal.hide();
            });
        });
    });
</script>
<!-- App js -->
<script type="module" src="{{ asset('assets/js/app.js') }}"></script>
@endsection
