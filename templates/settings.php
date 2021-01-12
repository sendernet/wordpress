<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
<div class="sender-container">
    <div class="sender-flex-column">
		<?php if (!$apiKey) { ?>
            <form method="post" action='' class="sender-box required-api-key" novalidate="novalidate">
                <h2 class="sender-header">Enter your api key</h2>

                <div class="sender-subheader"> We need your API key to continue. <br>
                    <a href="#" class="sender-link">Click here</a> if you are not sure where to find it.

                </div>

                <div class="sender-flex-center-column">
                    <input name="sender_api_key" type="text" id="sender_api_key" placeholder="Paste your api key here"
                           class="sender-input sender-text-input ">
                    <input type="submit" name="submit" id="submit" class="sender-cta-button sender-input"
                           value="Begin">
                </div>

            </form>

		<?php } else { ?>
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
                        <div class="sender-subheader">
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
                            <input name="sender_api_key" type="hidden" id="sender_api_key" value="api_key"
                                   class="sender-input sender-text-input ">
                            <input type="submit" name="submit" id="submit" class="sender-cta-button sender-input"
                                   value="Change user">
                        </form>
                    </div>
                </div>
                <div class="sender-plugin-settings sender-box">
                    <form method="post" class="flex-column h-100" action=''>
                        <div class="sender-options">
                            <div class="sender-option">
                                <input type="hidden" value="0" name="sender_allow_guest_track_hidden_checkbox">
                                <label for="sender_allow_guest_track">
                                    <input class="sender-checkbox" type="checkbox" id="sender_allow_guest_track"
                                           value="sender_allow_guest_track"
                                           name="sender_allow_guest_track" <?php if (get_option('sender_allow_guest_track')) {
										echo 'checked';
									} ?> >
                                    <span>Allow guests tracking</span>
                                </label>

                            </div>
                            <div class="sender-option">
                                <input type="hidden" value="0" name="sender_allow_forms_hidden_checkbox">
                                <label for="sender_allow_forms">
                                    <input class="sender-checkbox" type="checkbox" id="sender_allow_forms"
                                           name="sender_allow_forms" <?php if (get_option('sender_allow_forms')) {
										echo 'checked';
									} ?> >
                                    <span>Show forms and pup-ups</span>
                                </label>
                            </div>
                            <div class="sender-option">
                                <input type="hidden" value="0" name="sender_allow_import_hidden_checkbox">
                                <label for="sender_allow_import">
                                    <input class="sender-checkbox" type="checkbox" id="sender_allow_import"
                                           name="sender_allow_import" <?php if (get_option('sender_allow_import')) {
										echo 'checked';
									} ?> >
                                    <span>Allow products import</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex-grow-1"></div>
                        <div class="sender-logout">
                            <input type="submit" name="submit" id="submit" class="sender-cta-button sender-input"
                                   value="Save">
                        </div>
                    </form>
                </div>
                <div class="sender-forms-list">
                    <div class="sender-big-header"> Forms </div>
                    <div class=" sender-box no-padding flex-column">
                        <?php foreach ($forms as $form): ?>
                            <div class="sender-form-row">
                                <div class="sender-form-thumbnail">
                                    <img src="<?=$form->thumbnail_url?>" alt="thumbnail">
                                </div>
                                <div class="sender-form-text flex-column">
                                    <div class="sender-header mb-10"><?= $form->title ?></div>
                                    <div class="sender-subheader">Edited <?= $form->modified ?></div>
                                </div>
                                <div class="flex-grow-1"></div>
                                <div class="sender-form-stats">
                                    <div class="sender-header-small mb-10">Subscribers</div>
                                    <div class="sender-subheader"><?= $form->subscribed ?></div>
                                </div>
                                <div class="sender-form-stats">
                                    <div class="sender-header-small mb-10">Visitors</div>
                                    <div class="sender-subheader"><?= $form->visited ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
		<?php } ?>
    </div>
</div>
<style>

    .h-100 {
        height: 100%;
    }

    .sender-container {
        font-family: 'Roboto', sans-serif !important;
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
        font-size: 13px;
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
        font-size: 22px;
        color: #222;
        line-height: 22px;
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

    .sender-input {

    }

    input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        z-index: -1;
    }

    /* Text color for the label */
    input[type="checkbox"] + span {
        cursor: pointer;
        font: 16px sans-serif;
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