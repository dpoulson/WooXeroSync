<?php

return [
    'log_retention_days' => env('SYNC_RUN_LOG_DAYS', 30),
    'order_sync_days' => env('ORDER_SYNC_DAYS', 1),
];