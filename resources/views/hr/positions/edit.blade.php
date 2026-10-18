@extends('layouts.app')
@section('title', 'Edit Position')

@section('content')
    <x-page-header :title="'Edit · '.$position->title" />

    <x-card>
        <form method="POST" action="{{ route('hr.positions.update', $position) }}">
            @csrf @method('PUT')
            @include('hr.positions._form')
        </form>
    </x-card>
@endsection
