/*
 * dp-grey-theme tab switching: swaps the tab images as well as the panels.
 * Loaded after js/collapse.js, whose show_tab()/hide_tabs() call these hooks.
 * (Was an inline <script> in header.php.)
 */
var GT_IMAGES = './style/dp-grey-theme/images/';
function gt_hide_tabs() {
	var tabs = document.getElementsByTagName('td');
	var i;
	for (i = 0; i < tabs.length; i++) {
		if (tabs[i].className == 'tabon')
			tabs[i].className = 'taboff';
	}
	var divs = document.getElementsByTagName('div');
	for (i = 0; i < divs.length; i++) {
		if (divs[i].className == 'tab')
			divs[i].style.display = 'none';
	}
	var imgs = document.getElementsByTagName('img');
	for (i = 0; i < imgs.length; i++) {
		if (imgs[i].id) {
			if (imgs[i].id.substr(0,8) == 'lefttab_')
				imgs[i].src = GT_IMAGES + 'bar_top_left.gif';
			else if (imgs[i].id.substr(0,9) == 'righttab_')
				imgs[i].src = GT_IMAGES + 'bar_top_right.gif';
		}
	}
}
function gt_show_tab(i) {
	var tab = document.getElementById('tab_' + i);
	tab.style.display = 'block';
	tab = document.getElementById('toptab_' + i);
	tab.className = 'tabon';
	var img = document.getElementById('lefttab_' + i);
	img.src = GT_IMAGES + 'bar_top_Selectedleft.gif';
	img = document.getElementById('righttab_' + i);
	img.src = GT_IMAGES + 'bar_top_Selectedright.gif';
}
hide_tab_function = gt_hide_tabs;
show_tab_function = gt_show_tab;
