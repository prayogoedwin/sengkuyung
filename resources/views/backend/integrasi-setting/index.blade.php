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
                                <h5 class="mb-0">Tambah Setting Integrasi</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('integrasi-setting.store') }}">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Nama Aplikasi</label>
                                            <input type="text" name="nama_aplikasi" class="form-control"
                                                value="{{ old('nama_aplikasi') }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Client ID</label>
                                            <input type="text" name="client_id" class="form-control"
                                                value="{{ old('client_id') }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Base URL</label>
                                            <input type="url" name="base_url" class="form-control"
                                                value="{{ old('base_url') }}" placeholder="https://samsat.jatengprov.go.id" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Secret Key</label>
                                            <input type="text" name="secret_key" class="form-control" autocomplete="off"
                                                value="{{ old('secret_key') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">API Key</label>
                                            <input type="text" name="api_key" class="form-control" autocomplete="off"
                                                value="{{ old('api_key') }}">
                                        </div>
                                    </div>
                                    <button class="btn btn-primary mt-3" type="submit">Simpan</button>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Daftar Setting Integrasi</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th width="70">ID</th>
                                                <th>Nama Aplikasi</th>
                                                <th>Client ID</th>
                                                <th>Secret Key</th>
                                                <th>API Key</th>
                                                <th>Base URL</th>
                                                <th width="180">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($settings as $setting)
                                                <tr>
                                                    <td>{{ $setting->id }}</td>
                                                    <td>
                                                        <input form="update-integrasi-{{ $setting->id }}" type="text"
                                                            name="nama_aplikasi" class="form-control form-control-sm"
                                                            value="{{ $setting->nama_aplikasi }}" required>
                                                    </td>
                                                    <td>
                                                        <input form="update-integrasi-{{ $setting->id }}" type="text"
                                                            name="client_id" class="form-control form-control-sm"
                                                            value="{{ $setting->client_id }}" required>
                                                    </td>
                                                    <td>
                                                        <input form="update-integrasi-{{ $setting->id }}" type="text"
                                                            name="secret_key" class="form-control form-control-sm"
                                                            value="{{ $setting->secret_key }}" autocomplete="off">
                                                    </td>
                                                    <td>
                                                        <input form="update-integrasi-{{ $setting->id }}" type="text"
                                                            name="api_key" class="form-control form-control-sm"
                                                            value="{{ $setting->api_key }}" autocomplete="off">
                                                    </td>
                                                    <td>
                                                        <input form="update-integrasi-{{ $setting->id }}" type="url"
                                                            name="base_url" class="form-control form-control-sm"
                                                            value="{{ $setting->base_url }}" required>
                                                    </td>
                                                    <td>
                                                        <form id="update-integrasi-{{ $setting->id }}" method="POST"
                                                            action="{{ route('integrasi-setting.update', $setting->id) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="btn btn-sm btn-warning">Update</button>
                                                        </form>
                                                        <form method="POST"
                                                            action="{{ route('integrasi-setting.destroy', $setting->id) }}"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Hapus setting integrasi ini?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center">Belum ada setting integrasi.</td>
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
