"use strict";

var KTProfile = function () {
	var avatar;
	var offcanvas;

	var _initAside = function () {
		offcanvas = new KTOffcanvas('kt_profile_aside', {
			overlay: true,
			baseClass: 'offcanvas-mobile',
			toggleBy: 'kt_subheader_mobile_toggle'
		});
	}

	var _initForm = function () {
		avatar = new KTImageInput('kt_profile_avatar');
	}

	return {
		init: function () {
			_initAside();
			_initForm();
		}
	};
}();

jQuery(document).ready(function () {
	KTProfile.init();
});
