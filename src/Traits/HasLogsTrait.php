<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;

trait HasLogsTrait
{
    use LogsActivity {
        LogsActivity::shouldLogOnlyDirty as parentShouldLogOnlyDirty;
    }

    public function shouldLogOnlyDirty(): bool
    {
        return true;
    }
}