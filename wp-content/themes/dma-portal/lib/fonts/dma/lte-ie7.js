/* Use this script if you need to support IE 7 and IE 6. */

window.onload = function() {
	function addIcon(el, entity) {
		var html = el.innerHTML;
		el.innerHTML = '<span style="font-family: \'dma\'">' + entity + '</span>' + html;
	}
	var icons = {
			'icon-search' : '&#xe000;',
			'icon-cd' : '&#xe001;',
			'icon-trophy' : '&#xe002;',
			'icon-location' : '&#xe003;',
			'icon-heart' : '&#xe004;',
			'icon-user' : '&#xe005;',
			'icon-arrow-up' : '&#xe006;',
			'icon-arrow-left' : '&#xe007;',
			'icon-arrow-right' : '&#xe008;',
			'icon-arrow-down' : '&#xe009;',
			'icon-arrow-left-2' : '&#xe00a;',
			'icon-arrow-down-2' : '&#xe00b;',
			'icon-arrow-up-2' : '&#xe00c;',
			'icon-arrow-right-2' : '&#xe00d;',
			'icon-arrow-left-3' : '&#xe00e;',
			'icon-arrow-down-3' : '&#xe00f;',
			'icon-arrow-up-3' : '&#xe010;',
			'icon-arrow-right-3' : '&#xe011;',
			'icon-checkmark' : '&#xe012;',
			'icon-cross' : '&#xe013;',
			'icon-help' : '&#xe014;',
			'icon-warning' : '&#xe015;',
			'icon-bookmark' : '&#xe016;',
			'icon-lock' : '&#xe017;',
			'icon-record' : '&#xe019;',
			'icon-cross-2' : '&#xe01b;',
			'icon-badge' : '&#xe01c;',
			'icon-award' : '&#xe01d;',
			'icon-unchecked' : '&#xe01e;',
			'icon-checked' : '&#xe01f;',
			'icon-home' : '&#xe020;',
			'icon-navbar' : '&#xe01a;',
			'icon-dot' : '&#xe018;',
			'icon-pen' : '&#xe021;',
			'icon-friends' : '&#xe022;',
			'icon-dma' : '&#xe023;',
			'icon-location-large-hole' : '&#xe025;',
			'icon-location-large' : '&#xe024;',
			'icon-list' : '&#xe026;'
		},
		els = document.getElementsByTagName('*'),
		i, attr, html, c, el;
	for (i = 0; i < els.length; i += 1) {
		el = els[i];
		attr = el.getAttribute('data-icon');
		if (attr) {
			addIcon(el, attr);
		}
		c = el.className;
		c = c.match(/icon-[^\s'"]+/);
		if (c && icons[c[0]]) {
			addIcon(el, icons[c[0]]);
		}
	}
};
