<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<div class="sender-container">
    <div class="sender-flex-column <?php if(!$apiKey) { echo 'is-single'; } ?>">
		<?php if (!$apiKey) { ?>
            <form method="post" action='' class="sender-box required-api-key" novalidate="novalidate">
                <div class="sender-login-image">
                    <img src="/wp-content/plugins/sender/assets/images/logo.svg" class="sender-logo d-block" alt="Sender logo">
                </div>

                <div class="sender-flex-center-column">

                <?php if(get_option('sender_account_message')) { ?>
                    <div class="mb-20 is-danger is-notification is-danger" >
						<?php echo get_option('sender_account_message') ?>
                    </div>
				<?php } ?>

                    <div class="sender-form-label">Enter your API key</div>
                    <input name="sender_api_key" type="text" id="sender_api_key" placeholder="Paste your API key here"
                           class="sender-input sender-text-input mb-20">
                    <input type="submit" name="submit" id="submit" class="sender-cta-button is-large mb-20"
                           value="Begin">

                    <div class="sender-api-text">
                        <a href="https://help.sender.net/knowledgebase/api-documentation/" target="_blank" class="sender-link">Click here</a> if you are not sure where to find it
                    </div>
                </div>
            </form>

		<?php } else {  ?>
            <div class="sender-settings-grid">
                <div class="flex-column sender-box is-justified-between">
                    <div>
                        <div class="mb-20">
                            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo.svg'; ?>"
                            class="sender-logo is-small d-block"  alt="Sender logo">
                        </div>
                    
                        <div class="sender-username mb-20">
                            <div class="sender-header"><?php echo $user->account->title ?></div>
                        </div>
                    </div>

                    <div class="sender-logout is-justified-between negative-margin">
                        <div class="status-display mb-20">
                            <div class="checkbox">
                                <span class="tick"></span>
                            </div>
                            <span>
                                <?php if ($user->account->active_plan->type === 'PAYG') {
                                    echo "Activated";
                                } else {
                                    if ($user->account->active_plan->type === 'SUBSCRIPTION') {
                                        echo "Activated";
                                    } else {
                                        echo "Activated";
                                    }
                                }
                                ?>
                            </span>
                        </div>
                        <form method="post" action='' class="mb-20">
                            <input name="sender_api_key" type="hidden" id="sender_api_key" value=""
                                   class="sender-input sender-text-input ">
                            <input type="submit" name="submit" id="submit" class="sender-cta-button is-medium"
                                   value="Change user">
                        </form>
                    </div>
                </div>
                <?php if ($wooEnabled) { ?>
                <div  style="position: relative" class="sender-plugin-settings sender-box">
                    <div class="sender-header mb-20">WooCommerce settings</div>
                    <form method="post" class="flex-column h-100" action='' id="sender-form-settings">
                        <div class="sender-options">
                            <div class="sender-option mb-20">
                                <input type="hidden" value="0" name="sender_allow_tracking_hidden_checkbox">
                                <label for="sender_allow_tracking" class="checkbox-label">
                                    <input class="sender-checkbox" type="checkbox" id="sender_allow_tracking"
                                           value="sender_allow_tracking"
                                           name="sender_allow_tracking" <?php if (get_option('sender_allow_tracking')) {
										echo 'checked';
									} ?> >
                                    <span>Enable tracking</span>
                                </label>
                            </div>

                            <div class="sender-option mb-20">
                                <label class="sender-select-label sender-form-label" for="sender_customers_list">Save "Recent buyers" to:</label>
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

                            <div class="sender-option mb-20">
                                <label class="sender-select-label sender-form-label" for="sender_registration_list">Save "Registered" customers to:</label>
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
                        <div class="sender-logout is-justified-between negative-margin">
                        <div class="sender-woocommerce-text mb-20">
                                <a href="#" target="_blank" class="sender-link">Click here</a> for more information
                            </div>
                            <div class="btn-wrap">
                                <input type="submit" name="submit" id="submit" class="sender-cta-button is-medium mb-20"
                                    value="Save">
                            </div>       
                        </div>
                    </form>
                </div>
                <?php } ?>
            </div>
		<?php } ?>
    </div>
</div>

<script>
    jQuery('#sender_allow_tracking').on('change', function (ev){
        jQuery('.sender-woo-lists').prop('disabled', !jQuery(ev.currentTarget).is(':checked'))
    });
</script>