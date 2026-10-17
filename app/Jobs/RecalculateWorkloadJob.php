<?php

namespace App\Jobs;

use App\Services\GtkAnalysisEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateWorkloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array{
     *   school_id?: ?string,
     *   academic_year_id?: ?string,
     *   scope?: string,
     *   trigger_source?: string,
     *   trigger_ref_id?: string,
     *   context?: array,
     * }
     */
    public function __construct(
        public array $options = []
    ) {}

    public function handle(GtkAnalysisEngine $engine): void
    {
        $triggerSource = $this->options['trigger_source'] ?? 'queue_job';
        $this->options['trigger_source'] = $triggerSource;

        $engine->run($this->options);
    }
}
