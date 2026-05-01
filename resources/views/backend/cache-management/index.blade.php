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

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
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
                                <h5 class="mb-0">Kelola Cache API</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">Hapus cache per grup endpoint API.</p>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($cacheGroups as $prefix => $label)
                                        <form action="{{ route('cache-management.clear-group') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="prefix" value="{{ $prefix }}">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                Hapus Grup {{ $label }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Daftar Cache Key Tersimpan</h5>
                            </div>
                            <div class="card-body">
                                @if (empty($trackedKeys))
                                    <div class="alert alert-info mb-0">
                                        Belum ada cache key API yang tercatat.
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('cache-management.clear-selected') }}">
                                        @csrf

                                        <div class="mb-3">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                Hapus Cache Terpilih
                                            </button>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped mb-0">
                                                <thead>
                                                    <tr>
                                                        <th width="50">Pilih</th>
                                                        <th>Cache Key</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($trackedKeys as $key)
                                                        <tr>
                                                            <td class="text-center">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="keys[]" value="{{ $key }}">
                                                            </td>
                                                            <td><code>{{ $key }}</code></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </form>
                                @endif
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
