@extends('backend.template.backend')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Tambah Akun Jasa Raharja</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('jasa-raharja.store') }}">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Nama</label>
                                            <input type="text" name="name" class="form-control"
                                                value="{{ old('name') }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" name="username" class="form-control"
                                                value="{{ old('username') }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" name="password" class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">API Key</label>
                                            <div class="input-group">
                                                <input type="text" name="api_key" id="jrApiKeyNew" class="form-control"
                                                    value="{{ old('api_key') }}" required>
                                                <button type="button" class="btn btn-outline-secondary" id="btnGenerateJrApiKeyNew">Generate</button>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary mt-3" type="submit">Simpan</button>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Daftar Akun Jasa Raharja</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th width="70">ID</th>
                                                <th>Nama</th>
                                                <th>Username</th>
                                                <th>API Key</th>
                                                <th>Password Baru</th>
                                                <th width="220">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($users as $user)
                                                <tr>
                                                    <td>{{ $user->id }}</td>
                                                    <td>
                                                        <input form="update-jr-{{ $user->id }}" type="text"
                                                            name="name" class="form-control form-control-sm"
                                                            value="{{ $user->name }}" required>
                                                    </td>
                                                    <td>
                                                        <input form="update-jr-{{ $user->id }}" type="text"
                                                            name="username" class="form-control form-control-sm"
                                                            value="{{ $user->email }}" required>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input form="update-jr-{{ $user->id }}" type="text"
                                                                name="api_key" class="form-control form-control-sm jr-api-key-input"
                                                                value="{{ $user->apikey }}" required>
                                                            <button type="button" class="btn btn-outline-secondary btn-sm btn-generate-jr-api-key">Generate</button>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input form="update-jr-{{ $user->id }}" type="password"
                                                            name="password" class="form-control form-control-sm"
                                                            placeholder="Kosongkan jika tidak diubah">
                                                    </td>
                                                    <td>
                                                        <form id="update-jr-{{ $user->id }}" method="POST"
                                                            action="{{ route('jasa-raharja.update', $user->id) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('jasa-raharja.destroy', $user->id) }}"
                                                            class="d-inline" onsubmit="return confirm('Hapus akun ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">Belum ada akun Jasa Raharja.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    function generateJrApiKey() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let key = 'sk-jr-';
        const bytes = new Uint8Array(24);
        window.crypto.getRandomValues(bytes);
        for (let i = 0; i < bytes.length; i++) {
            key += chars[bytes[i] % chars.length];
        }
        return key;
    }

    document.getElementById('btnGenerateJrApiKeyNew')?.addEventListener('click', function () {
        document.getElementById('jrApiKeyNew').value = generateJrApiKey();
    });

    document.querySelectorAll('.btn-generate-jr-api-key').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = this.closest('.input-group')?.querySelector('.jr-api-key-input');
            if (input) {
                input.value = generateJrApiKey();
            }
        });
    });
</script>
@endpush
