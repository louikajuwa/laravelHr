@extends('layouts.app')
@section('title', 'Add Position')

@section('content')
    <x-page-header title="Add Position" subtitle="Create a new job title." />

    <x-card>
        <form method="POST" action="{{ route('hr.positions.store') }}">
            @csrf
            @include('hr.positions._form')
        </form>
    </x-card>
@endsection
