{{-- One registration form field. $f = field config (see App\Support\RegistrationForm). --}}
@php
    use App\Support\RegistrationForm;
    $key = $f['key'];
    $name = $f['builtin'] ? $key : "answers[$key]";
    $dot = $f['builtin'] ? $key : "answers.$key";
    $id = $f['builtin'] ? $key : 'f_'.$key;
    $label = RegistrationForm::label($f);
    $required = (bool) $f['required'];
    $value = old($dot);
    $help = $f['help'] ?? null;
    if ($key === 'phone' && ! $help) { $help = __('site.form.phone_hint'); }
    $star = $required ? '<span class="req">*</span>' : '';
@endphp

@if ($f['type'] === 'checkbox')
  {{-- Tick box, e.g. consent --}}
  <div @class(['field', 'invalid' => $errors->has($dot)])>
    <label class="check"><input type="checkbox" name="{{ $name }}" value="1" @checked($value) @required($required)>
      <span>{{ $label }} {!! $star !!}</span></label>
    @if ($help)<div class="hint">{{ $help }}</div>@endif
    @error($dot)<div class="err">{{ $message }}</div>@enderror
  </div>
@else
<div @class(['field', 'invalid' => $errors->has($dot) || $errors->has($dot.'.*')])>
  @if (in_array($f['type'], ['gender', 'select', 'yes_no', 'checkboxes'], true))
    <label>{{ $label }} {!! $star !!}</label>
  @else
    <label for="{{ $id }}">{{ $label }} {!! $star !!}</label>
  @endif

  @switch($f['type'])
    @case('gender')
      <div class="radio-row">
        <label><input type="radio" name="{{ $name }}" value="male" @checked($value === 'male') @required($required)> {{ __('site.form.male') }}</label>
        <label><input type="radio" name="{{ $name }}" value="female" @checked($value === 'female')> {{ __('site.form.female') }}</label>
      </div>
      @break
    @case('yes_no')
      <div class="radio-row">
        <label><input type="radio" name="{{ $name }}" value="yes" @checked($value === 'yes') @required($required)> {{ app()->getLocale() === 'am' ? 'አዎ' : 'Yes' }}</label>
        <label><input type="radio" name="{{ $name }}" value="no" @checked($value === 'no')> {{ app()->getLocale() === 'am' ? 'አይ' : 'No' }}</label>
      </div>
      @break
    @case('select')
      <div class="radio-row" style="flex-wrap:wrap">
        @foreach ($f['options'] as $opt)
          <label><input type="radio" name="{{ $name }}" value="{{ $opt }}" @checked($value === $opt) @required($required)> {{ $opt }}</label>
        @endforeach
      </div>
      @break
    @case('checkboxes')
      <div class="radio-row" style="flex-wrap:wrap">
        @foreach ($f['options'] as $opt)
          <label><input type="checkbox" name="{{ $name }}[]" value="{{ $opt }}" @checked(in_array($opt, (array) $value, true))> {{ $opt }}</label>
        @endforeach
      </div>
      @break
    @case('sub_city')
      <select id="{{ $id }}" name="{{ $name }}" @required($required)>
        <option value=""></option>
        @foreach (RegistrationForm::SUB_CITIES as $sc)
          <option value="{{ $sc }}" @selected($value === $sc)>{{ $sc }}</option>
        @endforeach
      </select>
      @break
    @case('textarea')
      <textarea id="{{ $id }}" name="{{ $name }}" maxlength="{{ $f['builtin'] ? 1000 : 3000 }}" style="min-height:90px" @required($required)>{{ $value }}</textarea>
      @break
    @case('date')
      <input id="{{ $id }}" type="date" name="{{ $name }}" value="{{ $value }}" @required($required)
        @if ($key === 'date_of_birth') max="{{ now()->subYears(3)->toDateString() }}" @endif>
      @break
    @case('phone')
      <input id="{{ $id }}" type="tel" name="{{ $name }}" value="{{ $value }}" maxlength="20" inputmode="tel" @required($required)
        @if ($key === 'phone') autocomplete="tel" placeholder="09XX XXX XXX" @endif>
      @break
    @case('email')
      <input id="{{ $id }}" type="email" name="{{ $name }}" value="{{ $value }}" maxlength="150" autocomplete="email" @required($required)>
      @break
    @case('number')
      <input id="{{ $id }}" type="number" name="{{ $name }}" value="{{ $value }}" step="any" @required($required)>
      @break
    @default
      <input id="{{ $id }}" type="text" name="{{ $name }}" value="{{ $value }}" maxlength="{{ $key === 'full_name' ? 150 : 500 }}" @required($required)
        @if ($key === 'full_name') autocomplete="name" @endif>
  @endswitch

  @if ($help)<div class="hint">{{ $help }}</div>@endif
  @error($dot)<div class="err">{{ $message }}</div>@enderror
  @error($dot.'.*')<div class="err">{{ $message }}</div>@enderror
</div>
@endif
