@extends('layouts.app')
@section('title', 'Add Department')

@section('content')
    <x-page-header title="Add Department" subtitle="Create a new team or division." />

    <x-card>
        <form method="POST" action="{{ route('hr.departments.store') }}">
            @csrf
            @include('hr.departments._form')
        </form>
    </x-card>
@endsection
