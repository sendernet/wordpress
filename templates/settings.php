<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<div class="sender-container">
    <div class="sender-flex-column">
		<?php if (!$apiKey) { ?>
            <form method="post" action='' class="sender-box required-api-key" novalidate="novalidate">
                <div class="sender-login-image">
                    <img src="/wp-content/plugins/sender/assets/images/logo.png">
                </div>
                <h2 class="sender-header">Enter your API key</h2>

                <div class="sender-flex-center-column">
                <div class="flex-grow-1"></div>

                <?php if(get_option('sender_account_message')) { ?>
                    <div class="sender-is-danger sender-padding-10" >
						<?php echo get_option('sender_account_message') ?>
                    </div>
				<?php } ?>


                    <input name="sender_api_key" type="text" id="sender_api_key" placeholder="Paste your API key here"
                           class="sender-input sender-text-input ">
                    <input type="submit" name="submit" id="submit" class="sender-cta-button"
                           value="Begin">

                    <div class="sender-api-text">
                        <a href="https://help.sender.net/knowledgebase/api-documentation/" target="_blank" class="sender-link">Click here</a> if you are not sure where to find it
                    </div>
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
                                <label class="sender-select-label" for="sender_customers_list">Save "Recent buyers" to:</label>
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
                                <label class="sender-select-label" for="sender_registration_list">Save "Registered" customers to:</label>
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
                            <?php if(isset($_GET['submit'])){
                                echo "Changes saved";
                            }
                            ?>
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