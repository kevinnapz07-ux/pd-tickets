<?php

namespace App\Notifications;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventTicketNotification extends Notification
{
    use Queueable;

    public function __construct(public Registration $registration) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->registration->loadMissing('event');
        $qrDataUri = $this->registration->qrCodeDataUri();
        $qrContents = base64_decode((string) str($qrDataUri)->after('base64,'), true);

        $message = (new MailMessage)
            ->theme('pdug')
            ->subject('Tiket Event PD Gunadarma - '.$this->registration->event->title)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Pembayaran Anda telah dikonfirmasi melalui Midtrans.')
            ->line('Tiket untuk event '.$this->registration->event->title.' sudah aktif.')
            ->line('Kode registrasi: '.$this->registration->registration_code)
            ->action('Lihat Tiket', $this->registration->verificationUrl())
            ->line('Simpan QR Code terlampir dan tunjukkan kepada panitia saat check-in.')
            ->line('Email tiket ini dikirim ke alamat email akun PD Gunadarma Anda.');

        if ($qrContents !== false) {
            $message->attachData(
                $qrContents,
                'tiket-'.$this->registration->registration_code.'.svg',
                ['mime' => 'image/svg+xml']
            );
        }

        return $message;
    }
}
