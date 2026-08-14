import $ from 'jquery';
import Templates from 'core/templates';
import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * HZ Grading Assistant - Moodle 5.2+ ES6 Module
 *
 * Beheert ND/NB en handmatige cijfer-overrides voor de assignment-rubric.
 * De override wordt NIET via het rubric-formulier van Moodle opgeslagen
 * (dat wordt door assign_grade_form::get_data() genegeerd omdat het geen
 * gedefinieerd element is), maar via een eigen webservice
 * (local_hzgrading_set_override). Het daadwerkelijk overschrijven van het
 * cijfer gebeurt server-side in \local_hzgrading\observer, nadat Moodle
 * het rubric-cijfer heeft opgeslagen.
 *
 * @module local_hzgrading/hzgrading
 */

let interfaceLoaded = false;
let cmid = 0;

/**
 * Stuurt de override naar de server. Fire-and-forget vanuit de UI-logica,
 * zodat de override altijd al opgeslagen is tegen de tijd dat de docent op
 * "Wijzigingen opslaan" klikt.
 *
 * @param {Number} userid
 * @param {String} type ND, NB, MANUAL of RESET
 * @param {String} value
 * @return {Promise}
 */
const persistOverride = (userid, type, value) => {
    if (!cmid || !userid) {
        // eslint-disable-next-line no-console
        console.error('local_hzgrading: cmid of userid ontbreekt, override wordt niet opgeslagen.');
        return Promise.resolve();
    }

    return Ajax.call([{
        methodname: 'local_hzgrading_set_override',
        args: {
            cmid: cmid,
            userid: userid,
            type: type,
            value: value === null || value === undefined ? '' : String(value),
        },
    }])[0].catch(Notification.exception);
};

export const init = (config) => {
    cmid = config && config.cmid ? config.cmid : 0;

    const isAssignPage = window.location.href.includes('mod/assign') ||
                         $('body').hasClass('path-mod-assign');

    if (!isAssignPage) {
        return;
    }

    const observer = new MutationObserver(() => {
        const gradeForm = $('[data-region="grade-panel"] form');
        if (gradeForm.length > 0 && !interfaceLoaded) {
            interfaceLoaded = true;
            injectUI(gradeForm);
        }
        if (gradeForm.length === 0) {
            interfaceLoaded = false;
        }
    });

    observer.observe(document.body, {childList: true, subtree: true});
};

const injectUI = (gradeForm) => {
    Templates.render('local_hzgrading/rubric_grading_form', {})
        .then((html, js) => {
            const wrapperHTML = '<div id="hzgrading-btns-wrapper" ' +
                'class="p-3 mb-3 border rounded shadow-sm" style="background-color: #f8f9fa;"></div>';
            const wrapper = $(wrapperHTML).html(html);

            const rubric = gradeForm.find('.gradingform_hzrubric, .gradingform_rubric');
            if (rubric.length > 0) {
                rubric.first().before(wrapper);
            } else {
                gradeForm.prepend(wrapper);
            }

            Templates.runTemplateJS(js);
            setupLogic(gradeForm);
        })
        .catch(() => {
            interfaceLoaded = false;
        });
};

/**
 * Haalt het id van de student op die op dit moment beoordeeld wordt.
 * Het gradeform bevat hiervoor een hidden 'userid' veld (nodig door Moodle
 * zelf om te weten wiens rubric-inzending het is), dus die is altijd
 * actueel, ook na het wisselen van student via de AJAX-navigatie.
 *
 * @param {jQuery} gradeForm
 * @return {Number}
 */
const getCurrentUserId = (gradeForm) => {
    const val = gradeForm.find('input[name="userid"]').val();
    return val ? parseInt(val, 10) : 0;
};

const setupLogic = (gradeForm) => {
    const cbActivate = $('#activate_override_grade');
    const overrideInput = $('#hz_override_grade');
    const ndCb = $('#nietdeelgenomen');
    const nbCb = $('#nietontvankelijk');
    const rubricWrapper = $('.gradingform_hzrubric, .gradingform_rubric');

    overrideInput.removeAttr('name').attr('type', 'text').prop('disabled', false).prop('readonly', true);

    const fillLowestRubricScores = () => {
        // Dit blijft nodig: Moodle's eigen rubric-validatie vereist dat elk
        // criterium is ingevuld voordat het formulier mag worden opgeslagen,
        // ook wanneer het uiteindelijke cijfer straks door de override wordt
        // vervangen.
        gradeForm.find('tr.criterion').each(function() {
            if ($(this).find('input[type="radio"]:checked').length === 0) {
                const firstRadio = $(this).find('input[type="radio"]').first();
                if (firstRadio.length > 0) {
                    firstRadio.prop('checked', true);
                    firstRadio.closest('.level').addClass('checked');
                }
            }
        });
    };

    const updateData = (status) => {
        cbActivate.prop('disabled', false);
        overrideInput.prop('readonly', !cbActivate.is(':checked'));
        rubricWrapper.css('opacity', '1');

        const userid = getCurrentUserId(gradeForm);

        if (status === 'ND' || status === 'NB') {
            cbActivate.prop('checked', false).prop('disabled', true);
            overrideInput.prop('readonly', true);
            rubricWrapper.css('opacity', '0.5');
            fillLowestRubricScores();

            const val = status === 'ND' ? '0.1' : '0.2';
            overrideInput.val(val);
            persistOverride(userid, status, val);

        } else if (status === 'MANUAL') {
            cbActivate.prop('checked', true);
            overrideInput.prop('readonly', false);
            // We raken de rubric hier NIET aan! Docent vult hem zelf in.

            const rawVal = overrideInput.val() ? overrideInput.val().replace(',', '.') : '';
            overrideInput.val(rawVal);
            if (rawVal !== '') {
                persistOverride(userid, 'MANUAL', rawVal);
            } else {
                persistOverride(userid, 'RESET', '');
            }

        } else {
            cbActivate.prop('checked', false);
            overrideInput.prop('readonly', true);
            overrideInput.val('');
            persistOverride(userid, 'RESET', '');
        }
        gradeForm.trigger('change');
    };

    ndCb.on('change', function() { updateData($(this).is(':checked') ? 'ND' : 'RESET'); });
    nbCb.on('change', function() { updateData($(this).is(':checked') ? 'NB' : 'RESET'); });
    cbActivate.on('change', function() {
        if ($(this).is(':checked')) {
            ndCb.prop('checked', false);
            nbCb.prop('checked', false);
            updateData('MANUAL');
            overrideInput.focus();
        } else {
            updateData('RESET');
        }
    });

    // Debounce: bij elke toetsaanslag opslaan is onnodig zwaar; wacht tot
    // de docent even stopt met typen.
    let debounceTimer = null;
    overrideInput.on('input', function() {
        const val = $(this).val().replace(',', '.');
        $(this).val(val);
        gradeForm.trigger('change');

        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => {
            const userid = getCurrentUserId(gradeForm);
            if (val !== '') {
                persistOverride(userid, 'MANUAL', val);
            } else {
                persistOverride(userid, 'RESET', '');
            }
        }, 400);
    });

    // Overrideveld voorinvullen op basis van wat er al bekend was (bv. na
    // wisselen van student). Dit leest alleen de UI-status, niet meer de
    // hidden fields, want die bestaan niet meer.
    const currentVal = overrideInput.val();
    if (currentVal === '0.1' || currentVal === '0,1') {
        ndCb.prop('checked', true);
        rubricWrapper.css('opacity', '0.5');
        overrideInput.val('0.1');
    } else if (currentVal === '0.2' || currentVal === '0,2') {
        nbCb.prop('checked', true);
        rubricWrapper.css('opacity', '0.5');
        overrideInput.val('0.2');
    } else if (currentVal !== '' && currentVal !== null && currentVal !== undefined) {
        cbActivate.prop('checked', true);
        overrideInput.prop('readonly', false);
        overrideInput.val(currentVal);
    }
};
