@extends('layouts.app')
@section('title', 'Edit Shift')

@section('content')
    <x-page-header :title="'Edit · '.$shift->name" />

    <x-card>
        <form method="POST" action="{{ route('hr.shifts.update', $shift) }}">
            @csrf @method('PUT')
            @include('hr.shifts._form')
        </form>
    </x-card>
@endsection
