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
        ],
        'date' => [
            'type' => 'integer',
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
        ],
    ];

    public function execute(int $fromUserId, int $toUserId, int $value, array $details = []): bool
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

        $this->db->write_query('BEGIN');

        $transferId = $this->insert($transferData);

        $result = BalanceOperations::with($this->db)->execute($fromUserId, -$value, [
            'transfer_id' => $transferId,
        ], false);
        $result &= BalanceOperations::with($this->db)->execute($toUserId, $value, [
            'transfer_id' => $transferId,
        ], false);

        if ($result) {
            $this->db->write_query('COMMIT');

            return true;
        } else {
            $this->db->write_query('ROLLBACK');

            return false;
        }
    }
}
