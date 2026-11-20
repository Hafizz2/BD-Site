@extends('errors.layout')

@section('code', '503')
@section('title', 'Back soon')
@section('title_am', 'በቅርቡ እንመለሳለን')
@section('message', 'We\'re updating the site right now. Please check back in a few minutes.')
@section('message_am', 'ድረ-ገጹን አሁን እያሻሻልን ነው። እባክዎ ከጥቂት ደቂቃዎች በኋላ ይመለሱ።')

@section('actions')
  <a class="btn btn-primary" href="javascript:location.reload()">{{ app()->getLocale() === 'am' ? 'ገጹን አድስ' : 'Refresh the page' }}</a>
  <a class="btn btn-ghost" href="{{ url('/') }}">{{ app()->getLocale() === 'am' ? 'ወደ መነሻ ገጽ' : 'Home page' }}</a>
@endsection
