<?php

namespace mint\DbRepository;

class BalanceOperations extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_balance_operations';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'user_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'users',
                    'column' => 'uid',
                    'noReference' => true,
                ],
            ],
        ],
        'result_balance' => [
            'type' => 'integer',
        ],
        'value' => [
            'type' => 'integer',
        ],
        'date' => [
            'type' => 'integer',
        ],
        'transfer_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_balance_transfers',
                    'column' => 'id',
                ],
            ],
        ],
        'termination_point_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_termination_points',
                    'column' => 'id',
                ],
            ],
        ],
    ];

    public function execute(int $userId, int $value, array $details = [], bool $useTransaction = true, $allowOverdraft = false): bool
    {
        $operationData = [
            'user_id' => $userId,
            'value' => $value,
            'date' => TIME_NOW,
        ];

        if (!empty($details['transfer_id'])) {
            $operationData['transfer_id'] = $details['transfer_id'];
        }

        if (!empty($details['termination_point_id'])) {
            $operationData['termination_point_id'] = $details['termination_point_id'];
        }

        if ($useTransaction) {
            $this->db->write_query('BEGIN');
        }

        $currentBalance = \mint\getUserBalance($userId, true);

        $operationData['result_balance'] = $currentBalance + $value;

        if ($currentBalance !== null) {
            if ($allowOverdraft || $currentBalance + $value >= 0) {
                $result = (bool)$this->insert($operationData);

                $result &= (bool)$this->db->update_query('users', [
                    'mint_balance' => (int)$operationData['result_balance'],
                ], 'uid = ' . (int)$userId);

                if ($result) {
                    if ($useTransaction) {
                        $this->db->write_query('COMMIT');
                    }

                    return true;
                }
            }
        }

        if ($useTransaction) {
            $this->db->write_query('ROLLBACK');
        }

        return false;
    }
}
