@extends('layouts.app')
@section('title', 'New Salary Structure')

@section('content')
    <x-page-header title="New Salary Structure" subtitle="Define pay rules for an employee." />

    <x-card>
        <form method="POST" action="{{ route('hr.salary-structures.store') }}">
            @csrf
            @include('hr.salary_structures._form')
        </form>
    </x-card>
@endsection
