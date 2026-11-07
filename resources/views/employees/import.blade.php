@extends('layouts.app')

@section('title', 'Impor Pegawai')

@section('content')
<div class="page-head">
    <div>
        <h1>Impor Pegawai</h1>
        <p>Tambahkan banyak pegawai sekaligus dari berkas CSV sesuai template daftar pegawai.</p>
    </div>
    <a class="btn btn-outline" href="{{ route('employees.index') }}">← Kembali</a>
</div>

@if (session('import_errors'))
    <div class="notice error" role="alert">
        <strong>Impor dibatalkan. Tidak ada data yang disimpan.</strong>
        <p class="muted mb-0">Perbaiki baris berikut lalu unggah ulang seluruh berkas.</p>
        <ul>
            @foreach (session('import_errors') as $error)
                <li>{{ $error['line'] > 0 ? 'Baris '.$error['line'].': ' : '' }}{{ $error['message'] }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid align-start">
    <section class="card" aria-labelledby="upload-heading">
        <h2 id="upload-heading">Unggah berkas</h2>
        <form method="POST" action="{{ route('employees.import.store') }}" enctype="multipart/form-data" class="stack">
            @csrf
            <div class="field">
                <label for="file">Berkas CSV (maks. 2 MB, {{ $maxRows }} baris)</label>
                <input id="file" type="file" name="file" accept=".csv,text/csv" required>
            </div>
            <div><button type="submit" class="btn" data-confirm="Impor seluruh baris pada berkas ini?">Impor pegawai</button></div>
        </form>
    </section>

    <section class="card" aria-labelledby="format-heading">
        <h2 id="format-heading">Format berkas</h2>
        <ul class="policy-list">
            <li>Baris pertama wajib persis: <code>{{ $header }}</code></li>
            <li>Pemisah koma atau titik koma (format ekspor Excel) diterima.</li>
            <li>Atur kolom NIP sebagai <strong>teks</strong> di Excel agar 18 digit tidak berubah.</li>
            <li>Email boleh kosong. Seluruh baris divalidasi dulu; bila ada satu baris salah, tidak ada yang disimpan.</li>
            <li>NIP yang sudah terdaftar tidak ditimpa.</li>
            <li>Akun hasil impor belum dapat login sampai Super Admin menetapkan kata sandi sementara lewat menu Ubah.</li>
        </ul>
    </section>
</div>
@endsection
