@extends('layouts.app')
@section('title', 'Edit Salary Structure')

@section('content')
    <x-page-header title="Edit Salary Structure" :subtitle="$structure->user->name" />

    <x-card>
        <form method="POST" action="{{ route('hr.salary-structures.update', $structure) }}">
            @csrf
            @method('PUT')
            @include('hr.salary_structures._form')
        </form>
    </x-card>
@endsection
