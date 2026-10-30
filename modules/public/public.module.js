/**
 * Helpers for the public module's pop-up pages (selectors, calendar, date format).
 * Loaded on every m=public page by CAppUI::loadJS().
 *
 * The pages get the name of a function in the opener (?callback=setColor) or of a
 * form field in the parent (?field=editFrm.end_date). Instead of writing that name
 * into the script as code, they look it up here as a dotted property path.
 */

// Names that would turn a callback into code execution.
var dPpublicForbidden = { eval: 1, Function: 1, setTimeout: 1, setInterval: 1, execScript: 1 };

/**
 * Returns {owner: object, name: last segment} for the dotted path inside root,
 * or null when the path is not a plain property path or an intermediate part is missing.
 */
function dPpublicLookup(root, path) {
	path = String(path);
	if (root == null || !/^[A-Za-z_$][\w$]*(\.[A-Za-z_$][\w$]*)*$/.test(path)) {
		return null;
	}
	var parts = path.split('.');
	var owner = root;
	for (var i = 0; i < parts.length - 1; i++) {
		if (dPpublicForbidden[parts[i]]) {
			return null;
		}
		owner = owner[parts[i]];
		if (owner == null) {
			return null;
		}
	}
	var name = parts[parts.length - 1];
	if (dPpublicForbidden[name]) {
		return null;
	}
	return { owner: owner, name: name };
}

/**
 * Calls window.opener.<path>(args...) like the old inline window.opener.callback(...).
 * Throws when the function does not exist, as the old code did.
 */
function dPopenerCall(path, args) {
	var target = dPpublicLookup(window.opener, path);
	if (!target || typeof target.owner[target.name] !== 'function') {
		throw new Error('No callback function "' + path + '" in the opener window');
	}
	return target.owner[target.name].apply(target.owner, args || []);
}
