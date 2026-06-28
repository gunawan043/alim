<?php

namespace App\Events;

use App\Models\GtkAnalysisRun;
use App\Models\GtkGapSummary;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GtkAnalysisCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GtkAnalysisRun $run,
        public ?GtkGapSummary $summary,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('gtk-analysis')];
    }

    public function broadcastAs(): string
    {
        return 'analysis.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'run_id' => $this->run->id,
            'status' => $this->run->status,
            'academic_year_id' => $this->run->academic_year_id,
            'summary' => $this->summary?->only([
                'total_teachers', 'overloaded_teachers', 'underloaded_teachers',
                'covered_subjects', 'partial_subjects', 'uncovered_subjects',
                'total_deficit_hours', 'total_surplus_hours',
            ]),
        ];
    }
}
