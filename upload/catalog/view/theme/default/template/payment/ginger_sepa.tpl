<h2><?php echo $ginger_bank_details; ?></h2>
<p><b><?php echo $text_description; ?></b></p>

<form action=<?php echo $action; ?> method="post">
    <div class="well well-sm">
        <p><?php echo $ginger_payment_reference; ?></p>
        <p><?php echo $ginger_iban; ?></p>
        <p><?php echo $ginger_bic; ?></p>
        <p><?php echo $ginger_account_holder; ?></p>
        <p><?php echo $ginger_residence; ?></p>
    </div>

    <div class="buttons pull-right">
        <div class="right">
            <input type="submit" value="<?php echo $button_confirm; ?>" class="button btn btn-primary"/>
        </div>
    </div>
</form>