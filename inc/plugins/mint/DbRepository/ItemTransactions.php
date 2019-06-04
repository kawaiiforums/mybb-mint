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
        'balance_transfer_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'balance_transfers',
                    'column' => 'id',
                ],
            ],
            'uniqueKey' => 1,
        ],
    ];

    public function create(array $data, array $items, bool $useDbTransaction = true): ?int
    {
        if ($data['ask_price'] >= 0) {
            if ($useDbTransaction) {
                $this->db->write_query('BEGIN');
            }

            \mint\getItemsById(
                array_column($items, 'item_id'),
                true
            );

            $transaction = array_merge($data, [
                'ask_date' => TIME_NOW,
                'active' => true,
                'completed' => false,
            ]);

            $transactionId = $this->insert($transaction);

            $result = (bool)$transactionId;

            $itemOwnershipDetails = \mint\getItemOwnershipsDetails(
                array_column($items, 'item_ownership_id')
            );

            foreach ($itemOwnershipDetails as $itemOwnership) {
                if (
                    $itemOwnership['user_id'] == $data['ask_user_id'] &&
                    $itemOwnership['item_ownership_active'] == 1 &&
                    $itemOwnership['item_active'] == 1 &&
                    $itemOwnership['item_type_transferable'] == 1 &&
                    !$itemOwnership['item_transaction_id']
                ) {
                    $result &= ItemTransactionItems::with($this->db)->insert([
                            'item_transaction_id' => $transactionId,
                            'item_id' => $itemOwnership['item_id'],
                        ]) !== false;
                } else {
                    $result = false;
                }
            }

            if ($useDbTransaction) {
                if ($result == true) {
                    $this->db->write_query('COMMIT');
                } else {
                    $this->db->write_query('ROLLBACK');
                }
            }

            if ($result == true) {
                return $transactionId;
            } else {
                return null;
            }
        } else {
            return false;
        }
    }

    public function cancel(int $transactionId, bool $useDbTransaction = true): bool
    {
        $result = true;

        if ($useDbTransaction) {
            $this->db->write_query('BEGIN');
        }

        $transaction = \mint\getItemTransactionById($transactionId, true);

        if (
            $transaction['completed'] == 1 ||
            $transaction['active'] == 0
        ) {
            $result = false;
        } else {
            $result &= $this->deleteById($transactionId);
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

    public function execute(int $transactionId, int $bidUserId, bool $useDbTransaction = true): bool
    {
        $result = true;

        if ($useDbTransaction) {
            $this->db->write_query('BEGIN');
        }

        $transaction = \mint\getItemTransactionById($transactionId, true);

        if (
            $transaction['completed'] == 1 ||
            $transaction['ask_user_id'] == $bidUserId
        ) {
            $result = false;
        } else {
            $transactionItems = \mint\getItemTransactionItems($transaction['id']);

            \mint\getItemsById(array_column($transactionItems, 'item_id'), true);

            foreach ($transactionItems as $transactionItem) {
                if (
                    $transactionItem['user_id'] != $transaction['ask_user_id'] ||
                    $transactionItem['item_ownership_active'] == 0 ||
                    $transactionItem['item_active'] == 0
                ) {
                    $result &= false;
                    break;
                }
            }

            if ($result == true) {
                if ($transaction['ask_price'] != 0) {
                    $balanceTransferId = BalanceTransfers::with($this->db)->execute(
                        $bidUserId,
                        $transaction['ask_user_id'],
                        $transaction['ask_price'],
                        [
                            'handler' => 'item_transaction',
                        ],
                        false
                    );

                    $result = $balanceTransferId !== null;
                } else {
                    $balanceTransferId = null;
                }
            }

            if ($result == true) {
                $result &= ItemOwnerships::with($this->db)->remove($transactionItems, $transaction['ask_user_id']);
                $result &= ItemOwnerships::with($this->db)->assign($transactionItems, $bidUserId);

                $transaction['bid_user_id'] = $bidUserId;
                $transaction['active'] = 0;
                $transaction['completed'] = 1;
                $transaction['completed_date'] = TIME_NOW;
                $transaction['balance_transfer_id'] = $balanceTransferId;

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
