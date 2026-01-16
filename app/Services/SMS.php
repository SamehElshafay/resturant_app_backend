<?php

use App\Models\CustomerModel\CustomerSmsLog;
use Illuminate\Support\Facades\Http;

class SMS {
    public function sendSms($mobile, $message , $user_id , $operation_type){
        $response = Http::get('http://triple-core.ps/sendbulksms.php', [
            'api_token' => '6969922aaf3e1',
            //'user_name' => 'now',
            //'user_pass' => '241939',
            'sender'    => 'NOW',
            'mobile'    => $mobile,
            'type'      => 0,
            'text'      => urlencode($message),
        ]);

        $this->store_sms_operation($user_id , $operation_type);
        return $this->mapSmsResponse(trim($response->body()));
    }

    private function mapSmsResponse(string $response): array{
        return match (true) {
            str_starts_with($response, '1001') => [
                'success' => true,
                'code'    => 1001,
                'message' => 'تم إرسال الرسالة بنجاح',
                'message_id' => str_contains($response, '_')
                    ? explode('_', $response)[1]
                    : null,
            ],

            $response == '1000' => [
                'success' => false,
                'code' => 1000,
                'message' => 'لا يوجد رصيد كافي',
            ],

            $response == '2000' => [
                'success' => false,
                'code' => 2000,
                'message' => 'خطأ في عملية التفويض',
            ],

            $response == '3000' => [
                'success' => false,
                'code' => 3000,
                'message' => 'خطأ في نوع الرسالة',
            ],

            $response == '4000' => [
                'success' => false,
                'code' => 4000,
                'message' => 'أحد المدخلات المطلوبة غير موجود',
            ],

            $response == '5000' => [
                'success' => false,
                'code' => 5000,
                'message' => 'رقم المحمول غير مدعوم',
            ],

            $response == '6000' => [
                'success' => false,
                'code' => 6000,
                'message' => 'اسم المرسل غير معرف على حسابك',
            ],

            $response == '10000' => [
                'success' => false,
                'code' => 10000,
                'message' => 'هذا الـ IP غير مفوض للإرسال من خلال هذا الحساب',
            ],

            $response == '15000' => [
                'success' => false,
                'code' => 15000,
                'message' => 'خدمة الإرسال عبر API غير مفعلة',
            ],

            default => [
                'success' => false,
                'code' => null,
                'message' => 'رد غير معروف من مزود خدمة SMS',
                'raw_response' => $response,
            ],
        };
    }

    public function checkBalance(){
        $response = Http::get('http://triple-core.ps/getbalance.php', [
            'api_token' => '6969922aaf3e1',
        ]);

        return [
            'balance' => $response->body() . ' remaining SMS'
        ] ;
    }

    public function store_sms_operation($user_id , $operation_type){
        CustomerSmsLog::create([
            'customer_id' => $user_id,
            'type' => $operation_type,
        ]);
    }

    public function verify_sms_operation($user_id){
        CustomerSmsLog::where('customer_id', $user_id)->latest()->first()?->update(['verified_at' => now()]);
    }
}