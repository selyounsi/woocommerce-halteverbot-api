<td class="address billing-address">
    <?php if($order->getBillingData("company")): ?>
        <div class="billing-company"><?= $order->getBillingData("company"); ?></div>
    <?php endif; ?>

    <?php if($order->getBillingData("address_1")): ?>
        <div class="billing-address_1"><?= $order->getBillingData("address_1"); ?></div>
    <?php endif; ?>

    <?php if($order->getBillingData("postcode")): ?>
        <div class="billing-postcode"><?= $order->getBillingData("postcode"); ?> <?= $order->getBillingData("city"); ?></div>
    <?php endif; ?>


    <?php if($order->getBillingData("emailX")): ?>
        <div class="billing-email"><?= $order->getBillingData("email"); ?></div>
    <?php endif; ?>

    <?php if($order->getBillingData("phoneX")): ?>
        <div class="billing-phone"><?= $order->getBillingData("phone"); ?></div>
    <?php endif; ?>
</td>