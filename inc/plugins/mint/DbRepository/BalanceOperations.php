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
            'notNull' => true,
        ],
        'result_balance' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'value' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'date' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'balance_transfer_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_balance_transfers',
                    'column' => 'id',
                ],
            ],
        ],
        'currency_termination_point_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_currency_termination_points',
                    'column' => 'id',
                ],
            ],
        ],
    ];

    public function execute(int $userId, int $value, array $details = [], bool $useDbTransaction = true, $allowOverdraft = false): bool
    {
        $operationData = [
            'user_id' => $userId,
            'value' => $value,
            'date' => TIME_NOW,
        ];

        if (!empty($details['balance_transfer_id'])) {
            $operationData['balance_transfer_id'] = $details['balance_transfer_id'];
        }

        if (!empty($details['currency_termination_point_id'])) {
            $operationData['currency_termination_point_id'] = $details['currency_termination_point_id'];
        }

        if ($useDbTransaction) {
            $this->db->write_query('BEGIN');
        }

        $currentBalance = \mint\getUserBalance($userId, true);

        $operationData['result_balance'] = $currentBalance + $value;

        if ($currentBalance !== null) {
            if ($allowOverdraft || $currentBalance + $value >= 0) {
                $result = (bool)$this->insert($operationData);

                $result &= \mint\updateUser($userId, [
                    'mint_balance' => (int)$operationData['result_balance'],
                ]);

                if ($result) {
                    if ($useDbTransaction) {
                        $this->db->write_query('COMMIT');
                    }

                    return true;
                }
            }
        }

        if ($useDbTransaction) {
            $this->db->write_query('ROLLBACK');
        }

        return false;
    }
}
