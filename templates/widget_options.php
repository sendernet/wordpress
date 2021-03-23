<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<h3>Select form</h3>
<label class="sender-d-none" for="<?php echo esc_attr($this->get_field_id( 'form' )); ?>">Select form</label>
<select class="sender-d-none sender-invisible-form-select" id="<?php echo esc_attr($this->get_field_id( 'form' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'form' )); ?>" class="widefat" style="width:100%;">
	<option disabled selected>Select your form</option>
	<?php
	foreach($forms as $form) {
        $selected = false;
        if(isset($instance['form'])){
            $selected = $form->settings->embed_hash == $instance['form'];
        }
        ?>
		<option <?php echo 'value="'.esc_attr($form->settings->embed_hash).'" '; if ($selected) echo 'selected = "selected"' ; ?>><?php echo esc_html($form->title); ?></option>
		<?php
	}
	?>
</select>
<div class="sender-widget-container">

	<?php
	foreach($forms as $form) {
                $selected = false;
                if(isset($instance['form'])){
                    $selected = $form->settings->embed_hash == $instance['form'];
                }
            ?>
		<div class="sender-form-select <?= $selected ? 'sender-form-is-selected' : '' ?>" data-id="<?php echo esc_attr($form->settings->embed_hash).'" ';?>">
			<div class="sender-form-title">
                <span>
				<?php
                    $name = esc_html($form->title);
                    if(strlen($name) > 20){
                        $name = substr($name, 0, 20) . '...';
                    }
                    echo $name;
                    ?>
                </span>
			</div>
			<div class="sender-form-thumbnail" style="background-image: url('<?=$form->thumbnail_url ? $form->thumbnail_url : 'https://cdn.sender.net/rsz_antrinis_logotipas.png' ?>') ">
			</div>
		</div>

		<?php
	}
	?>


</div>

<script>
    divs = document.getElementsByClassName('sender-form-select')


    for (i = 0; i < divs.length; ++i) {
        formSelect = divs[i];

        formSelect.addEventListener('click', function (formSelect){
            var select = document.querySelector('.sender-invisible-form-select');
            jQuery('.sender-invisible-form-select option[value="' + this.getAttribute('data-id') + '"]').prop('selected', true);
            jQuery('.sender-invisible-form-select').trigger('change');
            select.value = this.getAttribute('data-id');
            var event = document.createEvent('HTMLEvents');
            event.initEvent('change', true, false);
            select.dispatchEvent(event);

            for (i = 0; i < divs.length; ++i) {
                formSelect = divs[i];
                formSelect.classList.remove('sender-form-is-selected')
            }
            this.classList.add('sender-form-is-selected')
        })
    }

</script>

