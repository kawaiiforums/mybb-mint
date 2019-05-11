<?php

namespace mint\DbRepository;

class ItemTransactions extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_item_transactions';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'ask_user_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'users',
                    'column' => 'uid',
                    'noReference' => true,
                ],
            ],
            'notNull' => true,
        ],
        'bid_user_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'users',
                    'column' => 'uid',
                    'noReference' => true,
                ],
            ],
        ],
        'ask_price' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'ask_date' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'active' => [
            'type' => 'bool',
            'notNull' => true,
        ],
        'completed' => [
            'type' => 'bool',
            'notNull' => true,
        ],
        'completed_date' => [
            'type' => 'integer',
        ],
    ];

    public function execute(int $transactionId, bool $useDbTransaction = true): bool
    {
        $result = true;

        if ($useDbTransaction) {
            $this->db->write_query('BEGIN');
        }

        $transaction = \mint\getItemTransactionById($transactionId, true);

        if (
            $transaction['completed'] == 1 ||
            $transaction['ask_user_id'] == $transaction['bid_user_id']
        ) {
            $result = false;
        } else {
            $transactionItems = \mint\getItemTransactionItems($transaction['id']);

            \mint\getItemsById(array_column($transactionItems, 'item_id'), true);

            foreach ($transactionItems as $transactionItem) {
                if (
                    $transactionItem['user_id'] != $transaction['ask_user_id'] ||
                    $transactionItem['active'] == 0
                ) {
                    $result &= false;
                    break;
                }
            }

            if ($result == true) {
                $result &= BalanceTransfers::with($this->db)->execute(
                    $transaction['bid_user_id'],
                    $transaction['ask_user_id'],
                    $transaction['ask_price'],
                    [
                        'handler' => 'item_transaction',
                    ],
                    false
                );

                $result &= ItemOwnerships::with($this->db)->remove($transactionItems, $transaction['ask_user_id']);
                $result &= ItemOwnerships::with($this->db)->assign($transactionItems, $transaction['bid_user_id']);

                $transaction['active'] = 0;
                $transaction['completed'] = 1;
                $transaction['completed_date'] = TIME_NOW;

                $result &= $this->updateById($transaction['id'], $transaction);
            }
        }

        if ($useDbTransaction) {
            if ($result == true) {
                $this->db->write_query('COMMIT');
            } else {
                $this->db->write_query('ROLLBACK');
            }
        }

        return $result;
    }
}
