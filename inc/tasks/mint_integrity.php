<?php

function task_mint_integrity(array $task): void
{
    global $lang;

    $lang->load('mint');

    if (function_exists(('\mint\verifyBalanceOperationsDataIntegrity'))) {
        \mint\verifyBalanceOperationsDataIntegrity();

        add_task_log($task, $lang->mint_synchronize_task_ran);
    }
}
