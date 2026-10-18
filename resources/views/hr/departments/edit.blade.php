@extends('layouts.app')
@section('title', 'Edit Department')

@section('content')
    <x-page-header :title="'Edit · '.$department->name" :subtitle="$department->code" />

    <x-card>
        <form method="POST" action="{{ route('hr.departments.update', $department) }}">
            @csrf @method('PUT')
            @include('hr.departments._form')
        </form>
    </x-card>
@endsection
