<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<div class="sender-container <?php if(!$apiKey) { echo 'sender-single-column sender-d-flex'; } ?>">
    <div class="sender-flex-column">
		<?php if (!$apiKey) { ?>
            <form method="post" action='' class="sender-box sender-br-5 sender-api-key sender-d-flex sender-flex-dir-column" novalidate="novalidate">
                <div class="sender-login-image">
                    <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo.svg'; ?>" class="sender-logo" alt="Sender logo">
                </div>

                <div class="sender-flex-center-column sender-h-100 sender-d-flex sender-flex-dir-column">

                <?php if(get_option('sender_account_message')) { ?>
                    <div class="sender-mb-20 sender-notification sender-is-danger sender-br-5" >
						<?php echo get_option('sender_account_message') ?>
                    </div>
				<?php } ?>
                    <div class="sender-d-flex">
                        <label for="sender_api_key" class="sender-label sender-form-label">Enter your API key</label>
                    </div>  
                    <input name="sender_api_key" type="text" id="sender_api_key" placeholder="Paste your API key here"
                           class="sender-input sender-text-input sender-mb-20 sender-br-5">
                    <input type="submit" name="submit" id="submit" class="sender-cta-button sender-large sender-mb-20 sender-br-5"
                           value="Begin">

                    <div class="sender-api-text">
                        <a href="https://app.sender.net/settings/tokens" target="_blank" class="sender-link">Click here</a> if you are not sure where to find it
                    </div>
                </div>
            </form>

		<?php } else {  ?>
            <div class="sender-settings-layout sender-d-flex sender-flex-dir-column">
                <div class="sender-flex-dir-column sender-box sender-br-5 sender-d-flex sender-justified-between">
                    <div>
                        <div class="sender-mb-20">
                            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo.svg'; ?>"
                            class="sender-logo sender-small"  alt="Sender logo">
                        </div>
                    
                        <div class="sender-username sender-mb-20">
                            <div class="sender-header"><?php echo $user->account->title ?></div>
                        </div>
                    </div>

                    <div class="sender-logout sender-d-flex sender-justified-between">
                        <div class="sender-status-display sender-d-flex sender-mb-20 sender-p-relative">
                            <div class="sender-green-checkbox">
                                <span class="sender-checkbox-tick"></span>
                            </div>
                            <div class="sender-status-text sender-p-relative sender-default-text">
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
                            </div>
                        </div>
                        <div class="sender-btn-wrap sender-d-flex">
                            <form method="post" action='' class="sender-mb-20">
                                <input name="sender_api_key" type="hidden" id="sender_api_key" value=""
                                    class="sender-input sender-text-input sender-br-5">
                                <input type="submit" name="submit" id="submit" class="sender-cta-button sender-medium sender-br-5"
                                    value="Change user">
                            </form>
                        </div>
                    </div>
                </div>
                <?php if ($wooEnabled) { ?>
                <div class="sender-plugin-settings sender-box sender-br-5 sender-p-relative">
                    <div class="sender-header sender-mb-20">WooCommerce settings</div>
                    <form method="post" class="sender-flex-dir-column sender-d-flex sender-h-100" action='' id="sender-form-settings">
                        <div class="sender-options sender-d-flex sender-flex-dir-column">
                            <div class="sender-option sender-d-flex sender-p-relative sender-mb-20">
                                <input type="hidden" value="0" name="sender_allow_tracking_hidden_checkbox">
                                <label for="sender_allow_tracking" class="sender-label sender-checkbox-label sender-p-relative">
                                    <input class="sender-checkbox" type="checkbox" id="sender_allow_tracking"
                                           value="sender_allow_tracking"
                                           name="sender_allow_tracking" <?php if (get_option('sender_allow_tracking')) {
										echo 'checked';
									} ?> >
                                    <span class="sender-visible-checkbox" style="background-image: url(<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/white_check.png'; ?>)"></span>
                                    <span>Enable tracking</span>
                                </label>
                            </div>

                            <div class="sender-option sender-mb-20">
                                <div class="sender-dropdown-wrap">
                                    <label class="sender-label sender-select-label sender-form-label" for="sender_customers_list">Save "Customers who made a purchase" to:</label>
                                    <div class="sender-select-wrap sender-p-relative">
                                        <select form="sender-form-settings" class="sender-woo-lists sender-br-5" name="sender_customers_list" <?php if (!get_option('sender_allow_tracking')) {
                                            echo 'disabled';
                                        } ?> id="sender_customers_list" value="<?=get_option('sender_customers_list')?>">
                                            <option value="0">Select a list</option>
                                            <?php foreach ($groups as $tag): ?>
                                                <option  <?= get_option('sender_customers_list') == $tag->id ? 'selected' : '' ?>  value="<?=$tag->id?>"><?=$tag->title?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="sender-option sender-mb-20">
                                <div class="sender-dropdown-wrap">
                                    <label class="sender-label sender-select-label sender-form-label" for="sender_registration_list">Save "New registrations" to:</label>
                                    <div class="sender-select-wrap sender-p-relative">
                                        <select form="sender-form-settings" <?php if (!get_option('sender_allow_tracking')) {
                                            echo 'disabled';
                                            } ?> name="sender_registration_list" class="sender-woo-lists sender-br-5" id="sender_registration_list" value="<?=get_option('sender_registration_list')?>">
                                                <option value="0">Select a list</option>
                                            <?php foreach ($groups as $tag): ?>
                                                <option  <?= get_option('sender_registration_list') == $tag->id ? 'selected' : '' ?>  value="<?=$tag->id?>"><?=$tag->title?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="sender-logout sender-d-flex sender-justified-between">
                        <div class="sender-default-text sender-mb-20">
                                <a href="https://help.sender.net/knowledgebase/the-documentation-for-woocommerce-plugin/" target="_blank" class="sender-link">Click here</a> for documentation of WooCommerce plugin
                            </div>
                            <div class="sender-btn-wrap sender-d-flex">
                                <input type="submit" name="submit" id="submit" class="sender-cta-button sender-medium sender-mb-20 sender-br-5"
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
    var checkboxEl = jQuery('#sender_allow_tracking');

    jQuery(document).ready(function() {
        if(checkboxEl[0] && !checkboxEl[0].checked) {
            jQuery('.sender-dropdown-wrap').addClass('sender-disabled');
        }        
    });

    checkboxEl.on('change', function (ev){
        jQuery('.sender-woo-lists').prop('disabled', !jQuery(ev.currentTarget).is(':checked'));
        jQuery('.sender-dropdown-wrap').toggleClass('sender-disabled', !jQuery(ev.currentTarget).is(':checked'));
    });
</script>