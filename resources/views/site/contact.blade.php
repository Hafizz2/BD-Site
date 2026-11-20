<x-layouts.app :title="$page?->t('title')" :description="$page?->seo_description ?: $page?->t('intro')">
<x-site.page-hero :page="$page" :crumbs="[$page?->t('title') => null]" />

<section class="section">
  <div class="container">
    <div class="split">
      <div class="panel reveal">
        @if (session('contact_sent'))
          <div class="alert alert-success" style="margin-bottom:18px">{{ __('site.message_sent') }}</div>
        @endif
        <form method="post" action="{{ route('contact.store') }}" class="form" novalidate>
          @csrf
          <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="form-grid two">
            <div @class(['field', 'invalid' => $errors->has('name')])>
              <label for="c_name">{{ __('site.name') }} <span class="req">*</span></label>
              <input id="c_name" type="text" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name">
              @error('name')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div @class(['field', 'invalid' => $errors->has('phone')])>
              <label for="c_phone">{{ __('site.phone') }}</label>
              <input id="c_phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="20" autocomplete="tel">
              @error('phone')<div class="err">{{ $message }}</div>@enderror
            </div>
          </div>
          <div @class(['field', 'invalid' => $errors->has('email')])>
            <label for="c_email">{{ __('site.email') }}</label>
            <input id="c_email" type="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email">
            @error('email')<div class="err">{{ $message }}</div>@enderror
          </div>
          <div class="field">
            <label for="c_subject">{{ __('site.subject') }}</label>
            <input id="c_subject" type="text" name="subject" value="{{ old('subject') }}" maxlength="150">
          </div>
          <div @class(['field', 'invalid' => $errors->has('message')])>
            <label for="c_message">{{ __('site.message') }} <span class="req">*</span></label>
            <textarea id="c_message" name="message" required maxlength="3000">{{ old('message') }}</textarea>
            @error('message')<div class="err">{{ $message }}</div>@enderror
          </div>
          <div><button class="btn btn-primary" type="submit">{{ __('site.send_message') }} {!! \App\Support\Icons::svg('arrow', '', 2) !!}</button></div>
        </form>
      </div>

      <aside class="reveal">
        <div class="panel dark">
          <div class="info-list">
            @if ($site->phone)<div class="row"><span class="ic">{!! \App\Support\Icons::svg('phone') !!}</span><div><small>{{ __('site.phone') }}</small><b><a href="tel:{{ preg_replace('/[^\d+]/', '', $site->phone) }}">{{ $site->phone }}</a></b>@if($site->phone_secondary)<br><b><a href="tel:{{ preg_replace('/[^\d+]/', '', $site->phone_secondary) }}">{{ $site->phone_secondary }}</a></b>@endif</div></div>@endif
            @if ($site->email)<div class="row"><span class="ic">{!! \App\Support\Icons::svg('mail') !!}</span><div><small>{{ __('site.email') }}</small><b><a href="mailto:{{ $site->email }}">{{ $site->email }}</a></b></div></div>@endif
            @if ($site->t('address'))<div class="row"><span class="ic">{!! \App\Support\Icons::svg('map') !!}</span><div><small>{{ __('site.address') }}</small><b>{{ $site->t('address') }}</b></div></div>@endif
            @if ($site->t('office_hours'))<div class="row"><span class="ic">{!! \App\Support\Icons::svg('clock') !!}</span><div><small>{{ __('site.hours') }}</small><b>{{ $site->t('office_hours') }}</b></div></div>@endif
          </div>
          @if ($site->socials())
            <h3 style="margin-top:24px">{{ __('site.follow_us') }}</h3>
            <x-site.socials />
          @endif
        </div>
        @if ($site->map_embed_url)
          <div class="panel" style="padding:0;overflow:hidden;margin-top:16px">
            <iframe src="{{ $site->map_embed_url }}" width="100%" height="280" style="border:0;display:block" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map"></iframe>
          </div>
        @endif
      </aside>
    </div>
    @if ($page?->t('body'))<div class="prose reveal mt-36">{!! $page->t('body') !!}</div>@endif
  </div>
</section>
</x-layouts.app>
