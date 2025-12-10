@extends('partials.Layouts.master')

@section('title', 'العملاء | Herozi')
@section('sub-title', 'العملاء')
@section('pagetitle', 'العملاء')

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
                            <th>آخر تسجيل دخول</th>
                            <th>تاريخ التسجيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $client)
                        <tr>
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
                                @if($client->is_active)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-danger">غير نشط</span>
                                @endif
                            </td>
                            <td>{{ $client->last_login_at ? $client->last_login_at->format('Y-m-d H:i') : '-' }}</td>
                            <td>{{ $client->created_at->format('Y-m-d') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableElement = document.getElementById('clients-table');

        // Initialize DataTable
        const dataTable = new DataTable(tableElement, {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
            },
            order: [[6, 'desc']], // Order by created_at descending
            pageLength: 10,
            responsive: true,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        });
    });
</script>
<!-- App js -->
<script type="module" src="{{ asset('assets/js/app.js') }}"></script>
@endsection
