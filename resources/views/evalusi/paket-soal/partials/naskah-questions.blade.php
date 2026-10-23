@foreach($rows as $row)
    <div class="naskah-question">
        @if(! empty($row['wacana']))
            <div class="naskah-wacana">
                @if(! empty($row['wacana']['judul']))
                    <div class="fw-semibold">{{ $row['wacana']['judul'] }}</div>
                @endif
                {!! nl2br(e($row['wacana']['teks'] ?? '')) !!}
                @if(! empty($row['wacana']['sumber']))
                    <div class="small text-muted">Sumber: {{ $row['wacana']['sumber'] }}</div>
                @endif
                @if(! empty($row['wacana']['rentang_nomor']))
                    <div class="small text-muted">Untuk soal nomor: {{ $row['wacana']['rentang_nomor'] }}</div>
                @endif
            </div>
        @endif

        <div class="naskah-question-row">
            <div class="naskah-number">{{ $row['nomor'] }}.</div>
            <div class="naskah-question-body">
                <div>{!! nl2br(e($row['pertanyaan'] ?: '—')) !!}</div>

                @if(! empty($row['gambar_path']))
                    <div class="mt-2">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($row['gambar_path']) }}" alt="Gambar soal" style="max-width:320px;max-height:240px;">
                        @if(! empty($row['gambar_keterangan']))
                            <div class="small text-muted">{{ $row['gambar_keterangan'] }}</div>
                        @endif
                    </div>
                @endif

                @if(! empty($row['opsi']))
                    <div class="naskah-options">
                        @foreach($row['opsi'] as $index => $opsi)
                            <div>
                                <span class="naskah-option-label">{{ $opsi['label'] ?? chr(65 + $index) }}.</span>
                                {{ $opsi['teks'] ?? '' }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endforeach
