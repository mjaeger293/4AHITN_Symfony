<?php

namespace App;

use App\Entity\IDebugger;

class PlainTextLogger implements IDebugger
{
    public function logToScreen(string $msg): void
    {
        echo "<pre>" . $msg . "</pre>";
    }
}
