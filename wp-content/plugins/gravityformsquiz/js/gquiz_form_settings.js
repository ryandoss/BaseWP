//------------------ Grades -----------------

function gquiz_toggle_grading_options(gradeOption){

    if (gradeOption == 'none'){
        jQuery('#gquiz-grading-pass-fail-container').fadeOut('fast');
        jQuery('#gquiz-grading-letter-container').fadeOut('fast');
    } else if (gradeOption == 'passfail') {
        jQuery('#gquiz-grading-letter-container').fadeOut('fast');
        jQuery('#gquiz-grading-pass-fail-container').fadeIn('fast');
    } else if (gradeOption == 'letter'){
        jQuery('#gquiz-grading-pass-fail-container').fadeOut('fast');
        jQuery('#gquiz-grading-letter-container').fadeIn('fast');
    }

}

function gquiz_Grade(text, value){
    this.text = text;
    this.value = value;
}

function gquiz_insert_grade(index){
    gquiz_update_grades_object();

    var gradeAbove = form.gquizGrades[index-1];
    var gradeBelow = form.gquizGrades[index];
    if ( gradeBelow == undefined )
        gradeBelowVal = 0;
    else
        gradeBelowVal = gradeBelow.value;
    var newValue = parseInt(gradeBelowVal) + parseInt( ( gradeAbove.value - gradeBelowVal ) / 2 );
    var g = new gquiz_Grade("", newValue);
    form.gquizGrades.splice(index, 0, g);
    jQuery('div#gquiz-settings-grades-container ul#gquiz-grades').html(gquiz_get_grades());
}

function gquiz_delete_grade(index){
    gquiz_update_grades_object();
    form.gquizGrades.splice(index, 1);
    jQuery('div#gquiz-settings-grades-container ul#gquiz-grades').html(gquiz_get_grades());
}

function guiz_move_grade(fromIndex, toIndex){
    gquiz_update_grades_object();
    var grade = form.gquizGrades[fromIndex];

    //deleting from old position
    form.gquizGrades.splice(fromIndex, 1);

    //inserting into new position
    form.gquizGrades.splice(toIndex, 0, grade);

    jQuery('div#gquiz-settings-grades-container ul#gquiz-grades').html(gquiz_get_grades());
}

function gquiz_get_grades(){

    var imagesUrl = gquizVars.imagesUrl;
    var str = "";
    for(var i=0; i<form.gquizGrades.length; i++){

        str += "<li data-index='" + i + "'>";
        str += "<img src='" + imagesUrl + "/arrow-handle.png' class='gquiz-grade-handle' alt='" + gquiz_strings.dragToReOrder + "' /> ";
        str += "<input type='text' id='gquiz-grade-text-" + i + "' value=\"" + form.gquizGrades[i].text.replace(/"/g, "&quot;") + "\"  class='gquiz-grade-input gquiz-grade-text' />&nbsp;&gt;=&nbsp;";
        str += "<input type='text' id='gquiz-grade-value-" + i + "' value=\"" + form.gquizGrades[i].value + "\" class='gquiz-grade-input gquiz-grade-value' >";
        str += "<img src='" + imagesUrl + "/add.png' class='gquiz-add-grade' title='" + gquiz_strings.addAnotherGrade + "' alt='" + gquiz_strings.addAnotherGrade + "' style='cursor:pointer; margin:0 3px;' onclick=\"gquiz_insert_grade(" + (i+1) + ");\" />";

        if(form.gquizGrades.length > 1 )
            str += "<img src='" + imagesUrl + "/remove.png' title='" + gquiz_strings.removeThisGrade + "' alt='" + gquiz_strings.removeThisGrade + "' class='gquiz-delete-grade' style='cursor:pointer;' onclick=\"gquiz_delete_grade(" + i + ");\" />";

        str += "</li>";

    }

    return str;
}

function gquiz_update_grades_object(){

    jQuery('ul#gquiz-grades li').each(function(index){
        var gquizText = jQuery(this).children('input.gquiz-grade-text').val();
        var gquizValue = jQuery(this).children('input.gquiz-grade-value').val();
        var i = jQuery(this).data("index");
        var g = new gquiz_Grade(gquizText, parseInt(gquizValue));
        form.gquizGrades[parseInt(i)]=g;
    });

}

jQuery(document).ready(function () {
    if(typeof form == 'undefined')
        return;

    //defaults
    if(form.gquizGrading == undefined)
        form.gquizGrading = 'none';
    if(form.gquizConfirmationFail == undefined)
        form.gquizConfirmationFail = gquiz_strings.gquizConfirmationFail;
    if(form.gquizConfirmationPass == undefined)
        form.gquizConfirmationPass = gquiz_strings.gquizConfirmationPass;
    if(form.gquizConfirmationLetter == undefined)
        form.gquizConfirmationLetter = gquiz_strings.gquizConfirmationLetter;
    if(form.gquizConfirmationPassAutoformatDisabled == undefined)
        form.gquizConfirmationPassAutoformatDisabled = false;
    if(form.gquizConfirmationFailAutoformatDisabled == undefined)
        form.gquizConfirmationFailAutoformatDisabled = false;
    if(form.gquizConfirmationLetterAutoformatDisabled == undefined)
        form.gquizConfirmationLetterAutoformatDisabled = false;

    if(form.gquizGrades == undefined || form.gquizGrades.length == 0)
        form.gquizGrades = new Array(
            new gquiz_Grade(gquiz_strings.gradeA, 90),
            new gquiz_Grade(gquiz_strings.gradeB, 80),
            new gquiz_Grade(gquiz_strings.gradeC, 70),
            new gquiz_Grade(gquiz_strings.gradeD, 60),
            new gquiz_Grade(gquiz_strings.gradeE, 0)
        );


    if(form.gquizPassMark == undefined)
        form.gquizPassMark = "50";

    if(form.gquizShuffleFields == undefined)
        form.gquizShuffleFields = false;
    if(form.gquizShuffleFields == undefined)
        form.gquizInstantFeedback = false;


    jQuery("div#gquiz-grading-options input#gquiz-grading-" + form.gquizGrading).prop("checked",true);
    gquiz_toggle_grading_options(form.gquizGrading);
    jQuery("#gquiz-pass-mark").val(form.gquizPassMark);
    jQuery("#gquiz-fail-confirmation-message").val(form.gquizConfirmationFail);
    jQuery("#gquiz-pass-confirmation-message").val(form.gquizConfirmationPass);
    jQuery("#gquiz-letter-confirmation-message").val(form.gquizConfirmationLetter);
    jQuery("#gquiz-pass-confirmation-message-disable-autoformatting").prop("checked", form.gquizConfirmationPassAutoformatDisabled);
    jQuery("#gquiz-fail-confirmation-message-disable-autoformatting").prop("checked", form.gquizConfirmationFailAutoformatDisabled);
    jQuery("#gquiz-letter-confirmation-message-disable-autoformatting").prop("checked", form.gquizConfirmationLetterAutoformatDisabled);

    jQuery("#gquiz-shuffle-fields").prop("checked", form.gquizShuffleFields);
    jQuery("#gquiz-instant-feedback").prop("checked", form.gquizInstantFeedback);

    jQuery('#gquiz-grades').html(gquiz_get_grades());

    jQuery(document).on("blur", 'input.gquiz-grade-value', (function () {
        var percent = jQuery(this).val();
        if (percent < 0 || isNaN(percent)) {
            jQuery(this).val(0);
        } else if (percent > 100) {
            jQuery(this).val(100);
        }
    })
    );
    jQuery(document).on("keypress", 'input.gquiz-grade-value', (function (event) {
        if (event.which == 27) {
            this.blur();
            return false;
        }
        if (event.which === 0 || event.which === 8)
            return true;
        if (event.which < 48 || event.which > 57) {
            event.preventDefault();
        }

    })

    );

    //enble sorting on the grades table
    jQuery('#gquiz-grades').sortable({
        axis  :'y',
        handle:'.gquiz-grade-handle',
        update:function (event, ui) {
            var fromIndex = ui.item.data("index");
            var toIndex = ui.item.index();
            guiz_move_grade(fromIndex, toIndex);
        }
    });

});

function SaveFormSettings() {

    form.gquizInstantFeedback = jQuery("#gquiz-instant-feedback").prop("checked");
    form.gquizShuffleFields = jQuery("#gquiz-shuffle-fields").prop("checked");
    form.gquizGrading = jQuery("#gquiz-grading-options input[name=gquiz-grading]:checked").val();
    form.gquizConfirmationLetter = jQuery("#gquiz-letter-confirmation-message").val();
    form.gquizConfirmationPass = jQuery("#gquiz-pass-confirmation-message").val();
    form.gquizConfirmationFail = jQuery("#gquiz-fail-confirmation-message").val();
    form.gquizConfirmationPassAutoformatDisabled = jQuery("#gquiz-pass-confirmation-message-disable-autoformatting").prop("checked");
    form.gquizConfirmationFailAutoformatDisabled = jQuery("#gquiz-fail-confirmation-message-disable-autoformatting").prop("checked");
    form.gquizConfirmationLetterAutoformatDisabled = jQuery("#gquiz-letter-confirmation-message-disable-autoformatting").prop("checked");
    form.gquizPassMark = jQuery("#gquiz-pass-mark").val();
    gquiz_update_grades_object();

    // set fields to empty array to avoid issues with post data being too long
    form.fields = [];
    var formJSON = jQuery.toJSON(form);
    jQuery("#gform_meta").val(formJSON);
    jQuery("form#gform_form_settings").submit();

}