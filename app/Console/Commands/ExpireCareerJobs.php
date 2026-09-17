<?php

namespace App\Console\Commands;

use App\Jobs\JobWorkflow;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('career:expire-jobs')]
#[Description('Expire published career jobs whose real deadline has passed')]
class ExpireCareerJobs extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(JobWorkflow $workflow): int
    {
        $count = $workflow->expireDueJobs();
        $this->info("Expired {$count} career job(s).");

        return self::SUCCESS;
    }
}
