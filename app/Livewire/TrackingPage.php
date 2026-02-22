<?php

namespace App\Livewire;

use App\Models\Ticket;
use App\Models\TicketEvaluation;
use Livewire\Component;

class TrackingPage extends Component
{
    public string $uuid;

    public int $technician_rating = 3;

    public int $company_rating = 3;

    public ?string $comment = null;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getTicket(): ?Ticket
    {
        return Ticket::where('uuid', $this->uuid)->with([
            'visits' => fn ($q) => $q->orderBy('check_in_at', 'desc'),
            'visits.technician',
            'assignedTechnician',
            'evaluation',
        ])->first();
    }

    /**
     * حالة التذكرة المعروضة للعميل:
     * - assigned:   تم تعيين فني (اسمه + تلفونه) ولم يضغط «في الطريق» بعد.
     * - in_transit: الفني ضغط «في الطريق» ولم يسجّل «وصلت وبدء العمل» بعد.
     * - working:   الفني سجّل الوصول (GPS) ولم ينهِ الزيارة بعد.
     * - completed: تم إنهاء الزيارة.
     */
    public function getDisplayStatus(): string
    {
        $ticket = $this->getTicket();
        if (!$ticket) {
            return 'not_found';
        }
        $visit = $ticket->visits->first();
        if ($visit && $visit->check_out_at) {
            return 'completed';
        }
        if ($visit && $visit->arrived_at) {
            return 'working';
        }
        if ($visit) {
            return 'in_transit';
        }
        return 'assigned';
    }

    public function submitEvaluation(): void
    {
        $ticket = $this->getTicket();
        if (!$ticket || $ticket->evaluation) {
            return;
        }
        $latestVisit = $ticket->visits()->whereNotNull('check_out_at')->orderBy('check_out_at', 'desc')->first();
        if (!$latestVisit) {
            return;
        }
        TicketEvaluation::create([
            'ticket_id'         => $ticket->id,
            'visit_id'          => $latestVisit->id,
            'technician_rating' => $this->technician_rating,
            'company_rating'    => $this->company_rating,
            'comment'           => $this->comment,
        ]);
    }

    public function render()
    {
        return view('livewire.tracking-page')->layout('layouts.tracking');
    }
}
