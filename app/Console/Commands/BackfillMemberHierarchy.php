<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillMemberHierarchy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'members:backfill-hierarchy {--dry-run : Report orphan counts without updating any rows}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill region_id/zone_id/stream_id on members created without hierarchy stamps (V2 create left them NULL, hiding them from Region-Lead-scoped reads)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orphans = Member::whereNull('region_id')
            ->whereNotNull('bacenta_id')
            ->count();

        $noBacenta = Member::whereNull('region_id')
            ->whereNull('bacenta_id')
            ->count();

        $this->info("Members missing region_id but having a bacenta: {$orphans}");
        $this->info("Members missing region_id with no bacenta (needs manual triage): {$noBacenta}");

        if ($this->option('dry-run')) {
            $this->info('Dry run — no rows updated.');
            return self::SUCCESS;
        }

        if ($orphans === 0) {
            $this->info('Nothing to backfill.');
            return self::SUCCESS;
        }

        $updated = DB::table('members as m')
            ->join('bacentas as b', 'b.id', '=', 'm.bacenta_id')
            ->leftJoin('regions as r', 'r.id', '=', 'b.region_id')
            ->whereNull('m.region_id')
            ->whereNotNull('m.bacenta_id')
            ->whereNull('m.deleted_at')
            ->update([
                'm.region_id' => DB::raw('b.region_id'),
                'm.zone_id' => DB::raw('b.zone_id'),
                'm.stream_id' => DB::raw('r.stream_id'),
            ]);

        $this->info("Backfilled hierarchy on {$updated} member(s).");

        return self::SUCCESS;
    }
}
