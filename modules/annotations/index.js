/* Annotations list (modules/annotations/index.php). */

// to popup the calendar for choosing dates
function popCalendar(field) {
	var idate = document.chooseDate.annotation_show_date.value;
	window.open('index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + encodeURIComponent(idate),
		'calwin', 'width=280, height=250, scrollbars=no, status=no');
}

/**
*	@param string Input date in the format YYYYMMDD
*	@param string Formatted date
*/
function setCalendar(idate, fdate) {
	document.chooseDate.annotation_show_date.value = idate;
	document.chooseDate.show_date.value = fdate;
	if (document.pickOwner && document.pickOwner.show_date) {
		document.pickOwner.show_date.value = fdate;
	}
	document.chooseDate.submit();
}
