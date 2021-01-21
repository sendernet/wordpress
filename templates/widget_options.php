
<h3>Select form</h3>
<label class="display-none" for="<?php echo esc_attr($this->get_field_id( 'form' )); ?>">Select form</label>
<select class="display-none" id="<?php echo esc_attr($this->get_field_id( 'form' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'form' )); ?>" class="widefat" style="width:100%;">
	<option disabled selected>Select your form</option>
	<?php
	foreach($forms as $form) {
		?>
        <?
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
		?>
            <?
                $selected = false;
                if(isset($instance['form'])){
                    $selected = $form->settings->embed_hash == $instance['form'];
                }
            ?>
		<div class="sender-form-select <?= $selected ? 'sender-form-is-selected' : '' ?>" data-id="<?php echo esc_attr($form->settings->embed_hash).'" ';?>">
			<div class="sender-form-title">
				<?php
                    $name = esc_html($form->title);
                    if(strlen($name) > 20){
                        $name = substr($name, 0, 20) . '...';
                    }
                    echo $name;
                    ?>
			</div>
			<div class="sender-form-thumbnail">
				<img src="<?=$form->thumbnail_url ? $form->thumbnail_url : 'https://cdn.sender.net/rsz_antrinis_logotipas.png' ?>" alt="thumbnail">
			</div>
		</div>

		<?php
	}
	?>


</div>



<style>
	.sender-widget-container {
		display: flex;
		flex-wrap: wrap;
        align-items: center;
	}
	.sender-form-title {
		padding: 10px;
        font-weight: bolder;
	}
	.sender-form-is-selected {
		border: 1px solid #ff8d00 !important;
	}

	.sender-form-select {
        cursor: pointer;
		width: 125px;
		margin: 3px;
		border: 1px solid #ccc;
		border-radius: 5px;
        height: 190px;
	}
    .sender-form-thumbnail img {
		width: 100%;
	}
    .sender-form-thumbnail {
		width: 100%;
	}
    .display-none {
        display:none;
    }
</style>

<script>
    divs = document.getElementsByClassName('sender-form-select')


    for (i = 0; i < divs.length; ++i) {
        formSelect = divs[i];
        console.log(formSelect.attributes);

        formSelect.addEventListener('click', function (formSelect){
            var select = document.getElementById('<?php echo esc_attr($this->get_field_id( 'form' )); ?>')
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

