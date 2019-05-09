<?php

namespace mint\DbRepository;

class BalanceTransfers extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_balance_transfers';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'from_user_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'users',
                    'column' => 'uid',
                    'noReference' => true,
                ],
            ],
        ],
        'to_user_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'users',
                    'column' => 'uid',
                    'noReference' => true,
                ],
            ],
        ],
        'value' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'date' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'handler' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'note' => [
            'type' => 'text',
        ],
        'private' => [
            'type' => 'bool',
            'notNull' => true,
        ],
    ];

    public function execute(int $fromUserId, int $toUserId, int $value, array $details = [], bool $useDbTransaction = true): bool
    {
        if ($value < 0) {
            return false;
        }

        if ($fromUserId === $toUserId) {
            return false;
        }

        $transferData = [
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'value' => $value,
            'date' => TIME_NOW,
        ];

        if (!empty($details['handler'])) {
            $transferData['handler'] = $details['handler'];
        }

        if (!empty($details['note'])) {
            $transferData['note'] = substr($details['note'], 0, 100);
        }

        $transferData['private'] = !empty($details['private']);

        if ($useDbTransaction) {
            $this->db->write_query('BEGIN');
        }

        $transferId = $this->insert($transferData);

        $result = BalanceOperations::with($this->db)->execute($fromUserId, -$value, [
            'balance_transfer_id' => $transferId,
        ], false);
        $result &= BalanceOperations::with($this->db)->execute($toUserId, $value, [
            'balance_transfer_id' => $transferId,
        ], false);

        if ($result) {
            if ($useDbTransaction) {
                $this->db->write_query('COMMIT');
            }

            return true;
        } else {
            if ($useDbTransaction) {
                $this->db->write_query('ROLLBACK');
            }

            return false;
        }
    }
}
