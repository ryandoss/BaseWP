<?php
class GFQuizFormSettings {

    public static function form_settings_page() {
        $form_id = rgget('id');
        $form        = RGFormsModel::get_form_meta($form_id);
        $quiz_fields = GFCommon::get_fields_by_type($form, array('quiz'));
        if (empty($quiz_fields)){
            GFFormSettings::page_header(__("Quiz Form Settings", "gravityformsquiz"));
            echo "<h3><span>" . __("Quiz Form Settings", "gravityformsquiz") . "</h3>";
            _e("This form doesn't have any quiz fields.");
            GFFormSettings::page_footer();
            return;
        }
        if(rgpost('gform_meta')) {

            // die if not posted from correct page
            check_admin_referer("gform_save_form_settings_{$form_id}", 'gform_save_form_settings');

            $updated_form = json_decode(rgpost('gform_meta'), true);
            $updated_form['fields'] = $form['fields'];

            require_once(GFCommon::get_base_path() . '/form_detail.php');
            $update_result = GFFormDetail::save_form_info($form_id, addslashes(json_encode($updated_form)));

            // update working form object with updated form object
            $form = $updated_form;
        }


        ?>
        <script type="text/javascript">
        <?php GFCommon::gf_global(); ?>
        <?php GFCommon::gf_vars(); ?>

        var form = <?php echo json_encode($form); ?>;
        var fieldSettings = [];
        </script>
        <?php
        GFFormSettings::page_header(__("Quiz Form Settings", "gravityformsquiz"));
        ?>
    <div class="gform_panel gform_panel_form_settings" id="form_settings">
        <h3><span><?php _e("Quiz Form Settings", "gravityformsquiz"); ?></span></h3>

        <form action="" method="post" id="gform_form_settings">
            <div id="gquiz-form-settings">
                <ul>
                    <li>
                        <input type="checkbox" id="gquiz-shuffle-fields"/> <label
                            for="gquiz-shuffle-fields"><?php _e("Shuffle quiz fields", "gravityformsquiz") ?> <?php gform_tooltip("gquiz_shuffle_fields") ?></label>
                    </li>
                    <li>
                        <input type="checkbox" id="gquiz-instant-feedback"/> <label
                            for="gquiz-instant-feedback"><?php _e("Instant feedback", "gravityformsquiz") ?> <?php gform_tooltip("gquiz_instant_feedback") ?></label>
                    </li>
                    <li>
                        <h4 class="gf_settings_subgroup_title"><?php _e("Grading", "gravityformsquiz"); ?></h4>

                        <div id="gquiz-grading-options">
                            <input type="radio" id="gquiz-grading-none" name="gquiz-grading" value="none"
                                   onclick="gquiz_toggle_grading_options('none')"/>
                            <label for="gquiz-grading-none" class="inline">
                                <?php _e("None", "gravityformsquiz"); ?>
                                <?php gform_tooltip("gquiz_grading_none") ?>
                            </label>
                            &nbsp;&nbsp;
                            <input type="radio" id="gquiz-grading-passfail" name="gquiz-grading" value="passfail"
                                   onclick="gquiz_toggle_grading_options('passfail')"/>
                            <label for="gquiz-grading-passfail" class="inline">
                                <?php _e("Pass/Fail", "gravityformsquiz"); ?>
                                <?php gform_tooltip("gquiz_grading_pass_fail") ?>
                            </label>
                            &nbsp;&nbsp;
                            <input type="radio" id="gquiz-grading-letter" name="gquiz-grading" value="letter"
                                   onclick="gquiz_toggle_grading_options('letter')"/>
                            <label for="gquiz-grading-letter" class="inline">
                                <?php _e("Letter", "gravityformsquiz"); ?>
                                <?php gform_tooltip("gquiz_grading_letter") ?>
                            </label>

                            <div id="gquiz-grading-pass-fail-container" style="margin-top:10px;display:none;">
                                <div id="gquiz-form-setting-pass-grade">
                                    <label for="gquiz-pass-mark" style="display:block;">
                                        <?php _e("Pass Percentage", "gravityformsquiz"); ?>
                                        <?php gform_tooltip("gquiz_pass_percentage") ?>
                                    </label>

                                    <div id="gquiz-form-setting-pass-grade-value">
                                        <input type="text" id="gquiz-pass-mark" class="gquiz-grade-value"
                                               value=""><span>%</span>
                                    </div>
                                </div>

                                <div id="gquiz-form-setting-pass-confirmation-message">
                                    <label for="gquiz-pass-confirmation-message"
                                           style="display:block;"><?php _e("Quiz Pass Confirmation", "gravityformsquiz"); ?>
                                        <?php gform_tooltip("gquiz_pass_confirmation") ?>
                                    </label>

                                    <div>
                                        <?php GFCommon::insert_variables($form["fields"], "gquiz-pass-confirmation-message"); ?>
                                    </div>
                                    <textarea id="gquiz-pass-confirmation-message"
                                              class="fieldwidth-3 fieldheight-1"></textarea>

                                    <div style="margin-top:5px;margin-left:1px;">
                                        <input type="checkbox" id="gquiz-pass-confirmation-message-disable-autoformatting"/>
                                        <label for="gquiz-pass-confirmation-message-disable-autoformatting"><?php _e("Disable Auto-formatting", "gravityformsquiz") ?> <?php gform_tooltip("form_confirmation_autoformat") ?></label>
                                    </div>
                                </div>
                                <br/>

                                <div id="gquiz-form-setting-fail-confirmation-message">
                                    <label for="gquiz-fail-confirmation-message" style="display:block;">
                                        <?php _e("Quiz Fail Confirmation", "gravityformsquiz"); ?>
                                        <?php gform_tooltip("gquiz_fail_confirmation") ?>
                                    </label>

                                    <div>
                                        <?php GFCommon::insert_variables($form["fields"], "gquiz-fail-confirmation-message"); ?>
                                    </div>
                                    <textarea id="gquiz-fail-confirmation-message"
                                              class="fieldwidth-3 fieldheight-1"></textarea>

                                    <div style="margin-top:5px;margin-left:1px;">
                                        <input type="checkbox" id="gquiz-fail-confirmation-message-disable-autoformatting"/>
                                        <label for="gquiz-fail-confirmation-message-disable-autoformatting"><?php _e("Disable Auto-formatting", "gravityformsquiz") ?> <?php gform_tooltip("form_confirmation_autoformat") ?></label>
                                    </div>
                                </div>
                            </div>

                            <div id="gquiz-grading-letter-container" style="margin-top:10px;display:none;">
                                <label for="gquiz-settings-grades-container" style="display:block;">
                                    <?php _e("Letter Grades", "gravityformsquiz"); ?>
                                    <?php gform_tooltip("gquiz_letter_grades") ?>
                                </label>

                                <div id="gquiz-settings-grades-container">
                                    <label class="gquiz-grades-header-label"><?php _e("Label", "gravityformsquiz") ?></label><label
                                        class="gquiz-grades-header-value"><?php _e("Percentage", "gravityformsquiz") ?></label>
                                    <ul id="gquiz-grades"></ul>
                                </div>
                                <br/>

                                <div id="gquiz-form-setting-letter-confirmation-message">
                                    <label for="gquiz-letter-confirmation-message" style="display:block;">
                                        <?php _e("Quiz Confirmation", "gravityformsquiz"); ?>
                                        <?php gform_tooltip("gquiz_letter_confirmation") ?>
                                    </label>

                                    <div>
                                        <?php GFCommon::insert_variables($form["fields"], "gquiz-letter-confirmation-message"); ?>
                                    </div>
                                    <textarea id="gquiz-letter-confirmation-message"
                                              class="fieldwidth-3 fieldheight-1"></textarea>

                                    <div style="margin-top:5px;margin-left:1px;">
                                        <input type="checkbox"
                                               id="gquiz-letter-confirmation-message-disable-autoformatting"/> <label
                                            for="gquiz-letter-confirmation-message-disable-autoformatting"><?php _e("Disable Auto-formatting", "gravityformsquiz") ?> <?php gform_tooltip("form_confirmation_autoformat") ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </li>


                </ul>
            </div>
            <?php if(GFCommon::current_user_can_any("gravityforms_edit_settings")){ ?>
            <br/><br/>
            <?php wp_nonce_field("gform_save_form_settings_{$form_id}", 'gform_save_form_settings'); ?>
            <input type="hidden" id="gform_meta" name="gform_meta" />
            <input type="button" id="gform_save_settings" name="gform_save_settings" value="Update Form Settings" class="button-primary gfbutton" onclick="SaveFormSettings();" />

            <?php } ?>
        </form>
    </div>
    <?php
        GFFormSettings::page_footer();

    }


}

