@extends('layouts.blog')

@section('content')
<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">Category: {{ $category->name }}</h2>
    <p style="color: var(--text-secondary);">{{ $category->description }}</p>
</div>

@include('blog.partials.post_list', ['posts' => $posts])
@endsection
