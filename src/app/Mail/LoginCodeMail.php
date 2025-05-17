<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class LoginCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $expires;

    public function __construct(int $code, Carbon $expires)
    {
        $this->code = $code;
        $this->expires = $expires;
    }

    public function build()
    {
        return $this->subject('ログイン認証コードのお知らせ')
                    ->view('emails.login_code')
                    ->with([
                        'code'    => $this->code,
                        'expires' => $this->expires->format('H:i'),
                    ]);
    }
}
