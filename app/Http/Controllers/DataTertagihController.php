<?php

namespace App\Http\Controllers;

use App\Models\DataTertagih;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class DataTertagihController extends Controller
{
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

                    return '
                        <button class="btn btn-sm btn-warning" onclick="toggleTertagihStatus(' . $row->id . ', ' . $toggleTo . ')">' . $toggleText . '</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteTertagih(' . $row->id . ')">Delete</button>
                    ';
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
}
