@if (session('success'))
    <div class="notice success" role="status">{{ session('success') }}</div>
@endif
@if (session('warning'))
    <div class="notice warning" role="status">{{ session('warning') }}</div>
@endif
@if ($errors->any())
    <div class="notice error" role="alert">
        <strong>Perlu diperiksa</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
