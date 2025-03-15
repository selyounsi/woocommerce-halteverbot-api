<td class="address billing-address">
    <?php if(isset($data["billing"]["company"]) && $data["billing"]["company"] !== ''): ?>
        <div class="billing-company"><?= $data["billing"]["company"]; ?></div>
    <?php endif; ?>

    <?php if(isset($data["billing"]["address_1"]) && $data["billing"]["address_1"] !== ''): ?>
        <div class="billing-address_1"><?= $data["billing"]["address_1"]; ?></div>
    <?php endif; ?>

    <?php if(isset($data["billing"]["postcode"]) && $data["billing"]["postcode"] !== ''): ?>
        <div class="billing-postcode"><?= $data["billing"]["postcode"]; ?> <?= $data["billing"]["city"]; ?></div>
    <?php endif; ?>


    <?php if(isset($data["billing"]["emailX"]) && $data["billing"]["emailX"] !== ''): ?>
        <div class="billing-emailX"><?= $data["billing"]["emailX"]; ?></div>
    <?php endif; ?>

    <?php if(isset($data["billing"]["phoneX"]) && $data["billing"]["phoneX"] !== ''): ?>
        <div class="billing-phoneX"><?= $data["billing"]["phoneX"]; ?></div>
    <?php endif; ?>
</td>