<?php

namespace App\Http\Controllers;

use App\Models\PembayaranTarikLog;
use App\Models\SengBayarPajak;
use App\Services\SengBayarPajakApiImporter;
use App\Support\PembayaranTarik;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class PembayaranController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            abort_unless(
                $user && $user->hasAnyRole(['super-admin', 'superadmin', 'admin', 'adminprov'], 'web'),
                403,
                'Akses hanya untuk super admin dan admin prov.'
            );

            return $next($request);
        });
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $tanggal = (string) $request->input('tanggal', '');
            $nopol = trim((string) $request->input('nopol', ''));

            $query = SengBayarPajak::query()->orderByDesc('id');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) === 1) {
                $query->where('tgl_bayar', $tanggal);
            } else {
                $query->whereRaw('1 = 0');
            }

            if ($nopol !== '') {
                $query->where(function ($q) use ($nopol) {
                    $q->where('nopol', 'like', '%'.$nopol.'%')
                        ->orWhere('nopol_', 'like', '%'.$nopol.'%');
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('tgl_bayar_fmt', function ($row) {
                    return $row->tgl_bayar ? $row->tgl_bayar->format('Y-m-d') : '';
                })
                ->addColumn('pkb_provinsi_jalan_fmt', fn ($row) => $this->rupiah($row->pkb_provinsi_jalan))
                ->addColumn('pkb_provinsi_tunggakan_fmt', fn ($row) => $this->rupiah($row->pkb_provinsi_tunggakan))
                ->addColumn('pkb_opsen_jalan_fmt', fn ($row) => $this->rupiah($row->pkb_opsen_jalan))
                ->addColumn('pkb_opsen_tunggakan_fmt', fn ($row) => $this->rupiah($row->pkb_opsen_tunggakan))
                ->make(true);
        }

        $this->failStaleRuns();

        return view('backend.pembayaran.index', [
            'jadwal' => PembayaranTarik::settings(),
            'logs' => PembayaranTarikLog::query()->orderByDesc('id')->limit(20)->get(),
            'defaultTanggal' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('tanggal', '')) === 1
                ? (string) $request->query('tanggal')
                : now()->toDateString(),
        ]);
    }

    public function simpanJadwal(Request $request)
    {
        $validated = $request->validate([
            'aktif' => ['required', 'in:0,1'],
            'jam' => ['required', 'date_format:H:i'],
            'tanggal_sumber' => ['required', 'in:hari_ini,kemarin'],
        ]);

        $jadwal = PembayaranTarik::settings();
        $jadwal->fill([
            'aktif' => $validated['aktif'] === '1',
            'jam' => $validated['jam'],
            'tanggal_sumber' => $validated['tanggal_sumber'],
        ]);
        $jadwal->save();

        return redirect()
            ->route('pembayaran.index')
            ->with('success', 'Jadwal harian disimpan. Tarikan jalan pada jam '.$jadwal->jam.' WIB jika cron schedule:run aktif.');
    }

    public function tarik(Request $request, SengBayarPajakApiImporter $importer): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:satu,rentang'],
            'tanggal' => ['required_if:mode,satu', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'tanggal_mulai' => ['required_if:mode,rentang', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'tanggal_selesai' => ['required_if:mode,rentang', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'run_id' => ['nullable', 'integer'],
        ]);

        $this->failStaleRuns();

        $runId = isset($validated['run_id']) ? (int) $validated['run_id'] : 0;
        if ($runId > 0) {
            $log = PembayaranTarikLog::query()->where('status', 'berjalan')->find($runId);
            if ($log === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi tarikan tidak ditemukan atau sudah selesai.',
                ], 422);
            }
        } else {
            [$mulai, $selesai, $jenis, $error] = $this->resolveRange($validated);
            if ($error !== null) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }

            if (PembayaranTarikLog::query()->where('status', 'berjalan')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarikan lain masih berjalan. Tunggu sampai selesai.',
                ], 409);
            }

            $log = PembayaranTarikLog::query()->create([
                'jenis' => $jenis,
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'tanggal_proses' => $mulai,
                'halaman' => 1,
                'status' => 'berjalan',
                'created_by' => Auth::id(),
                'started_at' => now(),
                'message' => 'Memulai tarikan '.$mulai.'.',
            ]);
        }

        $owner = 'run:'.$log->id;
        if (! PembayaranTarik::acquire($owner)) {
            if ($runId === 0) {
                $log->status = 'gagal';
                $log->finished_at = now();
                $log->message = 'Tarikan lain masih berjalan.';
                $log->save();
            }

            return response()->json([
                'success' => false,
                'message' => 'Tarikan lain masih berjalan. Tunggu sampai selesai.',
            ], 409);
        }

        $tanggal = $log->tanggal_proses?->toDateString() ?? $log->tanggal_mulai->toDateString();
        $page = max(1, (int) $log->halaman);
        $userId = $log->created_by !== null ? (int) $log->created_by : null;

        try {
            $result = $importer->importPage($tanggal, $page, $userId);
        } catch (Throwable $e) {
            $log->status = 'gagal';
            $log->finished_at = now();
            $log->message = $e->getMessage();
            $log->save();
            PembayaranTarik::release($owner);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'run_id' => $log->id,
            ], 422);
        }

        $log->inserted += $result['inserted'];
        $log->skipped_duplicate += $result['skipped_duplicate'];
        $log->skipped_invalid += $result['skipped_invalid'];
        $log->total_halaman = $result['total_pages'];
        $log->message = $tanggal.' halaman '.$result['page'].'/'.$result['total_pages']
            .'. Masuk: '.$log->inserted
            .'. Duplikat: '.$log->skipped_duplicate.'.';

        $done = false;
        if ($result['done']) {
            $next = Carbon::parse($tanggal)->addDay()->toDateString();
            $akhir = $log->tanggal_selesai->toDateString();
            if ($next <= $akhir) {
                $log->tanggal_proses = $next;
                $log->halaman = 1;
            } else {
                $log->tanggal_proses = $tanggal;
                $log->halaman = $result['page'];
                $log->status = 'selesai';
                $log->finished_at = now();
                $log->message = 'Selesai '.$log->tanggal_mulai->toDateString().' s/d '.$akhir
                    .'. Masuk: '.$log->inserted
                    .'. Duplikat: '.$log->skipped_duplicate
                    .'. Tidak valid: '.$log->skipped_invalid.'.';
                $done = true;
                PembayaranTarik::release($owner);
            }
        } else {
            $log->tanggal_proses = $tanggal;
            $log->halaman = $page + 1;
        }

        $log->save();

        return response()->json([
            'success' => true,
            'done' => $done,
            'run_id' => $log->id,
            'tanggal' => $tanggal,
            'page' => $result['page'],
            'total_pages' => $result['total_pages'],
            'total_items' => $result['total_items'],
            'inserted' => $log->inserted,
            'skipped_duplicate' => $log->skipped_duplicate,
            'skipped_invalid' => $log->skipped_invalid,
            'message' => $log->message,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: string, 2: string, 3: ?string}
     */
    private function resolveRange(array $validated): array
    {
        if ($validated['mode'] === 'satu') {
            return [(string) $validated['tanggal'], (string) $validated['tanggal'], 'manual', null];
        }

        $mulai = (string) $validated['tanggal_mulai'];
        $selesai = (string) $validated['tanggal_selesai'];
        if ($selesai < $mulai) {
            return [$mulai, $selesai, 'rentang', 'Tanggal selesai harus sama atau setelah tanggal mulai.'];
        }

        $days = Carbon::parse($mulai)->startOfDay()->diff(Carbon::parse($selesai)->startOfDay())->days + 1;
        if ($days > PembayaranTarik::MAX_RANGE_DAYS) {
            return [$mulai, $selesai, 'rentang', 'Rentang maksimal '.PembayaranTarik::MAX_RANGE_DAYS.' hari.'];
        }

        return [$mulai, $selesai, 'rentang', null];
    }

    private function rupiah(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((int) $value, 0, ',', '.');
    }

    private function failStaleRuns(): void
    {
        $stale = PembayaranTarikLog::query()
            ->where('status', 'berjalan')
            ->where('updated_at', '<', now()->subMinutes(20))
            ->get();

        foreach ($stale as $log) {
            if ($log->jenis === 'background') {
                if (PembayaranTarik::pidAlive(PembayaranTarik::backgroundPid())) {
                    continue;
                }

                $log->status = 'berhenti';
                $log->finished_at = now();
                $log->message = 'Proses terminal terputus di '
                    .($log->tanggal_proses?->toDateString() ?? '-')
                    .' halaman '.$log->halaman
                    .'. Lanjutkan: php artisan pembayaran:tarik-bg --lanjut';
                $log->save();
                PembayaranTarik::release('bg:'.$log->id);

                continue;
            }

            $log->status = 'gagal';
            $log->finished_at = now();
            $log->message = 'Tarikan terputus karena tidak ada kelanjutan selama 20 menit.';
            $log->save();
            PembayaranTarik::release('run:'.$log->id);
        }
    }
}
