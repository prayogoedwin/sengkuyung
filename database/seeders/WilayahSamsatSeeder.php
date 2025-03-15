<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class WilayahSamsatSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['id' => '01', 'nama' => 'KOTA SEMARANG I', 'kabkota' => '3374'],
            ['id' => '02', 'nama' => 'KOTA SEMARANG II', 'kabkota' => '3374'],
            ['id' => '03', 'nama' => 'KOTA SEMARANG III', 'kabkota' => '3374'],
            ['id' => '04', 'nama' => 'SALATIGA', 'kabkota' => '3373'],
            ['id' => '05', 'nama' => 'KABUPATEN SEMARANG', 'kabkota' => '3322'],
            ['id' => '06', 'nama' => 'KABUPATEN KENDAL', 'kabkota' => '3324'],
            ['id' => '07', 'nama' => 'KABUPATEN DEMAK', 'kabkota' => '3321'],
            ['id' => '08', 'nama' => 'KABUPATEN GROBOGAN', 'kabkota' => '3315'],
            ['id' => '09', 'nama' => 'KOTA SURAKARTA', 'kabkota' => '3372'],
            ['id' => '10', 'nama' => 'KABUPATEN SUKOHARJO', 'kabkota' => '3311'],
            ['id' => '11', 'nama' => 'KABUPATEN KLATEN', 'kabkota' => '3310'],
            ['id' => '12', 'nama' => 'KABUPATEN BOYOLALI', 'kabkota' => '3309'],
            ['id' => '13', 'nama' => 'KABUPATEN SRAGEN', 'kabkota' => '3314'],
            ['id' => '14', 'nama' => 'KABUPATEN KARANGANYAR', 'kabkota' => '3313'],
            ['id' => '15', 'nama' => 'KABUPATEN WONOGIRI', 'kabkota' => '3312'],
            ['id' => '16', 'nama' => 'KABUPATEN PATI', 'kabkota' => '3318'],
            ['id' => '17', 'nama' => 'KABUPATEN KUDUS', 'kabkota' => '3319'],
            ['id' => '18', 'nama' => 'KABUPATEN JEPARA', 'kabkota' => '3320'],
            ['id' => '19', 'nama' => 'KABUPATEN REMBANG', 'kabkota' => '3317'],
            ['id' => '20', 'nama' => 'KABUPATEN BLORA', 'kabkota' => '3316'],
            ['id' => '21', 'nama' => 'KOTA PEKALONGAN', 'kabkota' => '3375'],
            ['id' => '22', 'nama' => 'KABUPATEN PEKALONGAN', 'kabkota' => '3326'],
            ['id' => '23', 'nama' => 'KABUPATEN BATANG', 'kabkota' => '3325'],
            ['id' => '24', 'nama' => 'KABUPATEN PEMALANG', 'kabkota' => '3327'],
            ['id' => '25', 'nama' => 'KOTA TEGAL', 'kabkota' => '3376'],
            ['id' => '26', 'nama' => 'KABUPATEN TEGAL', 'kabkota' => '3328'],
            ['id' => '27', 'nama' => 'KABUPATEN BREBES', 'kabkota' => '3329'],
            ['id' => '28', 'nama' => 'KABUPATEN BANYUMAS', 'kabkota' => '3302'],
            ['id' => '29', 'nama' => 'KABUPATEN CILACAP', 'kabkota' => '3301'],
            ['id' => '30', 'nama' => 'KABUPATEN PURBALINGGA', 'kabkota' => '3303'],
            ['id' => '31', 'nama' => 'KABUPATEN BANJARNEGARA', 'kabkota' => '3304'],
            ['id' => '32', 'nama' => 'KOTA MAGELANG', 'kabkota' => '3371'],
            ['id' => '33', 'nama' => 'KABUPATEN MAGELANG', 'kabkota' => '3308'],
            ['id' => '34', 'nama' => 'KABUPATEN PURWOREJO', 'kabkota' => '3306'],
            ['id' => '35', 'nama' => 'KABUPATEN KEBUMEN', 'kabkota' => '3305'],
            ['id' => '36', 'nama' => 'KABUPATEN TEMANGGUNG', 'kabkota' => '3323'],
            ['id' => '37', 'nama' => 'KABUPATEN WONOSOBO', 'kabkota' => '3307'], 
        ];

        DB::table('wilayah_samsat')->insert($data);
    }
}

