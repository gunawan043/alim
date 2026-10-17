<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nama Program <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" value="{{ old('nama', $penerima->kesejahteraan->nama ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Jenis Kesejahteraan <span class="text-danger">*</span></label>
        <select name="jenis" class="form-select" required>
            @php $j = old('jenis', $penerima->kesejahteraan->jenis ?? 'tunjangan'); @endphp
            <option value="tunjangan" {{ $j=='tunjangan'?'selected':'' }}>Tunjangan</option>
            <option value="bantuan" {{ $j=='bantuan'?'selected':'' }}>Bantuan</option>
            <option value="santunan" {{ $j=='santunan'?'selected':'' }}>Santunan</option>
            <option value="asuransi" {{ $j=='asuransi'?'selected':'' }}>Asuransi</option>
            <option value="bpjs" {{ $j=='bpjs'?'selected':'' }}>BPJS</option>
        </select>
    </div>
    <div class="col-md-12">
        <label class="form-label">Deskripsi</label>
        <textarea name="deskripsi" class="form-control" rows="2">{{ old('deskripsi', $penerima->kesejahteraan->deskripsi ?? '') }}</textarea>
    </div>
    <div class="col-md-4">
        <label class="form-label">Nominal / Nilai (Rp)</label>
        <input type="number" name="nominal" class="form-control" value="{{ old('nominal', $penerima->kesejahteraan->nominal ?? 0) }}" min="0">
    </div>
    <div class="col-md-4">
        <label class="form-label">Periode Mulai</label>
        <input type="date" name="periode_mulai" class="form-control" value="{{ old('periode_mulai', isset($penerima->kesejahteraan) ? optional($penerima->kesejahteraan->periode_mulai)->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Periode Selesai</label>
        <input type="date" name="periode_selesai" class="form-control" value="{{ old('periode_selesai', isset($penerima->kesejahteraan) ? optional($penerima->kesejahteraan->periode_selesai)->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-12">
        <hr>
        <h6 class="text-muted">Data Penerima</h6>
    </div>
    <div class="col-md-6">
        <label class="form-label">Penerima (GTK) <span class="text-danger">*</span></label>
        <select name="user_id" class="form-select" required>
            <option value="">-- Pilih GTK --</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ old('user_id', $penerima->user_id ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Tanggal Pemberian</label>
        <input type="date" name="tanggal_pemberian" class="form-control" value="{{ old('tanggal_pemberian', isset($penerima->tanggal_pemberian) ? $penerima->tanggal_pemberian->format('Y-m-d') : date('Y-m-d')) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        @php $st = old('status', $penerima->status ?? 'pending'); @endphp
        <select name="status" class="form-select" required>
            <option value="pending" {{ $st=='pending'?'selected':'' }}>Pending</option>
            <option value="aktif" {{ $st=='aktif'?'selected':'' }}>Aktif</option>
            <option value="selesai" {{ $st=='selesai'?'selected':'' }}>Selesai</option>
            <option value="ditolak" {{ $st=='ditolak'?'selected':'' }}>Ditolak</option>
        </select>
    </div>
    <div class="col-md-12">
        <label class="form-label">Catatan</label>
        <textarea name="catatan" class="form-control" rows="2">{{ old('catatan', $penerima->catatan ?? '') }}</textarea>
    </div>
</div>
