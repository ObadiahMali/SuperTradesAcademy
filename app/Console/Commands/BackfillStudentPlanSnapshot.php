<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;

class BackfillStudentPlanSnapshot extends Command
{
    protected $signature = 'students:backfill-plan-snapshot {--force : overwrite existing values}';
    protected $description = 'Backfill students.course_fee and currency from config/pricing.plans where plan_key exists';

    public function handle()
    {
        $force = $this->option('force');
        $this->info('Starting backfill of students from pricing config...');

        $students = Student::whereNotNull('plan_key')->get();
        $bar = $this->output->createProgressBar($students->count());
        $bar->start();

        foreach ($students as $student) {
            $planKey = $student->plan_key;
            $plan = config("pricing.plans.{$planKey}");

            if (!$plan) {
                // skip if plan not found
                $bar->advance();
                continue;
            }

            if (!$force) {
                // skip students who already have values
                if (!is_null($student->course_fee) && !is_null($student->currency)) {
                    $bar->advance();
                    continue;
                }
            }

            $student->course_fee = $plan['price'];
            $student->currency = $plan['currency'] ?? 'UGX';
            $student->save();

            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->info('Backfill completed.');
        return 0;
    }
}