@extends('layouts.app')
@section('title', 'Add Shift')

@section('content')
    <x-page-header title="Add Shift" subtitle="Define working hours and rules." />

    <x-card>
        <form method="POST" action="{{ route('hr.shifts.store') }}">
            @csrf
            @include('hr.shifts._form')
        </form>
    </x-card>
@endsection
