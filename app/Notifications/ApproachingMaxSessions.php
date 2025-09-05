<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ApproachingMaxSessions extends Notification implements ShouldQueue
{
    use Queueable;

    protected Student $student;
    protected int     $count;
    protected int     $max;

    public function __construct(Student $student, int $count, int $max)
    {
        $this->student = $student;
        $this->count   = $count;
        $this->max     = $max;
    }

    public function via($notifiable): array
    {
        // Use multiple channels; Telegram requires external package if added.
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Session Limit Approaching')
            ->line("Your child {$this->student->full_name} has completed {$this->count}/{$this->max} sessions this month.")
            ->line("Only ".($this->max - $this->count)." sessions remain.")
            ->action('View Sessions', route('filament.resources.students.view', $this->student->id));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'student_id' => $this->student->id,
            'completed'  => $this->count,
            'max'        => $this->max,
        ];
    }
}
