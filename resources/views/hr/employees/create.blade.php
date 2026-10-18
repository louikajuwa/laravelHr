@extends('layouts.app')
@section('title', 'Add Employee')

@section('content')
    <x-page-header title="Add Employee" subtitle="Create a new staff account." />

    <x-card>
        <form method="POST" action="{{ route('hr.employees.store') }}">
            @csrf
            @include('hr.employees._form')
        </form>
    </x-card>
@endsection
