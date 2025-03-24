<td class="address billing-address">

    <?php if($this->order->getBillingData("company")): ?>
        <div class="billing-company"><?= $this->order->getBillingData("company"); ?></div>
    <?php endif; ?>

    <?php if($this->order->getBillingData("address_1")): ?>
        <div class="billing-address_1"><?= $this->order->getBillingData("address_1"); ?></div>
    <?php endif; ?>

    <?php if($this->order->getBillingData("postcode")): ?>
        <div class="billing-postcode"><?= $this->order->getBillingData("postcode"); ?> <?= $this->order->getBillingData("city"); ?></div>
    <?php endif; ?>


    <?php if($this->order->getBillingData("emailX")): ?>
        <div class="billing-email"><?= $this->order->getBillingData("email"); ?></div>
    <?php endif; ?>

    <?php if($this->order->getBillingData("phoneX")): ?>
        <div class="billing-phone"><?= $this->order->getBillingData("phone"); ?></div>
    <?php endif; ?>
</td>