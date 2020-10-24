window.onload = init;

function init() {
    // When a select is changed, look for the assignments based on the course id
    // and display on the dropdown assignment select
    $('#id_runes_course').change(function() {
      $('#id_runes_assign').load('../mod/runestone/getter.php?courseid=' + $('#id_runes_course').val());
    });
}
