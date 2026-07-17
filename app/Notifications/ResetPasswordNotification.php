<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $expiration = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->theme('pdug')
            ->subject('Reset Password Akun PD Gunadarma')
            ->greeting('Halo,')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun PD Gunadarma yang menggunakan alamat email ini.')
            ->line('Klik tombol di bawah untuk membuat password baru.')
            ->action('Atur Ulang Password', $url)
            ->line('Link ini hanya berlaku selama '.$expiration.' menit.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini. Password tidak akan berubah apabila tombol tidak digunakan.')
            ->salutation("Salam,\nTim PD Gunadarma\nWebsite Registrasi Event");
    }
}
