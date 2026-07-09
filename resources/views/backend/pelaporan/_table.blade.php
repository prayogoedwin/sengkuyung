@if (!empty($pelaporanTable))
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $pelaporanTable['title'] ?? 'Data Pelaporan' }}</h5>
                </div>
                <div class="card-body">
                    @if (empty($pelaporanTable['rows']))
                        <div class="alert alert-info mb-0">Data tidak ditemukan.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm mb-0">
                                <thead>
                                    @if (($pelaporanTable['type'] ?? '') === 'rekap')
                                        @foreach ($pelaporanTable['headers'] as $headerRow)
                                            <tr>
                                                @foreach ($headerRow as $header)
                                                    <th>{{ $header }}</th>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            @foreach ($pelaporanTable['headers'] as $header)
                                                <th>{{ $header }}</th>
                                            @endforeach
                                        </tr>
                                    @endif
                                </thead>
                                <tbody>
                                    @foreach ($pelaporanTable['rows'] as $row)
                                        <tr>
                                            @foreach ($row as $cell)
                                                <td>{{ is_numeric($cell) ? number_format((float) $cell, 0, ',', '.') : $cell }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
