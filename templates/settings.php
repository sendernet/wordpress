<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<div class="sender-container">
    <div class="sender-flex-column">
		<?php if (!$apiKey) { ?>
            <form method="post" action='' class="sender-box required-api-key" novalidate="novalidate">
                <h2 class="sender-header">Enter your api key</h2>

                <div class="sender-subheader"> We need your API key to continue. <br>
                    <a href="#" class="sender-link">Click here</a> if you are not sure where to find it.

                </div>
                <?php if(get_option('sender_account_message')) { ?>
                    <div class="sender-is-danger sender-margin-top-10 sender-padding-10">
                        <?php echo get_option('sender_account_message') ?>
                    </div>
				<?php } ?>

                <div class="sender-flex-center-column">
                    <input name="sender_api_key" type="text" id="sender_api_key" placeholder="Paste your api key here"
                           class="sender-input sender-text-input ">
                    <input type="submit" name="submit" id="submit" class="sender-cta-button sender-input"
                           value="Begin">
                </div>
            </form>

		<?php } else {  ?>
            <div class="sender-settings-grid">
                <div class="flex-column sender-box">
                    <div class="sender-logo">
                        <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo.png'; ?>"
                             alt="Sender logo">
                    </div>
                    <div class="sender-username">
                        <h2 class="sender-header"><?php echo $user->account->title ?></h2>
                    </div>

                    <div class="flex-grow-1"></div>
                    <div class="sender-logout">
                        <div >
							<?php if ($user->account->active_plan->type === 'PAYG') {
								echo "Pay as you go plan";
							} else {
								if ($user->account->active_plan->type === 'SUBSCRIPTION') {

									echo "Monthly subscription";
								} else {
									echo "Free plan";
								}
							}
							?>
                        </div>
                        <div class="flex-grow-1"></div>
                        <form method="post" action=''>
                            <input name="sender_api_key" type="hidden" id="sender_api_key" value=""
                                   class="sender-input sender-text-input ">
                            <input type="submit" name="submit" id="submit" class="sender-cta-button"
                                   value="Change user">
                        </form>
                    </div>
                </div>
                <?php if ($wooEnabled) { ?>
                <div  style="position: relative" class="sender-plugin-settings sender-box">
                    <div class="sender-header">WooCommerce settings</div>
                    <form method="post" class="flex-column h-100" action='' id="sender-form-settings">
                        <div class="sender-options">
                            <div class="sender-option">
                                <input type="hidden" value="0" name="sender_allow_tracking_hidden_checkbox">
                                <label for="sender_allow_tracking">
                                    <input class="sender-checkbox" type="checkbox" id="sender_allow_tracking"
                                           value="sender_allow_tracking"
                                           name="sender_allow_tracking" <?php if (get_option('sender_allow_tracking')) {
										echo 'checked';
									} ?> >
                                    <span>Allow tracking</span>
                                </label>
                            </div>
                            <div class="sender-option">
                                <label class="sender-select-label" for="sender_customers_list">Customers list</label>
                                <span class="sender-select-wrap">
                                    <select form="sender-form-settings" class="sender-woo-lists" name="sender_customers_list" <?php if (!get_option('sender_allow_tracking')) {
                                        echo 'disabled';
                                    } ?> id="sender_customers_list" value="<?=get_option('sender_customers_list')?>">
                                        <option value="0">No list</option>
                                        <?php foreach ($groups as $tag): ?>
                                            <option  <?= get_option('sender_customers_list') == $tag->id ? 'selected' : '' ?>  value="<?=$tag->id?>"><?=$tag->title?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </span>
                            </div>
                            <div class="sender-option">
                                <label class="sender-select-label" for="sender_registration_list">Users who registered list</label>
                                <span class="sender-select-wrap">
                                    <select form="sender-form-settings" <?php if (!get_option('sender_allow_tracking')) {
                                        echo 'disabled';
                                        } ?> name="sender_registration_list" class="sender-woo-lists" id="sender_registration_list" value="<?=get_option('sender_registration_list')?>">
                                            <option value="0">No list</option>
                                        <?php foreach ($groups as $tag): ?>
                                            <option  <?= get_option('sender_registration_list') == $tag->id ? 'selected' : '' ?>  value="<?=$tag->id?>"><?=$tag->title?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1"></div>
                        <div class="sender-logout" style="position: absolute; bottom: 20px; right: 20px">
                            <input type="submit" name="submit" id="submit" class="sender-cta-button"
                                   value="Save">
                        </div>
                    </form>
                </div>
                <?php } ?>
            </div>
		<?php } ?>
    </div>
</div>
<style>
    .sender-is-danger {
        color: #b41d1d !important;
        border: solid 1px #b41d1d;
        background-color: #ffe9e9;
        border-radius: 5px;

    }
    .sender-margin-top-10 {
        margin-top: 10px;
    }
    .sender-padding-10 {
        padding: 10px;
    }
    .h-100 {
        height: 100%;
    }

    .sender-options {
        display: flex;
        flex-direction: column;
    }

    .sender-option {
        width: 50%;
        height: 40px;
        margin-bottom: 10px;
    }

    .sender-container {
        font-family: 'Roboto', sans-serif !important;
        font-size: 14px;
        color: #000;
        line-height: 18px;
    }

    .sender-select-label {
        display: inline-block;
        width: 160px;
    }

    .sender-logout {
        display: flex;
        justify-content: flex-end;
        align-items: center;

    }

    .mb-10 {
        margin-bottom: 10px !important;
    }

    .flex-column {
        display: flex;
        flex-direction: column;
    }

    .flex-grow-1 {
        flex-grow: 1;
    }

    .no-padding {
        padding: 0px !important;
    }
    .sender-form-stats {
        margin-left: 10px;
    }

    .sender-form-thumbnail {
        height: 100%;
        margin-right: 20px;
        width: 60px;
        border: 1px solid #ccc;
    }

    .sender-form-thumbnail img {
        height: 100%;
    }

    .sender-form-row {
        display: flex;
        height: 60px;
        border-bottom: 1px solid #ccc;
        padding: 20px;
    }


    .sender-settings-grid {
        margin: 40px;
        margin-left: 20px;
        display: grid;
        grid-template-columns: 500px auto;
        grid-template-rows: 250px auto;
        grid-row-gap: 40px;
        grid-column-gap: 40px;
    }

    .sender-forms-list {
        grid-column: 1 / span 2;
        grid-row: 2 / 2;
    }

    .sender-flex-center-column {
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 100%;
    }

    .sender-cta-button {
        background-color: #ff8d00;
        border-color: transparent;
        cursor: pointer;
        justify-content: center;
        padding: 12px 20px;
        text-align: center;
        white-space: nowrap;
        height: unset !important;
        font-weight: 700;
        border-radius: 5px !important;
        box-shadow: none !important;

        color: #fff;
        font-size: 14px;
    }

    .sender-cta-button:hover {
        background-color: #ffae00 !important;
    }

    .sender-cta-button:active {
        background-color: #e67f00 !important;
    }

    .sender-cta-button:focus {
        background-color: #e67f00 !important;
    }

    .sender-input {
        margin: 10px 0;
    }

    .sender-text-input {
        box-shadow: none;
        padding: 7px 12px;
        border-color: #ccc;
        border-radius: 5px;
        color: #000;
        font-size: 14px;
        line-height: 28px;
        color: #000;
        height: 40px;
        max-width: 100%;
    }

    .sender-text-input::placeholder {
        color: #999;
    }

    .sender-text-input:focus {
        border-color: #aaa !important;
        box-shadow: none !important;
        outline: none !important;
    }

    .required-api-key {
        max-width: 80%;
        margin: 200px auto;
        width: 543px;
        height: 300px;
        background: white;
        display: flex;
        flex-direction: column;
        padding: 35px !important;
    }

    .sender-link {
        color: #ff8d00;
    }

    .sender-header-small {
        font-size: 16px;
        color: #222;
        line-height: 18px;
    }

    .sender-subheader {
        font-size: 14px;
        color: #555;
        line-height: 22px;
    }

    .sender-header {
        font-size: 16px;
        color: #000;
        line-height: 24px;
        font-weight: 500;
        margin-bottom: 20px;
    }

    .sender-big-header {
        font-size: 32px;
        line-height: 38px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #000;
    }

    .sender-box {
        border-radius: 10px;
        box-shadow: 0 0 4px 0 rgba(0, 0, 0, .1);
        border: 1px solid #ddd;
        background: white;
        padding: 20px;
    }

    .sender-woo-lists {
        width: 292px;
        height: 40px;
        margin: 5px 0 0;
        padding: 13px 11px 12px 12px;
        border-radius: 5px !important;
        border: 1px solid #cccccc !important;
        background-color: #ffffff;
        color: #000000 !important;
    }
    .sender-select-wrap {
        position: relative;
    }
    .sender-select-wrap::after {
        height: 0 !important;
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 5px solid #000;
        top: 50%;
        right: 12px;
        content: " ";
        display: block;
        pointer-events: none;
        position: absolute;
    }

    .sender-select-wrap:focus-within::after {
        transform: rotate(180deg);
    }

    .sender-woo-lists:hover:not([disabled]) {
        color: #000000 !important;
        border-radius: 5px !important;
        border: 1px solid #aaaaaa !important;
    }
    .sender-woo-lists {
       background: none !important;
    }
    @-moz-document url-prefix(){
        .sender-woo-lists{border: 1px solid #CCC; border-radius: 4px; box-sizing: border-box; position: relative; overflow: hidden;}
        .sender-woo-lists select { width: 110%; background-position: right 30px center !important; border: none !important;}
    }
    /* For IE10 */
    select.sender-woo-lists::-ms-expand {
        display: none !important;
    }
    select.sender-woo-lists:focus {
        outline: none !important;
        box-shadow: none !important;
    }

    input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        z-index: -1;
    }

    /* Text color for the label */
    input[type="checkbox"] + span {
        cursor: pointer;
        color: black;
    }

    /* Checkbox un-checked style */
    input[type="checkbox"] + span:before {
        content: '';
        border: 1px solid #ddd;
        border-radius: 3px;
        display: inline-block;
        width: 16px;
        height: 16px;
        margin-right: 0.5em;
        margin-top: 0.5em;
        vertical-align: -2px;
    }

    /* Checked checkbox style (in this case the background is green #e7ffba, change this to change the color) */
    input[type="checkbox"]:checked + span:before {
        /* NOTE: Replace the url with a path to an SVG of a checkmark to get a checkmark icon */
        background-image: url('<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/white_check.png'; ?>');
        background-repeat: no-repeat;
        background-position: center;
        /* The size of the checkmark icon, you may/may not need this */
        background-size: 11px;
        border-radius: 5px;
        background-color: #ff8d00;
        color: white;
        border: 1px solid transparent;
    }

    /* Adding a dotted border around the active tabbed-into checkbox */
    input[type="checkbox"]:focus + span:before,
    input[type="checkbox"] + span:hover:before {
        /* Visible in the full-color space */
        box-shadow: none;
        outline: none;
    }


    /* Disabled checkbox styles */
    input[type="checkbox"]:disabled + span {
        cursor: default;
        color: black;
        opacity: 0.5;
    }

</style>

<script>

    jQuery('#sender_allow_tracking').on('change', function (ev){
        jQuery('.sender-woo-lists').prop('disabled', !jQuery(ev.currentTarget).is(':checked'))
    });


</script>