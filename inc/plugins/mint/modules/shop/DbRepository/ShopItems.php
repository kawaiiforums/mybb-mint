<?php

namespace mint\modules\shop\DbRepository;

use mint\DbRepository\BalanceTransfers;

class ShopItems extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_shop_items';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'item_type_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_item_types',
                    'column' => 'id',
                    'onDelete' => 'cascade',
                ],
            ],
        ],
        'ask_price' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'sales_limit' => [
            'type' => 'integer',
        ],
        'times_purchased' => [
            'type' => 'integer',
            'default' => '0',
            'notNull' => true,
        ],
    ];


    public function purchase(int $shopItemId, int $bidUserId, bool $useDbTransaction = true): bool
    {
        $result = true;

        if ($useDbTransaction) {
            $this->db->write_query('BEGIN');
        }

        $shopItem = \mint\modules\shop\getShopItemById($shopItemId, true);

        if (
            $shopItem && (
                $shopItem['sales_limit'] == 0 ||
                $shopItem['sales_limit'] > $shopItem['times_purchased']
            )
        ) {
            if ($shopItem['ask_price'] != 0) {
                $balanceOperationId = \mint\userBalanceOperationWithTerminationPoint(
                    $bidUserId,
                    -$shopItem['ask_price'],
                    'shop',
                    false,
                    false
                );

                $result = $balanceOperationId !== null;
            }

            if ($result == true) {
                $result = \mint\createItemsWithTerminationPoint(
                    $shopItem['item_type_id'],
                    1,
                    $bidUserId,
                    'shop'
                );

                $result &= $this->updateById($shopItemId, [
                    'times_purchased' => $shopItem['times_purchased'] + 1,
                ]);
            }
        } else {
            $result = false;
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
