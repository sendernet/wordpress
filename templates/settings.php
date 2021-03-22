<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<div class="sender-container <?php if(!$apiKey) { echo 'single-column d-flex'; } ?>">
    <div class="sender-flex-column">
		<?php if (!$apiKey) { ?>
            <form method="post" action='' class="sender-box border-radius-5 required-api-key d-flex flex-column" novalidate="novalidate">
                <div class="sender-login-image">
                    <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo.svg'; ?>" class="sender-logo d-block" alt="Sender logo">
                </div>

                <div class="sender-flex-center-column h-100 d-flex flex-column">

                <?php if(get_option('sender_account_message')) { ?>
                    <div class="mb-20 is-notification is-danger border-radius-5" >
						<?php echo get_option('sender_account_message') ?>
                    </div>
				<?php } ?>
                    <div class="d-flex">
                        <label for="sender_api_key" class="sender-form-label">Enter your API key</label>
                    </div>  
                    <input name="sender_api_key" type="text" id="sender_api_key" placeholder="Paste your API key here"
                           class="sender-input sender-text-input mb-20 border-radius-5">
                    <input type="submit" name="submit" id="submit" class="sender-cta-button is-large mb-20 border-radius-5"
                           value="Begin">

                    <div class="sender-api-text">
                        <a href="https://help.sender.net/knowledgebase/access-tokens/" target="_blank" class="sender-link">Click here</a> if you are not sure where to find it
                    </div>
                </div>
            </form>

		<?php } else {  ?>
            <div class="sender-settings-layout d-flex flex-column">
                <div class="flex-column sender-box border-radius-5 d-flex is-justified-between">
                    <div>
                        <div class="mb-20">
                            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo.svg'; ?>"
                            class="sender-logo is-small d-block"  alt="Sender logo">
                        </div>
                    
                        <div class="sender-username mb-20">
                            <div class="sender-header"><?php echo $user->account->title ?></div>
                        </div>
                    </div>

                    <div class="sender-logout d-flex is-justified-between">
                        <div class="status-display d-flex mb-20 is-relative">
                            <div class="checkbox">
                                <span class="tick"></span>
                            </div>
                            <div class="status-text is-relative default-text">
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
                        <div class="btn-wrap d-flex">
                            <form method="post" action='' class="mb-20">
                                <input name="sender_api_key" type="hidden" id="sender_api_key" value=""
                                    class="sender-input sender-text-input border-radius-5">
                                <input type="submit" name="submit" id="submit" class="sender-cta-button is-medium border-radius-5"
                                    value="Change user">
                            </form>
                        </div>
                    </div>
                </div>
                <?php if ($wooEnabled) { ?>
                <div class="sender-plugin-settings sender-box border-radius-5 is-relative">
                    <div class="sender-header mb-20">WooCommerce settings</div>
                    <form method="post" class="flex-column d-flex h-100" action='' id="sender-form-settings">
                        <div class="sender-options d-flex flex-column">
                            <div class="sender-option d-flex is-relative mb-20">
                                <input type="hidden" value="0" name="sender_allow_tracking_hidden_checkbox">
                                <label for="sender_allow_tracking" class="checkbox-label is-relative">
                                    <input class="sender-checkbox" type="checkbox" id="sender_allow_tracking"
                                           value="sender_allow_tracking"
                                           name="sender_allow_tracking" <?php if (get_option('sender_allow_tracking')) {
										echo 'checked';
									} ?> >
                                    <span class="visible-checkbox" style="background-image: url(<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/white_check.png'; ?>)"></span>
                                    <span>Enable tracking</span>
                                </label>
                            </div>

                            <div class="sender-option mb-20">
                                <div class="dropdown-wrap">
                                    <label data-role="multiselect" class="sender-select-label sender-form-label" for="sender_customers_list">Save "Guest Checkouts" to:</label>
                                    <div class="sender-select-wrap is-relative">
                                        <select form="sender-form-settings" class="sender-woo-lists border-radius-5" name="sender_customers_list" <?php if (!get_option('sender_allow_tracking')) {
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

                            <div class="sender-option mb-20">
                                <div class="dropdown-wrap">
                                    <label data-role="multiselect1" class="sender-select-label sender-form-label" for="sender_registration_list">Save "Customers accounts" to:</label>
                                    <div class="sender-select-wrap is-relative">
                                        <select form="sender-form-settings" <?php if (!get_option('sender_allow_tracking')) {
                                            echo 'disabled';
                                            } ?> name="sender_registration_list" class="sender-woo-lists border-radius-5" id="sender_registration_list" value="<?=get_option('sender_registration_list')?>">
                                                <option value="0">Select a list</option>
                                            <?php foreach ($groups as $tag): ?>
                                                <option  <?= get_option('sender_registration_list') == $tag->id ? 'selected' : '' ?>  value="<?=$tag->id?>"><?=$tag->title?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="sender-logout d-flex is-justified-between">
                        <div class="default-text mb-20">
                                <a href="https://help.sender.net/knowledgebase/the-documentation-for-woocommerce-plugin/" target="_blank" class="sender-link">Click here</a> for documentation of WooCommerce plugin
                            </div>
                            <div class="btn-wrap d-flex">
                                <input type="submit" name="submit" id="submit" class="sender-cta-button is-medium mb-20 border-radius-5"
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
            jQuery('.dropdown-wrap').addClass('disabled');
        }        
    });

    checkboxEl.on('change', function (ev){
        jQuery('.sender-woo-lists').prop('disabled', !jQuery(ev.currentTarget).is(':checked'));
        jQuery('.dropdown-wrap').toggleClass('disabled', !jQuery(ev.currentTarget).is(':checked'));
    });
</script>