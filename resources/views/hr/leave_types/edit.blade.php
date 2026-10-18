@extends('layouts.app')
@section('title', 'Edit Leave Type')

@section('content')
    <x-page-header :title="'Edit · '.$leaveType->name" :subtitle="$leaveType->code" />

    <x-card>
        <form method="POST" action="{{ route('hr.leave-types.update', $leaveType) }}">
            @csrf @method('PUT')
            @include('hr.leave_types._form')
        </form>
    </x-card>
@endsection
