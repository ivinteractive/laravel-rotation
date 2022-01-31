<?php

namespace IvInteractive\Rotation\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use IvInteractive\Rotation\RotaterInterface;

class ReencryptionJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $columnIdentifier;
    protected $ids;

    public function __construct(string $columnIdentifier, array $ids)
    {
        $this->columnIdentifier = $columnIdentifier;
        $this->ids = $ids;
    }

    public function handle()
    {
        $rotater = app(RotaterInterface::class, ['oldKey'=>config('rotation.old_key'), 'newKey'=>config('app.key')]);
        $rotater->setColumnIdentifier($this->columnIdentifier);

        $records = app('db')->table($rotater->getTable())
                            ->select([$rotater->getPrimaryKey(), $rotater->getColumn()])
                            ->whereIn($rotater->getPrimaryKey(), $this->ids)
                            ->get();

        foreach ($records as $record) {
            $rotater->rotateRecord($record);
        }
    }
}
