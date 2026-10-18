@extends('layouts.app')
@section('title', 'Edit Employee')

@section('content')
    <x-page-header :title="'Edit · '.$employee->name" :subtitle="$employee->employee_code" />

    <x-card>
        <form method="POST" action="{{ route('hr.employees.update', $employee) }}">
            @csrf @method('PUT')
            @include('hr.employees._form')
        </form>
    </x-card>
@endsection
