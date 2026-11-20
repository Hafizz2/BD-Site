@extends('errors.layout')

@section('code', '419')
@section('title', 'This page expired')
@section('title_am', 'ገጹ ጊዜው አልፏል')
@section('message', 'The form was open for too long. Please go back, refresh the page and try again.')
@section('message_am', 'ቅጹ ለረጅም ጊዜ ተከፍቶ ቆይቷል። እባክዎ ወደ ኋላ ተመልሰው ገጹን ያድሱና እንደገና ይሞክሩ።')

@section('actions')
  <a class="btn btn-primary" href="javascript:location.reload()">{{ app()->getLocale() === 'am' ? 'ገጹን አድስ' : 'Refresh the page' }}</a>
  <a class="btn btn-ghost" href="{{ url('/') }}">{{ app()->getLocale() === 'am' ? 'ወደ መነሻ ገጽ' : 'Home page' }}</a>
@endsection
