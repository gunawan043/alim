@php
    use App\Services\Ljk\LjkArabicText;
@endphp
@if(! empty($block['opsi']))
    <table class="opts">
        <tr>
            @for($i = 0; $i < $maxOptions; $i++)
                @php
                    // RTL: opsi pertama (ا) di kolom paling kanan.
                    $idx = $rtl ? ($maxOptions - 1 - $i) : $i;
                    $opsi = $block['opsi'][$idx] ?? null;
                @endphp
                <td style="width: {{ $colWidth }}%; text-align: {{ $rtl ? 'right' : 'left' }};">
                    @if($opsi)
                        @if($rtl)
                            {{-- RTL: teks dulu, lalu penanda ".ج" di ujung kanan. --}}
                            {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                            <span class="letter">.{!! LjkArabicText::toHtml($opsi['display_label'] ?? $opsi['label'], false) !!}</span>
                        @else
                            <span class="letter">{!! LjkArabicText::toHtml($opsi['display_label'] ?? $opsi['label'], false) !!}.</span>
                            {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                        @endif
                    @else
                        &nbsp;
                    @endif
                </td>
            @endfor
        </tr>
    </table>
@endif
