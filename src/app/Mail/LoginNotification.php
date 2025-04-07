<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $token;

    /**
     * コンストラクタ
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * メールの構成
     */
    public function build()
    {
        return $this->view('emails/login')
            ->subject('【coachtechフリマ】認証ログイン')
            ->with([
                'token' => $this->token,
            ]);
    }
}
