@if(($nilaiPendingCount ?? 0) > 0)
    <span class="badge bg-danger ms-auto"
          title="{{ $nilaiPendingCount }} kelas perlu dilengkapi nilainya atau dipublikasikan">
        {{ $nilaiPendingCount }}
    </span>
@endif
