/**
 * Helpers for the macroprojects pages, called through js/dp-actions.js
 * (data-dp-* attributes written by dPaction()). Loaded on every m=macroprojects
 * page by CAppUI::loadJS(). Tabs shown inside other modules' pages define what
 * they need in their own nonce'd script.
 */

/**
 * Description tooltip of a project or macro-project row:
 * overlib(html, CAPTION, caption, CENTER).
 */
function macroprojectsTip(html, caption) {
	return overlib(html, CAPTION, caption, CENTER);
}
