@extends('pages.layout')

@section('title', $page->title($locale))

@section('pageContent')
    <h1 class="h3 fw-bold mb-4">{{ $page->title($locale) }}</h1>

    <div class="pdj-card page-prose">
        {!! $page->content($locale) !!}
    </div>
@endsection
