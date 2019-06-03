<?php

namespace mint\modules\shop\DbRepository;

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


    public function purchase(int $shopItemId, int $amount, int $bidUserId, bool $useDbTransaction = true): bool
    {
        $result = true;

        if ($amount > 0) {
            if ($useDbTransaction) {
                $this->db->write_query('BEGIN');
            }

            $shopItem = \mint\modules\shop\getShopItemById($shopItemId, true);

            if (
                $shopItem && (
                    $shopItem['sales_limit'] == 0 ||
                    (
                        $shopItem['sales_limit'] > $shopItem['times_purchased'] &&
                        ($shopItem['sales_limit'] - $shopItem['times_purchased']) >= $amount
                    )
                )
            ) {
                if ($shopItem['ask_price'] != 0) {
                    $value = -($amount * $shopItem['ask_price']);

                    $balanceOperationResult = \mint\userBalanceOperationWithTerminationPoint(
                        $bidUserId,
                        $value,
                        'shop',
                        false,
                        false
                    );

                    $result = $balanceOperationResult !== false;
                }

                if ($result == true) {
                    $result = \mint\createItemsWithTerminationPoint(
                        $shopItem['item_type_id'],
                        $amount,
                        $bidUserId,
                        'shop'
                    );

                    $result &= $this->updateById($shopItemId, [
                        'times_purchased' => $shopItem['times_purchased'] + $amount,
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
        } else {
            $result = false;
        }

        return $result;
    }
}
