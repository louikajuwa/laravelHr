@extends('layouts.app')
@section('title', 'Add Leave Type')

@section('content')
    <x-page-header title="Add Leave Type" subtitle="Define a new leave category." />

    <x-card>
        <form method="POST" action="{{ route('hr.leave-types.store') }}">
            @csrf
            @include('hr.leave_types._form')
        </form>
    </x-card>
@endsection
