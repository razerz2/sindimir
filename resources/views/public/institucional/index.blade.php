@extends('layouts.public')

@section('title', 'Institucional')

@section('content')
    @include('public.institucional.partials.hero')
    @include('public.institucional.partials.sobre')
    @include('public.institucional.partials.solucoes')
    @include('public.institucional.partials.diferenciais')
    @include('public.institucional.partials.cta')
@endsection

@section('footer')
    @include('public.institucional.partials.footer')
@endsection
