@extends('backend.template.backend')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
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
                                <h5 class="mb-0">Test Integrasi Pembayaran</h5>
                            </div>
                            <div class="card-body">
                                @if ($settings->isEmpty())
                                    <p class="mb-0">Belum ada setting integrasi.
                                        <a href="{{ route('integrasi-setting.index') }}">Tambah di Setting Integrasi</a>.
                                    </p>
                                @else
                                    <form method="POST" action="{{ route('integrasi-pembayaran.hit') }}">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Aplikasi</label>
                                                <select name="integrasi_id" class="form-select" required>
                                                    @foreach ($settings as $setting)
                                                        <option value="{{ $setting->id }}"
                                                            {{ (string) old('integrasi_id', $selectedId ?? $settings->first()->id) === (string) $setting->id ? 'selected' : '' }}>
                                                            {{ $setting->nama_aplikasi }} ({{ $setting->client_id }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Tanggal Penetapan</label>
                                                <input type="date" name="tanggal_penetapan" class="form-control"
                                                    value="{{ old('tanggal_penetapan', $tanggalPenetapan ?? '2026-07-07') }}" required>
                                            </div>
                                        </div>
                                        <button class="btn btn-primary mt-3" type="submit">Hit API</button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        @if ($result)
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Respons API</h5>
                                    @if ($result['status'])
                                        <span class="badge {{ $result['status'] >= 200 && $result['status'] < 300 ? 'bg-success' : 'bg-danger' }}">
                                            HTTP {{ $result['status'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>{{ $result['nama_aplikasi'] }}</strong></p>
                                    <p class="mb-1 text-break"><code>{{ $result['url'] }}</code></p>
                                    @if ($result['timestamp'])
                                        <p class="mb-3 text-muted">
                                            x-client-id: {{ $result['client_id'] }}
                                            · x-timestamp: {{ $result['timestamp'] }}
                                            · x-signature: {{ $result['signature'] }}
                                        </p>
                                    @endif

                                    @if ($result['error'])
                                        <div class="alert alert-danger mb-0">{{ $result['error'] }}</div>
                                    @else
                                        <pre class="mb-0" style="max-height: 70vh; overflow: auto; white-space: pre-wrap;">{{ $result['body'] }}</pre>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
@endsection
