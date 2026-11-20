@props(['paginator'])
@if ($paginator->hasPages())
<nav class="pager" aria-label="Pagination">
  @if ($paginator->onFirstPage())<span class="off">‹</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a>@endif
  @foreach (range(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $p)
    @if ($p === $paginator->currentPage())<span class="on">{{ $p }}</span>@else<a href="{{ $paginator->url($p) }}">{{ $p }}</a>@endif
  @endforeach
  @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">›</a>@else<span class="off">›</span>@endif
</nav>
@endif
