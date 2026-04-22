<?php

namespace App\Http\Controllers;

use App\Models\DataTertagih;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Yajra\DataTables\Facades\DataTables;

class DataTertagihController extends Controller
{
    private const TEMPLATE_HEADERS = [
        'no_polisi',
        'id_lokasi_samsat',
        'lokasi_layanan',
        'id_kecamatan',
        'nm_kecamatan',
        'id_kelurahan',
        'nm_kelurahan',
    ];

    private const TEMPLATE_EXAMPLE_ROWS = [
        ['H-1048-AA', '1', 'SEMARANG I', '103', 'GENUK', '103005', 'BANJARDOWO'],
        ['H-8042-UA', '1', 'SEMARANG I', '101', 'SEMARANG TENGAH', '101012', 'KARANG KIDUL'],
        ['H-7054-BA', '1', 'SEMARANG I', '104', 'SEMARANG TIMUR', '104008', 'REJOSARI'],
        ['H-2513-WP', '1', 'SEMARANG I', '104', 'SEMARANG TIMUR', '104006', 'BUGANGAN'],
        ['H-1071-SF', '1', 'SEMARANG I', '102', 'SEMARANG UTARA', '102008', 'TANJUNGMAS'],
        ['H-3322-PH', '1', 'SEMARANG I', '102', 'SEMARANG UTARA', '102004', 'PURWOSARI'],
        ['H-8455-BL', '1', 'SEMARANG I', '106', 'BANYUMANIK', '106004', 'TLGOSARI'],
        ['H-9012-KQ', '1', 'SEMARANG I', '105', 'GAJAHMUNGKUR', '105002', 'PETOMPON'],
        ['H-3345-VX', '1', 'SEMARANG I', '107', 'CANDISARI', '107006', 'KARANGANYAR GUNUNG'],
        ['H-6678-RN', '1', 'SEMARANG I', '108', 'MIJEN', '108003', 'JATIBARANG'],
    ];

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $currentYear = (int) date('Y');
            $year = $request->filled('year') ? (int) $request->year : $currentYear;

            $query = DataTertagih::query()->where('year', $year);

            if ($request->filled('is_terdata')) {
                $query->where('is_terdata', (int) $request->is_terdata);
            }

            if ($request->filled('no_polisi')) {
                $query->where('no_polisi', 'like', '%' . $request->no_polisi . '%');
            }

            $query->orderBy('id', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('status_terdata', function ($row) {
                    return (int) $row->is_terdata === 1 ? 'Terdata' : 'Belum Terdata';
                })
                ->addColumn('actions', function ($row) {
                    $toggleTo = (int) $row->is_terdata === 1 ? 0 : 1;
                    $toggleText = $toggleTo === 1 ? 'Set Terdata' : 'Set Belum';

                    // return '
                    //     <button class="btn btn-sm btn-warning" onclick="toggleTertagihStatus(' . $row->id . ', ' . $toggleTo . ')">' . $toggleText . '</button>
                    //     <button class="btn btn-sm btn-danger" onclick="deleteTertagih(' . $row->id . ')">Delete</button>
                    // ';
                    return '';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        $defaultYear = (int) date('Y');
        $years = DataTertagih::select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (!in_array($defaultYear, $years, true)) {
            array_unshift($years, $defaultYear);
        }

        $years = array_values(array_unique($years));

        return view('backend.data-tertagih.index', compact('defaultYear', 'years'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return redirect()->route('data-tertagih.index')->with('error', 'File CSV tidak dapat dibaca.');
        }

        $year = (int) $request->year;
        $userId = Auth::id();
        $now = Carbon::now();

        // Skip header row
        fgetcsv($handle, 0, ',');

        $inserted = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            // Fallback for CSV that uses semicolon delimiter.
            if (count($row) === 1 && isset($row[0]) && str_contains((string) $row[0], ';')) {
                $row = str_getcsv((string) $row[0], ';');
            }

            if (count($row) < 7) {
                continue;
            }

            if (trim((string) ($row[0] ?? '')) === '') {
                continue;
            }

            DataTertagih::create([
                'no_polisi' => trim((string) ($row[0] ?? '')),
                'id_lokasi_samsat' => trim((string) ($row[1] ?? '')),
                'lokasi_layanan' => trim((string) ($row[2] ?? '')),
                'id_kecamatan' => trim((string) ($row[3] ?? '')),
                'nm_kecamatan' => trim((string) ($row[4] ?? '')),
                'id_kelurahan' => trim((string) ($row[5] ?? '')),
                'nm_kelurahan' => trim((string) ($row[6] ?? '')),
                'is_terdata' => 0,
                'year' => $year,
                'created_at' => $now,
                'created_by' => $userId,
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);

            $inserted++;
        }

        fclose($handle);

        return redirect()
            ->route('data-tertagih.index')
            ->with('success', 'Import CSV selesai. Data masuk: ' . $inserted);
    }

    public function downloadTemplate(string $format, string $type)
    {
        $isExample = $type === 'contoh';
        $filename = $isExample
            ? 'data-tertagih-contoh-10-row.' . $format
            : 'data-tertagih-format-kosong.' . $format;

        if ($format === 'csv') {
            return $this->downloadCsvTemplate($filename, $isExample);
        }

        if ($format === 'xlsx') {
            return $this->downloadExcelTemplate($filename, $isExample);
        }

        abort(404);
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'is_terdata' => 'required|in:0,1',
        ]);

        $data = DataTertagih::findOrFail($id);
        $data->is_terdata = (int) $request->is_terdata;
        $data->updated_at = Carbon::now();
        $data->updated_by = Auth::id();
        $data->save();

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui.',
        ]);
    }

    public function destroy(int $id)
    {
        $data = DataTertagih::findOrFail($id);
        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus.',
        ]);
    }

    private function downloadCsvTemplate(string $filename, bool $isExample)
    {
        $rows = $isExample ? self::TEMPLATE_EXAMPLE_ROWS : [];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::TEMPLATE_HEADERS);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function downloadExcelTemplate(string $filename, bool $isExample)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(self::TEMPLATE_HEADERS, null, 'A1');

        if ($isExample) {
            $sheet->fromArray(self::TEMPLATE_EXAMPLE_ROWS, null, 'A2');
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
