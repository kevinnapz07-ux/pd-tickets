<?php

namespace App\Notifications;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prepared for use after a payment has been confirmed as paid/settlement.
 * Dispatching remains controlled by the existing Midtrans flow.
 */
class PaymentSucceededNotification extends Notification
{
    use Queueable;

    public function __construct(public Registration $registration) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->registration->loadMissing(['event', 'payment']);
        $event = $this->registration->event;
        $payment = $this->registration->payment;

        return (new MailMessage)
            ->theme('pdug')
            ->subject('Pembayaran Berhasil - '.$event->title)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Pembayaran Anda untuk '.$event->title.' telah berhasil dikonfirmasi.')
            ->line('Order ID: '.($payment?->order_id ?? '-'))
            ->line('Nominal pembayaran: Rp '.number_format((int) ($payment?->amount ?? 0), 0, ',', '.'))
            ->line('Metode pembayaran: '.($payment?->payment_type ?? '-'))
            ->line('Status pembayaran: Berhasil')
            ->action('Lihat Tiket', $this->registration->verificationUrl())
            ->line('Tiket dan QR Code hanya dapat digunakan setelah pembayaran berhasil.');
    }
}
