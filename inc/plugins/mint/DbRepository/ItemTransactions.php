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
        'unlisted' => [
            'type' => 'bool',
            'notNull' => true,
        ],
        'token' => [
            'type' => 'varchar',
            'length' => 40,
        ],
        'balance_transfer_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_balance_transfers',
                    'column' => 'id',
                ],
            ],
            'uniqueKey' => 1,
        ],
    ];

    public function create(array $data, bool $useDbTransaction = true): ?int
    {
        if ($useDbTransaction) {
            $this->db->write_query('BEGIN');
        }

        try {
            $result = true;

            if ($data['ask_price'] < 0) {
                throw new \RuntimeException('Ask price invalid');
            }

            \mint\getItemsById(
                array_column($data['offered_items'], 'item_id'),
                true
            );

            $transaction = array_merge(
                \mint\getArraySubset($data, [
                    'ask_user_id',
                    'ask_price',
                    'unlisted',
                ]),
                [
                    'ask_date' => TIME_NOW,
                    'active' => true,
                    'completed' => false,
                ]
            );

            if ($transaction['unlisted'] == true) {
                $transaction['token'] = \random_str(14);
            }

            $transactionId = $this->insert($transaction);

            if (!$transactionId) {
                throw new \RuntimeException('Could not insert Item Transaction record');
            }

            $itemOwnershipDetails = \mint\getItemOwnershipsDetails(
                array_column($data['offered_items'], 'item_ownership_id')
            );

            if (!\mint\itemsTransferableFromUser($itemOwnershipDetails, $data['ask_user_id'], true)) {
                throw new \RuntimeException('Item Transaction Items for asking user not transferable');
            }

            foreach ($itemOwnershipDetails as $itemOwnership) {
                $result = ItemTransactionItems::with($this->db)->insert([
                    'item_transaction_id' => $transactionId,
                    'item_id' => $itemOwnership['item_id'],
                    'bid' => false,
                ]) !== false;

                if ($result == false) {
                    throw new \RuntimeException('Could not insert offered Item Transaction Item');
                }
            }

            if (!empty($data['ask_item_types'])) {
                $askItemTypes = ItemTypes::with($this->db)->getById(array_keys($data['ask_item_types']));

                foreach ($data['ask_item_types'] as $itemTypeId => $amount) {
                    if (
                        (int)$amount > 0 && (int)$amount <= 1000 &&
                        isset($askItemTypes[$itemTypeId]) &&
                        $askItemTypes[$itemTypeId]['transferable'] == 1
                    ) {
                        $result = ItemTransactionItemTypes::with($this->db)->insert([
                            'item_transaction_id' => $transactionId,
                            'item_type_id' => $itemTypeId,
                            'amount' => (int)$amount,
                        ]) !== false;

                        if ($result == false) {
                            throw new \RuntimeException('Could not insert Item Transaction Item Type');
                        }
                    } else {
                        throw new \RuntimeException('Item Transaction Item Types invalid');
                    }
                }
            }
        } catch (\RuntimeException $e) {
            $result = false;
        } finally {
            if ($useDbTransaction) {
                if ($result == true) {
                    $this->db->write_query('COMMIT');
                } else {
                    $this->db->write_query('ROLLBACK');
                }
            }
        }

        if ($result == true) {
            return $transactionId;
        } else {
            return null;
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
        if ($useDbTransaction) {
            $this->db->write_query('BEGIN');
        }

        try {
            $result = true;

            $transaction = \mint\getItemTransactionById($transactionId, true);

            if (!$transaction) {
                throw new \RuntimeException('Could not fetch Item Transaction');
            }

            if (
                $transaction['completed'] == 1 ||
                $transaction['ask_user_id'] == $bidUserId
            ) {
                throw new \RuntimeException('Item Transaction not executable');
            }

            $transactionAskItems = \mint\getTransactionAskItemsForUser($transaction['id'], $bidUserId);

            if ($transactionAskItems === null) {
                throw new \RuntimeException('Item Transaction Ask Item Types not satisfied by bidding user');
            }

            // lock, validate ask items
            \mint\getItemsById(array_column($transactionAskItems, 'item_id'), true);

            if (!\mint\itemsTransferableFromUser($transactionAskItems, $bidUserId)) {
                throw new \RuntimeException('Item Transaction Ask Items for bidding user not transferable');
            }

            foreach ($transactionAskItems as $itemOwnership) {
                $result = ItemTransactionItems::with($this->db)->insert([
                    'item_transaction_id' => $transactionId,
                    'item_id' => $itemOwnership['item_id'],
                    'bid' => true,
                ]) !== false;

                if ($result == false) {
                    throw new \RuntimeException('Could not insert bid Item Transaction Item');
                }
            }

            $transactionItems = \mint\getItemTransactionOfferedItems($transaction['id'], false);

            // lock, validate offered items
            \mint\getItemsById(array_column($transactionItems, 'item_id'), true);

            if (!\mint\itemsTransferableFromUser($transactionItems, $transaction['ask_user_id'])) {
                throw new \RuntimeException('Item Transaction Items for asking user not transferable');
            }

            // execute balance transfers
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

                if ($balanceTransferId === null) {
                    throw new \RuntimeException('Balance Transfer execution failed');
                }
            } else {
                $balanceTransferId = null;
            }

            // modify item ownerships
            $result &= ItemOwnerships::with($this->db)->remove($transactionItems, $transaction['ask_user_id']);
            $result &= ItemOwnerships::with($this->db)->assign($transactionItems, $bidUserId);

            if ($transactionAskItems) {
                $result &= ItemOwnerships::with($this->db)->remove($transactionAskItems, $bidUserId);
                $result &= ItemOwnerships::with($this->db)->assign($transactionAskItems, $transaction['ask_user_id']);
            }

            if ($result == false) {
                throw new \RuntimeException('Item ownership modification failed');
            }

            // update transaction information
            $transaction['bid_user_id'] = $bidUserId;
            $transaction['active'] = 0;
            $transaction['completed'] = 1;
            $transaction['completed_date'] = TIME_NOW;
            $transaction['balance_transfer_id'] = $balanceTransferId;

            $result &= $this->updateById($transaction['id'], $transaction);

            if ($result == false) {
                throw new \RuntimeException('Could not update Item Transaction');
            }

            $result = true;
        } catch (\RuntimeException $e) {
            $result = false;
        } finally {
            if ($useDbTransaction) {
                if ($result == true) {
                    $this->db->write_query('COMMIT');
                } else {
                    $this->db->write_query('ROLLBACK');
                }
            }
        }

        return $result;
    }
}
