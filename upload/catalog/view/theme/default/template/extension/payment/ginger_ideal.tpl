<form action="<?php echo $action; ?>" method="post">

    <?php echo $text_select_bank; ?>

    <select name="issuer_id">
        <?php foreach($issuers as $issuer){ ?>
        <option value="<?php echo $issuer->id(); ?>"><?php echo $issuer->name(); ?></option>
        <?php }?>
    </select>

    <div class="buttons pull-right">
        <div class="right">
            <input type="submit" value="<?php echo $button_confirm; ?>" class="button btn btn-primary"/>
        </div>
    </div>
</form>