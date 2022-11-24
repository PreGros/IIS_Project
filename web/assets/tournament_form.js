window.$ = window.jQuery = require('jquery'); //changed


window.addEventListener('load', () => {
    requireTeamMembers();
});

$('#tournament_create_form_participantType, #tournament_edit_form_participantType').on('change', (index, el) => {
    requireTeamMembers();
});

function requireTeamMembers(){
    var min = $('#tournament_create_form_minTeamMemberCount, #tournament_edit_form_minTeamMemberCount');
    var max = $('#tournament_create_form_maxTeamMemberCount, #tournament_edit_form_maxTeamMemberCount');
    var minLabel = $('[for=tournament_create_form_minTeamMemberCount], [for=tournament_edit_form_minTeamMemberCount]');
    var maxLabel = $('[for=tournament_create_form_maxTeamMemberCount], [for=tournament_edit_form_maxTeamMemberCount]');
    var minParent = min.parent('div');
    var maxParent = max.parent('div');
    if ($('#tournament_create_form_participantType, #tournament_edit_form_participantType').val() == 1){
        minParent.show();
        maxParent.show();
        minLabel.addClass('required');
        maxLabel.addClass('required');
        min.prop('required', true);   
        max.prop('required', true);
    }
    else{
        minParent.hide();
        maxParent.hide();
        minLabel.removeClass('required');
        maxLabel.removeClass('required');
        min.prop('required', false);
        max.prop('required', false);
    }
}
