@if ($paginator->hasPages())
    <nav class="pagination between" aria-label="Navigasi halaman">
        <span class="muted">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }} · {{ $paginator->total() }} data</span>
        <div class="row">
            @if ($paginator->onFirstPage())
                <span class="btn btn-outline btn-sm" aria-disabled="true">&laquo; Sebelumnya</span>
            @else
                <a class="btn btn-outline btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Sebelumnya</a>
            @endif
            @if ($paginator->hasMorePages())
                <a class="btn btn-outline btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya &raquo;</a>
            @else
                <span class="btn btn-outline btn-sm" aria-disabled="true">Berikutnya &raquo;</span>
            @endif
        </div>
    </nav>
@endif
