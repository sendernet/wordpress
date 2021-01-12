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

		<?php } else {?>
            <div class="sender-settings-grid">
                <div class="sender-account-info sender-box">
                    <div class="sender-logo">
                        <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/logo.png'; ?>" alt="Sender logo">
                    </div>
                    <div class="sender-username">
                        <h2 class="sender-header"><?php echo $user->account->title ?></h2>
                    </div>

                    <div class="flex-grow-1"></div>
                    <div class="sender-logout">
                        <div class="sender-subheader">
							<?php if($user->account->active_plan->type === 'PAYG') {
								echo "Pay as you go plan";
							} else if($user->account->active_plan->type === 'SUBSCRIPTION') {

								echo "Monthly subscription";
							} else {
								echo "Free plan";
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
                    <form method="post" action=''>
                        <div class="sender-option">
                            <input class="sender-checkbox" type="checkbox" id="sender_allow_guest_track" value="sender_allow_guest_track" name="sender_allow_guest_track" <?php if (get_option('sender_allow_guest_track')) {echo 'checked';} ?> >
                            <label for="sender_allow_guest_track">Allow guests tracking</label>
                        </div>
                        <div class="sender-option">
                            <input class="sender-checkbox" type="checkbox" id="sender_allow_forms" name="sender_allow_guest_track" <?php if (get_option('sender_allow_forms')) {echo 'checked';} ?> >
                            <label for="sender_allow_forms">Show forms and pup-ups</label>
                        </div>
                        <div class="sender-option">
                            <input class="sender-checkbox" type="checkbox" id="sender_allow_guest_track" name="sender_allow_import" <?php if (get_option('sender_allow_import')) {echo 'checked';} ?> >
                            <label for="sender_allow_import">Allow products import</label>
                        </div>
                        <div class="sender-logout">
                            <input type="submit" name="submit" id="submit" class="sender-cta-button sender-input"
                                   value="Save">
                        </div>
                    </form>
                </div>
                <div class="sender-forms-list sender-box"></div>
            </div>
        <?php } ?>
    </div>
</div>
<style>

    .sender-logout {
        display: flex;
        justify-content: flex-end;
        align-items: center;

    }
    .sender-account-info {
        display: flex;
        flex-direction: column;
    }

    .flex-grow-1 {
        flex-grow: 1;
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

    .sender-box {
        border-radius: 10px;
        box-shadow: 0 0 4px 0 rgba(0, 0, 0, .1);
        border: 1px solid #ddd;
        background: white;
        padding: 20px;
    }

    .sender-input {

    }
</style>