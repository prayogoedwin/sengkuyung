<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request; 
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;  

class Helper
{
    // public static function encodeId($id)
    // {
    //     return base64_encode($id);
    // }

    // public static function decodeId($encodedId)
    // {
    //     return base64_decode($encodedId);
    // }

    private static $salt = 'P3Z0Q'; // Ganti dengan salt yang aman
    public static function encodeId($id)
    {
        $idWithSalt = $id . self::$salt; // Tambahkan salt
        return base64_encode($idWithSalt);
    }

    public static function decodeId($encodedId)
    {
        $decoded = base64_decode($encodedId);
        return str_replace(self::$salt, '', $decoded); // Hapus salt
    }


        public static function fungsi_email($email, $subjek, $text)
    {
        try {
            Mail::send([], [], function ($message) use ($email, $subjek, $text) {
                $message->to($email)
                    ->subject($subjek)
                    ->html($text);
            });

            Log::info("Email berhasil dikirim ke: {$email}");
            return true;
        } catch (\Exception $e) {
            Log::error("Gagal mengirim email ke {$email}: " . $e->getMessage());
            return false;
        }
    }

    public static function fungsi_wa($no_wa, $subjek, $text)
    {
        try {
            $token = env('WA_TOKEN', 'VMoffahoDaBaO6DNvn4biBwIjSKtIHlvUUUR1TAYKMeQmz48E9');
            $url = 'http://nusagateway.com/api/send-message.php';
            
            // Format pesan dengan subjek jika ada
            $pesan = $subjek ? "*{$subjek}*\n\n{$text}" : $text;
            
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, array(
                'token'   => $token,
                'phone'   => $no_wa,
                'message' => $pesan,
            ));
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            // Log response untuk debugging
            if ($httpCode == 200 && empty($curlError)) {
                Log::info("WhatsApp berhasil dikirim ke: {$no_wa}", [
                    'response' => $response,
                    'http_code' => $httpCode
                ]);
                return true;
            } else {
                Log::error("Gagal mengirim WhatsApp ke {$no_wa}", [
                    'response' => $response,
                    'http_code' => $httpCode,
                    'error' => $curlError
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error("Exception saat mengirim WhatsApp ke {$no_wa}: " . $e->getMessage());
            return false;
        }
    }

    public static function generate_otp($length = 6)
    {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    public static function format_nomor_wa($no_wa)
    {
        // Hapus karakter non-digit
        $no_wa = preg_replace('/[^0-9]/', '', $no_wa);
        
        // Jika diawali 0, ganti dengan 62
        if (substr($no_wa, 0, 1) === '0') {
            $no_wa = '62' . substr($no_wa, 1);
        }
        
        // Jika belum diawali 62, tambahkan
        if (substr($no_wa, 0, 2) !== '62') {
            $no_wa = '62' . $no_wa;
        }
        
        return $no_wa;
    }
    
    
    /**
     * Log Activity Manual
     * 
     * @param Request $request
     * @param string $idKode - ID yang sudah di-encode
     * @param string $method - POST, PUT, DELETE
     * @param array $response - Response data
     * @param string $origin - 'api' atau 'web'
     * @return ActivityLog
     */
    public static function logActivity(Request $request, string $idKode, string $method, array $response, string $origin = 'api')
    {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'id_kode' => $idKode,
            'url' => $request->fullUrl(),
            'method' => $method,
            'request_data' => $request->all(),
            'response_data' => $response,
            'ip_address' => $request->ip(),
            'origin' => $origin,
        ]);
    }
    //  Helper::logActivity($request, Helper::encodeId($data->id), 'POST', $response);

    /**
     * Log Activity dengan custom request data (untuk exclude sensitive data)
     */
    public static function logActivityCustom(
        Request $request, 
        string $idKode, 
        string $method, 
        array $requestData,
        array $response, 
        string $origin = 'api'
    ) {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'id_kode' => $idKode,
            'url' => $request->fullUrl(),
            'method' => $method,
            'request_data' => $requestData,
            'response_data' => $response,
            'ip_address' => $request->ip(),
            'origin' => $origin,
        ]);
    }

    // $safeRequestData = $request->except(['password', 'password_confirmation', '_token']);
    // Helper::logActivityCustom($request, $id, 'POST', $safeRequestData, $response);

}




