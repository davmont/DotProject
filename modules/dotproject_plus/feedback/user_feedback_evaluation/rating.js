/**
 * Star rating of an instructional feedback (built in do_show_feedback.php).
 */
function saveRatingFeedbackEvaluation(feedbackId) {
    var grade = $('input[name=star]:checked').val();
    $.post('index.php?m=dotproject_plus', {
        dosql: 'do_save_feedback_student_evaluation',
        feedback_id: String(feedbackId),
        grade: grade,
        csrf_token: $('meta[name=csrf-token]').attr('content')
    });
}
