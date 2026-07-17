<?php

namespace App\Notifications;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prepared for use when event-registration confirmation email is enabled.
 * This notification is intentionally not dispatched by the registration flow yet.
 */
class EventRegistrationNotification extends Notification
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
        $event = $this->registration->event;

        return (new MailMessage)
            ->theme('pdug')
            ->subject('Registrasi Event Berhasil - '.$event->title)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Registrasi Anda untuk '.$event->title.' telah diterima.')
            ->line('Nomor registrasi: '.$this->registration->registration_code)
            ->line('Jadwal: '.$event->starts_at?->translatedFormat('d F Y, H:i').' WIB')
            ->line('Lokasi: '.$event->location)
            ->action('Lihat Detail Registrasi', route('registrations.show', $this->registration))
            ->line('Simpan nomor registrasi ini sebagai referensi Anda.');
    }
}
