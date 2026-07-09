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

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Status Maintenance</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">
                                    Status saat ini:
                                    <span class="badge {{ $status->maintenance ? 'bg-danger' : 'bg-success' }}">
                                        {{ $status->maintenance ? 'MAINTENANCE AKTIF' : 'NORMAL' }}
                                    </span>
                                </p>
                                <p class="mb-4 text-muted">
                                    Redis key <code>{{ $redisKey }}</code>:
                                    <strong>{{ $redisActive ? 'aktif (1)' : 'tidak ada' }}</strong>
                                </p>

                                <form method="POST" action="{{ route('maintenance-status.update') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="maintenance" class="form-label">Ubah status maintenance</label>
                                        <select id="maintenance" name="maintenance" class="form-select" required>
                                            <option value="0" {{ !$status->maintenance ? 'selected' : '' }}>Nonaktif (0)</option>
                                            <option value="1" {{ $status->maintenance ? 'selected' : '' }}>Aktif (1)</option>
                                        </select>
                                    </div>
                                    <button class="btn btn-primary" type="submit">
                                        Simpan Status
                                    </button>
                                </form>
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
