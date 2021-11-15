<?php

namespace Drupal\product_importer\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\Entity\Store;
use phpDocumentor\Reflection\Types\Null_;

class OrderController {

    public function order($orderID)
    {
        // Loadovanje product varijacije
        $productVariation = ProductVariation::load(160);

        // Loadovanje ordera sa prosleđenim ID-jem
        $order = Order::load($orderID);

        // Kreiranje order item-a
        $orderItem = OrderItem::create([
          'type' => 'default',
          'purchased_entity' => $productVariation,
          'quantity' => 1
        ]);
        $orderItem->save();

        if(!$order) {
          $order = Order::create([
            'type' => 'default',
            'mail' => \Drupal::currentUser()->getEmail(),
            'uid' => \Drupal::currentUser()->id(),
            'store_id' => 1,
            'order_items' => [$orderItem]
          ]);
            $order->save();
        } else {
            $order->setItems([$orderItem]);
            $order->save();
        }

        return [
            '#markup' => 'Jedi govna!'
        ];
    }
}
