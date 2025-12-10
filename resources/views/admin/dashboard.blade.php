@extends('partials.layouts.master')

@section('title', 'لوحة التحكم | Herozi')
@section('sub-title', 'لوحة التحكم')
@section('pagetitle', 'لوحة التحكم')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">مرحباً بك في لوحة التحكم</h5>
            </div>
            <div class="card-body">
                <p class="card-text">هذه صفحة لوحة التحكم الرئيسية. سيتم إضافة المحتوى لاحقاً.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<!-- App js -->
<script type="module" src="{{ asset('assets/js/app.js') }}"></script>
@endsection

