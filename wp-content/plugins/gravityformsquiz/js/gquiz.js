function gquizShowAnswer(radioInput) {
    var id = jQuery(radioInput).attr("id");
    var gform = gquizGetCurrentForm(radioInput);
    var label = jQuery(gform).find("label[for='" + id + "']");
    var allRadios = "input[name='" + radioInput.name + "']";
    jQuery(gform).find(allRadios).attr('disabled', 'disabled');
    jQuery(radioInput).removeAttr('disabled'); //lets us send the value
    var fieldId = radioInput.name.replace("input_", "");
    var correctValue = gquizAnswers[fieldId].correctValue;
    correctValue = base64_decode(correctValue);
    var isCorrect = !!(correctValue == radioInput.value);
    var correctInput = jQuery(gform).find("input[value='" + correctValue + "']");
    var correctInputId = jQuery(correctInput).attr("id");
    var correctLabel = jQuery(gform).find("label[for='" + correctInputId + "']");
    correctLabel.addClass("gquiz-correct-choice");
    var indicatorUrl = isCorrect ? gquizVars.correctIndicator : gquizVars.incorrectIndicator;
    var answerIndicator = "<img class='gquiz-indicator' src='" + indicatorUrl + "' />";
    jQuery(label).append(answerIndicator);
    var fieldContainer = jQuery(radioInput).closest(".ginput_container");
    var answerExplanation = gquizAnswers[fieldId].explanation;
    if (answerExplanation) {
        answerExplanation = '<div class="gquiz-answer-explanation">' + utf8_decode(base64_decode(answerExplanation)) + '</div>';
        fieldContainer.append(answerExplanation);
    }

}

function gquizGetCurrentForm(element) {
    var form = jQuery(element).closest("form");
    return form;
}
function gquizGetFormId(form) {
    var formId = jQuery(form).attr("id").replace("gform_", "");
    return formId;
}

jQuery(document).ready(function () {

    jQuery(".gquiz-field.gquiz-instant-feedback input[type='radio']:checked").each(function () {
        gquizShowAnswer(this);
    });
    jQuery(".gquiz-field.gquiz-instant-feedback input[type='radio']").change(function (e) {
        gquizShowAnswer(this);
    });

});

//two functions from phpjs.org
function base64_decode(data) {
    // http://kevin.vanzonneveld.net
    // +   original by: Tyler Akins (http://rumkin.com)
    // +   improved by: Thunder.m
    // +      input by: Aman Gupta
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   bugfixed by: Pellentesque Malesuada
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: base64_decode('S2V2aW4gdmFuIFpvbm5ldmVsZA==');
    // *     returns 1: 'Kevin van Zonneveld'
    // mozilla has this native
    // - but breaks in 2.0.0.12!
    if (typeof this.window['atob'] == 'function') {
        return atob(data);
    }
    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
        ac = 0,
        dec = "",
        tmp_arr = [];

    if (!data) {
        return data;
    }

    data += '';

    do { // unpack four hexets into three octets using index points in b64
        h1 = b64.indexOf(data.charAt(i++));
        h2 = b64.indexOf(data.charAt(i++));
        h3 = b64.indexOf(data.charAt(i++));
        h4 = b64.indexOf(data.charAt(i++));

        bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;

        o1 = bits >> 16 & 0xff;
        o2 = bits >> 8 & 0xff;
        o3 = bits & 0xff;

        if (h3 == 64) {
            tmp_arr[ac++] = String.fromCharCode(o1);
        } else if (h4 == 64) {
            tmp_arr[ac++] = String.fromCharCode(o1, o2);
        } else {
            tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
        }
    } while (i < data.length);

    dec = tmp_arr.join('');

    return dec;
}

function utf8_decode(str_data) {
    // http://kevin.vanzonneveld.net
    // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
    // +      input by: Aman Gupta
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Norman "zEh" Fuchs
    // +   bugfixed by: hitwork
    // +   bugfixed by: Onno Marsman
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: utf8_decode('Kevin van Zonneveld');
    // *     returns 1: 'Kevin van Zonneveld'
    var tmp_arr = [],
        i = 0,
        ac = 0,
        c1 = 0,
        c2 = 0,
        c3 = 0;

    str_data += '';

    while (i < str_data.length) {
        c1 = str_data.charCodeAt(i);
        if (c1 < 128) {
            tmp_arr[ac++] = String.fromCharCode(c1);
            i++;
        } else if (c1 > 191 && c1 < 224) {
            c2 = str_data.charCodeAt(i + 1);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
            i += 2;
        } else {
            c2 = str_data.charCodeAt(i + 1);
            c3 = str_data.charCodeAt(i + 2);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
        }
    }

    return tmp_arr.join('');
}
