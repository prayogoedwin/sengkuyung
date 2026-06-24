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
                                <h5 class="mb-0">Tambah Version App</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('version.store') }}">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Nama Aplikasi</label>
                                            <input type="text" name="nama_aplikasi" class="form-control"
                                                value="{{ old('nama_aplikasi') }}" placeholder="web / mobile" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Versi</label>
                                            <input type="text" name="versi" class="form-control"
                                                value="{{ old('versi') }}" placeholder="2.0.5" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Alias</label>
                                            <input type="text" name="alias" class="form-control"
                                                value="{{ old('alias') }}" placeholder="beta / alpha">
                                        </div>
                                    </div>
                                    <button class="btn btn-primary mt-3" type="submit">Simpan</button>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Daftar Version App</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th width="70">ID</th>
                                                <th>Nama Aplikasi</th>
                                                <th>Versi</th>
                                                <th>Alias</th>
                                                <th width="220">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($versions as $version)
                                                <tr>
                                                    <td>{{ $version->id }}</td>
                                                    <td>
                                                        <input form="update-version-{{ $version->id }}" type="text"
                                                            name="nama_aplikasi" class="form-control form-control-sm"
                                                            value="{{ $version->nama_aplikasi }}" required>
                                                    </td>
                                                    <td>
                                                        <input form="update-version-{{ $version->id }}" type="text"
                                                            name="versi" class="form-control form-control-sm"
                                                            value="{{ $version->versi }}" required>
                                                    </td>
                                                    <td>
                                                        <input form="update-version-{{ $version->id }}" type="text"
                                                            name="alias" class="form-control form-control-sm"
                                                            value="{{ $version->alias }}">
                                                    </td>
                                                    <td>
                                                        <form id="update-version-{{ $version->id }}" method="POST"
                                                            action="{{ route('version.update', $version->id) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit"
                                                                class="btn btn-sm btn-warning">Update</button>
                                                        </form>
                                                        <form method="POST"
                                                            action="{{ route('version.destroy', $version->id) }}"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Hapus data versi ini?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn btn-sm btn-danger">Hapus</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">Belum ada data versi.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
@endsection
