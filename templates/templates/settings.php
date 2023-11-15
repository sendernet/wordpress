<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<div class="sender-container <?php if (!$apiKey || get_option('sender_account_disconnected')) {
    echo 'sender-single-column sender-d-flex';
} ?>">
    <div class="sender-flex-column">
        <?php
        // Check if the sync has finished
        $syncFinished = get_transient('sender_sync_finished');
        if ($syncFinished) {
            echo '<div id="sender-data-sync-notice" class="notice notice-success is-dismissible"><p>Synced data completed.</p></div>';
            echo '<br>';
            delete_transient('sender_sync_finished');
        }
        ?>
        <?php if (!$apiKey || get_option('sender_account_disconnected')) { ?>
            <form method="post" action=''
                  class="sender-box sender-br-5 sender-api-key sender-d-flex sender-flex-dir-column"
                  novalidate="novalidate">
                <div class="sender-login-image">
                    <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo.svg'; ?>"
                         class="sender-logo" alt="Sender logo">
                </div>

                <div class="sender-flex-center-column sender-h-100 sender-d-flex sender-flex-dir-column">

                    <?php if (get_option('sender_account_message')) { ?>
                        <div class="sender-mb-20 sender-notification sender-is-danger sender-br-5">
                            <?php echo get_option('sender_account_message') ?>
                        </div>
                    <?php } ?>
                    <div class="sender-d-flex">
                        <label for="sender_api_key" class="sender-label sender-form-label">Enter your API key</label>
                    </div>
                    <input name="sender_api_key" type="text" id="sender_api_key" placeholder="Paste your API key here"
                           class="sender-input sender-text-input sender-mb-20 sender-br-5">
                    <input type="submit" name="submit" id="submit"
                           class="sender-cta-button sender-large sender-mb-20 sender-br-5"
                           value="Begin">

                    <div class="sender-api-text">
                        <a href="https://app.sender.net/settings/tokens" target="_blank" class="sender-link">Click
                            here</a> if you are not sure where to find it
                    </div>
                </div>
            </form>

        <?php } else { ?>
            <div class="sender-settings-layout sender-d-flex sender-flex-dir-column">
                <div class="sender-flex-dir-column sender-box sender-br-5 sender-d-flex sender-justified-between">
                    <div>
                        <div class="sender-mb-20">
                            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/logo.svg'; ?>"
                                 class="sender-logo sender-small" alt="Sender logo">
                        </div>

                    </div>

                    <div class="sender-logout sender-d-flex sender-justified-between">
                        <div class="sender-status-display sender-d-flex sender-mb-20 sender-p-relative">
                            <div class="sender-green-checkbox">
                                <span class="sender-checkbox-tick"></span>
                            </div>
                            <div class="sender-status-text sender-p-relative sender-default-text">
                                Connected to Sender account <br>
                                <strong><?php echo get_option('sender_account_title'); ?></strong>
                            </div>
                        </div>
                        <div class="sender-btn-wrap sender-d-flex">
                            <form method="post" action='' class="sender-mb-20">
                                <input name="sender_account_disconnected" type="hidden" id="sender_account_disconnected"
                                       value="true"
                                       class="sender-input sender-text-input sender-br-5">
                                <input type="submit" name="submit" id="sender-confirmation"
                                       class="sender-cta-button sender-medium sender-br-5"
                                       value="Change account">
                            </form>
                        </div>
                    </div>
                </div>
                <?php if ($wooEnabled) { ?>
                    <div class="sender-plugin-settings sender-box sender-br-5 sender-p-relative">
                        <div class="sender-header sender-mb-20">WooCommerce settings</div>
                        <form method="post" class="sender-flex-dir-column sender-d-flex sender-h-100" action=''
                              id="sender-form-settings">
                            <div class="sender-options sender-d-flex sender-flex-dir-column">
                                <div class="sender-option sender-d-flex sender-p-relative sender-mb-20">
                                    <input type="hidden" value="0" name="sender_allow_tracking_hidden_checkbox">
                                    <label for="sender_allow_tracking"
                                           class="sender-label sender-checkbox-label sender-p-relative">
                                        <input class="sender-checkbox" type="checkbox" id="sender_allow_tracking"
                                               value="sender_allow_tracking"
                                               name="sender_allow_tracking" <?php if (get_option('sender_allow_tracking')) {
                                            echo 'checked';
                                        } ?> >
                                        <span class="sender-visible-checkbox"
                                              style="background-image: url(<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/white_check.png'; ?>)"></span>
                                        <span>Enable tracking</span>
                                    </label>
                                </div>

                                <div class="sender-option sender-mb-20">
                                    <div class="">
                                        <label class="sender-label sender-select-label sender-form-label"
                                               for="sender_customers_list">Save "Customers who made a purchase"
                                            to:</label>
                                        <div class="sender-select-wrap sender-p-relative">
                                            <select form="sender-form-settings"
                                                    class="sender-woo-lists sender-br-5 select2-custom"
                                                    name="sender_customers_list" <?php if (!get_option('sender_allow_tracking')) {
                                                echo 'disabled';
                                            } ?> id="sender_customers_list"
                                                    value="<?= get_option('sender_customers_list') ?>">
                                                <option value="0">Select a list</option>
                                                <?php foreach (get_option('sender_groups_data') as $groupId => $groupTitle): ?>
                                                    <option <?= get_option('sender_customers_list') == $groupId ? 'selected' : '' ?>
                                                            value="<?= $groupId ?>"><?= $groupTitle ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="sender-option sender-mb-20">
                                    <div>
                                        <label class="sender-label sender-select-label sender-form-label"
                                               for="sender_registration_list">Save "New registrations" to:</label>
                                        <div class="sender-select-wrap sender-p-relative">
                                            <select form="sender-form-settings" <?php if (!get_option('sender_allow_tracking')) {
                                                echo 'disabled';
                                            } ?> name="sender_registration_list"
                                                    class="sender-woo-lists sender-br-5 select2-custom"
                                                    id="sender_registration_list"
                                                    value="<?= get_option('sender_registration_list') ?>">
                                                <option value="0">Select a list</option>
                                                <?php foreach (get_option('sender_groups_data') as $groupId => $groupTitle): ?>
                                                    <option <?= get_option('sender_registration_list') == $groupId ? 'selected' : '' ?>
                                                            value="<?= $groupId ?>"><?= $groupTitle ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="sender-logout sender-d-flex sender-justified-between">
                                <div class="sender-default-text sender-mb-20">
                                    <a href="https://help.sender.net/knowledgebase/the-documentation-for-woocommerce-plugin/"
                                       target="_blank" class="sender-link">Click here</a> for documentation of
                                    WooCommerce plugin
                                </div>
                                <div class="sender-btn-wrap sender-d-flex">
                                    <input type="submit" name="submit" id="submit"
                                           class="sender-cta-button sender-medium sender-mb-20 sender-br-5"
                                           value="Save">
                                </div>
                            </div>
                        </form>

                    </div>
                    <div class="sender-plugin-settings sender-box sender-br-5 sender-p-relative sender-mb-20">
                        <div class="sender-header sender-mb-20">Subscribe to newsletter label</div>
                        <p>Change the default text showing in cart checkouts and user account profile to your custom
                            text.</p>
                        <p><strong>Enable tracking must be active</strong></p>
                        <form method="post" class="sender-flex-dir-column sender-d-flex sender-h-100" action=''
                              id="sender-form-settings">
                            <div class="sender-options sender-d-flex sender-flex-dir-column">
                                <div class="sender-option sender-d-flex sender-p-relative sender-mb-20">
                                    <input type="hidden" value="0" name="sender_subscribe_label_hidden_checkbox">
                                    <label for="sender_subscribe_label"
                                           class="sender-label sender-checkbox-label sender-p-relative">
                                        <input class="sender-checkbox sender-label-subscribe" type="checkbox"
                                               id="sender_subscribe_label"
                                               value="sender_subscribe_label"
                                               name="sender_subscribe_label" <?php if (get_option('sender_subscribe_label')) {
                                            echo 'checked';
                                        } ?> >
                                        <span class="sender-visible-checkbox"
                                              style="background-image: url(<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/white_check.png'; ?>)"></span>
                                        <span>Enable</span>
                                    </label>
                                </div>
                                <div class="sender-option sender-mb-20">
                                    <div class="sender-subscriber-label-input">
                                        <label>
                                            <input maxlength="255" name="sender_subscribe_to_newsletter_string"
                                                   type="text"
                                                   class="sender-input sender-text-input sender-mb-20 sender-br-5 sender-label-subscribe"
                                                   id="sender_subscribe_to_newsletter_string"
                                                   value="<?php echo get_option('sender_subscribe_to_newsletter_string') ?>">
                                            <input type="submit" name="submit" id="submit-label-newsletter"
                                                   class="sender-cta-button sender-large sender-mb-20 sender-br-5 sender-submit-label-subscribe"
                                                   value="Save">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </form>

                    </div>

                    <div class="sender-flex-dir-column sender-box sender-br-5 sender-d-flex sender-justified-between sender-mt-20">
                        <form method="post" class="sender-flex-dir-column sender-d-flex sender-h-100" action=''
                              id="sender-export-data">
                            <div class="sender-mb-20">
                                <?php
                                // Check if there is a running or scheduled cron job
                                if ((isset($isCronJobRunning) && $isCronJobRunning) && (isset($syncFinished) && !$syncFinished)) {
                                    $disableSubmit = 'disabled';
                                    $noticeMessage = 'A job is running to sync data with Sender application.';
                                } else {
                                    $disableSubmit = '';
                                    $noticeMessage = 'Import all subscribers, orders, and products from your WooCommerce store into your Sender account.';
                                }
                                ?>
                                <input name="sender_wocommerce_sync" type="hidden" id="sender_wocommerce_sync" value="0"
                                       class="sender-input sender-text-input sender-br-5">
                                <div class="sender-btn-wrap sender-d-flex">
                                    <input type="submit" name="submit" id="sender-submit-sync"
                                           class="sender-cta-button sender-medium sender-br-5 sender-height-fit"
                                           value="Sync with Sender" <?php echo $disableSubmit; ?>>
                                    <div class="sender-default-text" id="sender-import-text">
                                        <?php echo $noticeMessage; ?>
                                        <a target="_blank" class="sender-link"
                                           href="https://app.sender.net/settings/connected-stores">See your store
                                            information</a>
                                        <span style="display: block">Last time synchronized: <strong
                                                    style="display: block"><?php echo get_option('sender_synced_data_date'); ?></strong></span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php } else { ?>
                <div class="sender-box sender-br-5 sender-p-relative" style="padding-top:0px!important">
                    <p>You can now find your <a target="_blank" class="sender-link" href="https://app.sender.net/forms">Sender.net
                            forms</a> in WordPress widgets or in the page builder.</p>
                </div>
            </div>
    <?php } ?>
    </div>
    <?php } ?>
</div>
</div>

<!--Select2 sender styling-->
<style>
    .select2 {
        font-size: 13px !important;
    }

    .select2-selection, .select2-selection__clear, .select2-selection__arrow {
        height: 40px !important;
    }

    .select2-selection__rendered {
        line-height: 40px !important;
        color: #000000 !important;
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable, .select2-results__option:hover, .select2-results__option:active, .select2-results__option:focus {
        background-color: #ff8d00 !important;
    }

    .select2-results__option {
        color: #0a0c0d;
    }

    .select2-search__field {
        border-color: transparent !important;
        box-shadow: 0 0 0 1px #8a8787 !important;
    }

    .select2-selection__arrow, .select2-selection__clear {
        font-size: 18px !important;
    }

    .select2-selection__arrow b {
        border-color: #000 transparent transparent transparent !important;
    }

    .select2-selection__clear {
        margin-right: 35px !important;
    }

    .select2-selection__arrow {
        margin-right: 5px !important;
    }

    .select2-selection__rendered {
        margin-left: 5px !important;
    }

</style>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script>
    var checkboxEl = jQuery('#sender_allow_tracking');
    var checkboxLabel = jQuery('#sender_subscribe_label');


    jQuery(document).ready(function () {
        if (checkboxEl[0] && !checkboxEl[0].checked) {
            jQuery('.sender-dropdown-wrap').addClass('sender-disabled');
            jQuery('.sender-subscriber-label-input').addClass('sender-disabled');
            jQuery('.sender-label-subscribe').prop('disabled', true);
        }

        var textField = jQuery('#sender_subscribe_to_newsletter_string');
        var submitBtn = jQuery('#submit-label-newsletter');

        textField.on('input', function() {
            if (textField.val().trim() === '') {
                submitBtn.prop('disabled', true);
                if (!textField.next('.sender-error-message').length) {
                    textField.after('<div class="sender-error-message" style="color:#b41d1d!important;margin-bottom:10px;">This field cannot be empty.</div>');
                }
            } else {
                textField.next('.sender-error-message').remove();
                submitBtn.prop('disabled', false);
            }
        });
    });

    checkboxEl.on('change', function (ev) {
        jQuery('.sender-woo-lists').prop('disabled', !jQuery(ev.currentTarget).is(':checked'));
        jQuery('.sender-dropdown-wrap').toggleClass('sender-disabled', !jQuery(ev.currentTarget).is(':checked'));
        jQuery('.sender-subscriber-label-input').toggleClass('sender-disabled', !jQuery(ev.currentTarget).is(':checked'))
            .prop('disabled', !jQuery(ev.currentTarget).is(':checked'));
        jQuery('.sender-label-subscribe').toggleClass('sender-disabled', !jQuery(ev.currentTarget).is(':checked'))
            .prop('disabled', !jQuery(ev.currentTarget).is(':checked'));
    });

    checkboxLabel.on('change', function (ev) {
        jQuery('.sender-subscriber-label-input').prop('disabled', !jQuery(ev.currentTarget).is(':checked'));
    });

    jQuery('#sender-confirmation').click(function (e) {
        e.preventDefault();
        toggleModal('This will disconnect the store from your Sender account.');
    });


    function toggleModal(text) {
        $wrapper = jQuery('<div class="sender-container" id="sender-modal-wrapper"></div>').appendTo('body');
        $modal = jQuery('<div id="sender-modal-confirmation">' +
            '<div id="sender-modal-header">' +
            '<h3 class="sender-header">Confirm Delete</h3>' +
            '<span class="sender-modal-action" id="sender-modal-close">' +
            'x</span>' +
            '</div>' +
            '<div id="sender-modal-content" class="sender-label sender-select-label sender-form-label">' +
            '<p>' + text + '</p>' +
            '<form id="sender-modal-form" method="post" action="">' +
            '<div style="margin: 15px 0 25px;">' +
            '<label class="sender-label" for="delete-subscribers-checkbox">' +
            '<input class="sender-checkbox" type="checkbox" id="delete-subscribers-checkbox" name="delete-subscribers">' +
            '<span class="sender-visible-checkbox sender-visible-checkbox-modal" style="background-image: url(<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/white_check.png'; ?>)"></span>' +
            '<span> Delete subscribers associated with this store </span>' +
            '</label>' +
            '</div>' +
            '<div id="sender-modal-buttons">' +
            '<button id="no-disconnected" class="sender-cta-button sender-medium sender-br-5 sender-modal-action-btn sender-modal-action">No</button>' +
            '<input name="sender_account_disconnected" type="hidden" id="sender_account_disconnected" value="true">' +
            '<input type="submit" name="submit" class="sender-cta-button sender-medium sender-br-5 sender-modal-action-btn" value="Yes"></input>' +
            '</div>' +
            '</form>' +
            '</div>' +
            '</div>').appendTo($wrapper);

        setTimeout(function () {
            $wrapper.addClass('active');
        }, 100);

        $wrapper.find('.sender-modal-action').click(function () {
            $wrapper.removeClass('active').delay(500).queue(function () {
                $wrapper.remove();
            });
        });

        $wrapper.find('#no-disconnected').click(function (e) {
            e.preventDefault();
            $wrapper.removeClass('active').delay(500).queue(function () {
                $wrapper.remove();
            });
        });
    }

    jQuery(document).ready(function() {
        if (jQuery.fn.select2) {
            jQuery('.select2-custom').select2({
                placeholder: 'Select a list',
                allowClear: true,
                width: '100%',
            });
        }
    });

</script>