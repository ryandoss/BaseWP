
//------------------ Choices -----------------

function QuizChoice( text, value, isCorrect){
    this.text = text;
    this.value = value ? value : text;
    this.isSelected = false;
    this.price = "";
    this.gquizIsCorrect = isCorrect;

}

function StartChangeQuizType(type) {

    var field = GetSelectedField();
    field["gquizFieldType"] = type;

    //reset answers
    jQuery.each(field.choices, function(index){
        field.choices[index].gquizIsCorrect = false;
    });

    return StartChangeInputType(type, field);
}

function GenerateQuizChoiceValue(field) {
    return 'gquiz' + field.id + 'xxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : r & 0x3 | 0x8;
            return v.toString(16);
        });
}

function gquiz_toggle_correct_choice(img, choiceIndex){

    var field = GetSelectedField();
    if (field.inputType == 'radio' || field.inputType == 'select'){
        for(var i=0; i<field.choices.length; i++){
        if ( field.choices[i].gquizIsCorrect )
            field.choices[i].gquizIsCorrect = false;
        }
        jQuery('img.gquiz-button-correct-choice').each(function(index){
            this.src = this.src.replace("correct1.png", "correct0.png");
        });
        field.choices[choiceIndex].gquizIsCorrect = true;
        img.src = img.src.replace("correct0.png", "correct1.png");
    } else if ( field.inputType == 'checkbox' ) {

        var is_active = img.src.indexOf("correct1.png") >=0
        if(is_active){
            img.src = img.src.replace("correct1.png", "correct0.png");
            jQuery(img).attr('title',gquiz_strings.defineAsCorrect).attr('alt', 'Not correct');
        }
        else{
            img.src = img.src.replace("correct0.png", "correct1.png");
            jQuery(img).attr('title',gquiz_strings.defineAsIncorrect).attr('alt', 'Correct');
        }

        var isCorrect = field.choices[choiceIndex].gquizIsCorrect;
        field.choices[choiceIndex].gquizIsCorrect = ! isCorrect;

    }

    UpdateFieldChoices(GetInputType(field));
    gquiz_maybe_display_choices_help(field);
}

function gquiz_maybe_display_choices_help(field){

    var display_help = true;
    for(var i=0; i<field.choices.length; i++){
        if ( field.choices[i].gquizIsCorrect )
            display_help = false;
    }
    if (display_help){

        jQuery('.gquiz-choices-help').fadeIn();
    } else {
        jQuery('.gquiz-choices-help').fadeOut();
    }

}



jQuery(document).bind("gform_load_field_choices", function(event, field) {

    if(field.type == "quiz"){
        jQuery('#gquiz-field-choices').html(GetFieldChoices(field));
        jQuery('#gquiz-field-choices').append('<div class="gquiz-choices-help" style="display:none">' + gquiz_strings.markAnAnswerAsCorrect + '</div>');
        gquiz_maybe_display_choices_help(field);
    }

});

//------------------ Field settings init -----------------
jQuery(document).bind("gform_load_field_settings", function(event, field, form) {

    if (field.type == 'quiz') {

        jQuery('#gquiz-randomize-quiz-choices').attr('checked', field.gquizEnableRandomizeQuizChoices ? true : false);
        jQuery("#gquiz-field-type").val(field["gquizFieldType"]);
        jQuery("#gquiz-question").val(field["label"]);
        jQuery("#gquiz-answer-explanation").val(field.gquizAnswerExplanation);
        if(field.gquizShowAnswerExplanation == undefined)
            field.gquizShowAnswerExplanation = false;
        var isShowExplanation = field.gquizShowAnswerExplanation;

        jQuery('#gquiz-show-answer-explanation').prop('checked', isShowExplanation);
        gquiz_toggle_answer_explanation(isShowExplanation);

        if (has_entry(field.id)) {
            jQuery("#gquiz-field-type").attr("disabled", true);
        } else {
            jQuery("#gquiz-field-type").removeAttr("disabled");
        }

    }
});

function gform_new_choice_quiz(field, choice) {
    if(field.type == "quiz"){
        choice["value"] = GenerateQuizChoiceValue(field);
        choice["gquizIsCorrect"] = false;
    }

    return choice;
}


//gform.addFilter( 'gform_append_field_choice_option_quiz', my_test_filter);
//function my_test_filter(str, field, choiceIndex){
function gform_append_field_choice_option_quiz(field, choiceIndex){

    var imagesUrl = gquizVars.imagesUrl;
    var inputType = GetInputType(field);
    var buttonFileName = field.choices[choiceIndex].gquizIsCorrect == true ? "/correct1.png" : "/correct0.png";

    str = "<img src='" + imagesUrl + buttonFileName + "' class='gquiz-button-correct-choice' title='" + gquiz_strings.toggleCorrectIncorrect + "' onclick=\"gquiz_toggle_correct_choice(this, '" + choiceIndex + "');\"/> ";
    return str;
}

function gquiz_toggle_answer_explanation(isShowExplanation){

    if(isShowExplanation){
        jQuery('.gquiz-setting-answer-explanation').show('slow');
    }
    else {
        jQuery('.gquiz-setting-answer-explanation').hide('slow');
    }
}

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

//------------------ Form settings -----------------

//for 1.7
//gform.addFilter('gform_before_update', my_gform_before_update)
//function my_gform_before_update(form){
function gform_before_update(form){

    if (jQuery("#gquiz-grading-options").length > 0) {
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
    }

    return form;
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

function SetDefaultValues_quiz(field) {

    field.gquizFieldType = "radio";
    field.label = "Untitled Quiz Field";
    field.inputType = "radio";
    field.inputs = null;
    field.enableChoiceValue = true;
    field.enablePrice = false;
    field.gquizEnableRandomizeQuizChoices = false;
    field.gquizShowAnswerExplanation = false;
    field.gquizAnswerExplanation = "";
    if (!field.choices) {
        field.choices = new Array(new QuizChoice(gquiz_strings.firstChoice, GenerateQuizChoiceValue(field), false), new QuizChoice(gquiz_strings.secondChoice, GenerateQuizChoiceValue(field),false), new QuizChoice(gquiz_strings.thirdChoice, GenerateQuizChoiceValue(field), false));
    }

    return field;
}

jQuery(document).bind("gform_load_form_settings", function(event, form) {

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

});

jQuery(document).bind("gform_field_deleted", function(event, form, fieldId) {
	//if there are no quiz fields left on the page then hide the quiz forms settings
	for(var i=0; i<form.fields.length; i++){
		if (form.fields[i].type == "quiz")
			return;
	}
	jQuery("#gquiz-form-settings").hide();
});
jQuery(document).bind("gform_field_added", function(event, form, field) {
	if (field.type == 'quiz')
		jQuery("#gquiz-form-settings").show();
});

jQuery(document).ready(function () {
    fieldSettings["quiz"] = ".gquiz-setting-field-type, .gquiz-setting-question, .gquiz-setting-choices, .gquiz-setting-show-answer-explanation,  .gquiz-setting-randomize-quiz-choices";

});
