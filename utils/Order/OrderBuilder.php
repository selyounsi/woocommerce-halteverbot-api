<?php

namespace Utils\Order;

/**
 * Class OrderBuilder
 * 
 * @package Utils\Order
 * @uses \WC_Order         Diese Klasse wird verwendet, um auf die Bestellobjekte von WooCommerce zuzugreifen und diese zu bearbeiten.
 * @uses \WC_Order_Item_Product  Diese Klasse wird verwendet, um Produktpositionen in der Bestellung zu erstellen und deren Metadaten zu setzen.
 */
class OrderBuilder
{
    private $order;
    private $order_data;

    public function __construct($order_data)
    {
        $this->order_data = $order_data;
    
        // Prüfe, ob $order_data ein WC_Order-Objekt ist
        if ($order_data instanceof \WC_Order) {
            $this->order = $order_data;
        } elseif (isset($order_data['id']) && is_numeric($order_data['id'])) {
            // Wenn eine ID vorhanden ist, hole die Bestellung über die ID
            $this->order = wc_get_order((int)$order_data['id']);
        } else {
            // Andernfalls ein neues temporäres WC_Order-Objekt erstellen
            $this->order = new \WC_Order();
        }
    
        // Aktualisiere oder setze die Daten der Bestellung
        if (is_array($order_data)) {
            $this->setPaymentData($order_data);
    
            if (!empty($order_data['billing'])) {
                $this->setBillingData($order_data['billing']);
            }
    
            if (!empty($order_data['line_items'])) {
                $this->setLineItems($order_data['line_items']);
            }
    
            if (!empty($order_data['meta_data'])) {
                $this->setOrderMetaData($order_data['meta_data']);
            }
    
            if (!empty($order_data['status'])) {
                $this->order->set_status($order_data['status']);
            }
    
            if (isset($order_data['total'])) {
                $this->order->set_total($order_data['total']);
            }
    
            if (!empty($order_data['customer_note'])) {
                $this->order->set_customer_note($order_data['customer_note']);
            }
        }
    }

    private function setPaymentData($order_data)
    {
        if (!empty($order_data['payment_method'])) {
            $this->order->set_payment_method($order_data['payment_method']);
        }
        if (!empty($order_data['payment_method_title'])) {
            $this->order->set_payment_method_title($order_data['payment_method_title']);
        }
    }

    private function setBillingData($billing_data)
    {
        if (isset($billing_data['country'])) {
            $this->order->set_billing_country($billing_data['country']);
        }
        if (isset($billing_data['company'])) {
            $this->order->set_billing_company($billing_data['company']);
        }
        if (isset($billing_data['first_name'])) {
            $this->order->set_billing_first_name($billing_data['first_name']);
        }
        if (isset($billing_data['last_name'])) {
            $this->order->set_billing_last_name($billing_data['last_name']);
        }
        if (isset($billing_data['address_1'])) {
            $this->order->set_billing_address_1($billing_data['address_1']);
        }
        if (isset($billing_data['city'])) {
            $this->order->set_billing_city($billing_data['city']);
        }
        if (isset($billing_data['postcode'])) {
            $this->order->set_billing_postcode($billing_data['postcode']);
        }
        if (isset($billing_data['email'])) {
            $this->order->set_billing_email($billing_data['email']);
        }
        if (isset($billing_data['phone'])) {
            $this->order->set_billing_phone($billing_data['phone']);
        }
    }

    private function setLineItems($line_items)
    {
        foreach ($line_items as $item_data) {
            $item = new \WC_Order_Item_Product();
            $item->set_product_id($item_data['product_id'] ?? 0);
            $item->set_quantity($item_data['quantity'] ?? 1);
            $item->set_subtotal($item_data['subtotal'] ?? 0);
            $item->set_total($item_data['total'] ?? 0);

            if (!empty($item_data['meta_data'])) {
                foreach ($item_data['meta_data'] as $meta) {
                    if (!empty($meta['key']) && isset($meta['value'])) {
                        $item->add_meta_data($meta['key'], $meta['value'], true);
                    }
                }
            }

            $this->order->add_item($item);
        }
    }

    private function setOrderMetaData($meta_data)
    {
        foreach ($meta_data as $meta) {
            if (!empty($meta['key']) && isset($meta['value'])) {
                $this->order->update_meta_data($meta['key'], $meta['value']);
            }
        }
    }

    /**
     * Retrieves a specific meta value from the order meta data or the wp_postmeta table.
     *
     * @param string $searchKey The key of the meta data to retrieve.
     * @param array $metaData Optional meta data array to search in.
     * @return mixed|null The meta value, or null if not found.
     */
    public function getMetaValue(string $searchKey, array $metaData = []): mixed
    {
        // Wenn kein Meta-Daten-Array übergeben wurde, nehme die Meta-Daten der Bestellung
        if (empty($metaData)) {
            $metaData = $this->order->get_meta_data();
        }

        // Suche in den übergebenen Meta-Daten
        foreach ($metaData as $meta) {
            $data = $meta->get_data();
            
            if (isset($data['key']) && $data['key'] === $searchKey) {
                return $data['value'];
            }
        }

        // Wenn keine Daten gefunden wurden, suche in der wp_postmeta-Tabelle mit der Bestell-ID
        $orderId = $this->order->get_id();
        if ($orderId) {
            $metaValue = get_post_meta($orderId, $searchKey, true);

            if (!empty($metaValue)) {
                return $metaValue;
            }
        }

        // Wenn nichts gefunden wurde, gib null zurück
        return null;
    }

    /**
     * Retrieves a specific value from the line items of the order.
     *
     * @param string $key The key of the line item data to retrieve.
     * @return mixed The value of the line item data, or an array if multiple values are found.
     */
    public function getLineItem(string $key): mixed
    {
        $values = [];
        
        foreach ($this->order->get_items() as $lineItem) {
            if (isset($lineItem[$key])) {
                $values[] = $lineItem[$key];
            }
        }

        if (count($values) === 1) {
            return $values[0];
        }

        return $values;
    }

    /**
     * Retrieves meta data for line items based on a specific meta key.
     *
     * @param string $metaKey The key of the meta data to retrieve.
     * @return mixed The meta value(s) for the line item, or an array if multiple values are found.
     */
    public function getLineItemMeta(string $metaKey): mixed
    {
        $values = [];
    
        foreach ($this->order->get_items() as $lineItem) {
    
            $metaData = $lineItem->get_meta_data();
            foreach ($metaData as $meta) {
                if (isset($meta->key) && $meta->key === $metaKey) {
                    $values[] = $meta->value;
                }
            }
        }
    
        if (count($values) === 1) {
            return $values[0];
        }

        return $values;
    }

    /**
     * Retrieves a specific billing data value based on the provided key.
     *
     * @param string $key The key of the billing data to retrieve.
     * @return mixed|null The billing data value, or null if the key doesn't exist.
     */
    public function getBillingData(string $key): mixed
    {
        $billingData = [
            'first_name' => $this->order->get_billing_first_name(),
            'last_name' => $this->order->get_billing_last_name(),
            'company' => $this->order->get_billing_company(),
            'address_1' => $this->order->get_billing_address_1(),
            'address_2' => $this->order->get_billing_address_2(),
            'city' => $this->order->get_billing_city(),
            'postcode' => $this->order->get_billing_postcode(),
            'country' => $this->order->get_billing_country(),
            'email' => $this->order->get_billing_email(),
            'phone' => $this->order->get_billing_phone(),
        ];
    
        return $billingData[$key] ?? null;  // Gibt null zurück, wenn der Key nicht existiert
    }

    public function getDocumentNote() 
    {
        if (isset($this->order_data['document_note'])) 
        {
            return $this->order_data['document_note'];
        }

        return null;
    }

    public function getCustomerNote()
    {
        if (isset($this->order_data['customer_note'])) {
            return $this->order_data['customer_note'];
        }  

        return null;
    }

    /**
     * Returns the order object.
     *
     * @return \WC_Order The order object.
     */
    public function getOrder()
    {
        return $this->order;
    }
}
