var User = {
	Permissions : {
		canEditHadith : false
	},
	Preferences : {
	},
	isSuperUser : false,
	Notification : {
		notifyIcon : new Favico({
			animation:'none',
			bgColor : '#000',
			textColor : '#fff',
		}),
		unread : 0,
		read : 0,
		maxItems : 20,
		markRead : function(obj, callback) {
			if (obj.hasClass("notification-read")) {
				if (typeof callback !== "undefined") {
					callback();
				}
				return;
			}
			NProgress.start();
			Utils.getToken(function(token) {
				return;
				fastIdentifier = obj.attr("fast-identifier");

			});
		},
		markUnread : function(obj, callback) {
			if (obj.hasClass("notification-read")) {
				if (typeof callback !== "undefined") {
					callback();
				}
				return;
			}
			NProgress.start();
			Utils.getToken(function(token) {
				fastIdentifier = obj.attr("fast-identifier");
				$.ajax({
					url: '/svc/notifications' + __svcargs + 'action=mark-unread&fi=' + fastIdentifier + '&token=' + token,
					type: 'GET'
				}).always(function(resp) {
					NProgress.done();
					if (resp.error) {
						Debug.log(resp.message);
					} else {
						obj.removeClass("notification-read").addClass("notification-unread");
						User.Notification.unread--;
						User.Notification.read++;
						User.Notification.renderCount();
						if (typeof callback !== "undefined") {
							callback();
						}
					}
				});
			});
		},
		remove : function(obj) {
			obj.addClass('small-spinner');
			NProgress.start();
			Utils.getToken(function(token) {
				fastIdentifier = obj.prev().attr("fast-identifier");
				$.ajax({
					url: '/svc/notifications' + __svcargs + 'action=remove&fi=' + fastIdentifier + '&token=' + token,
					type: 'GET'
				}).always(function(resp) {
					if (resp.error) {
						Debug.log(resp.message);
					} else {
						var notificationObj = $("div[fast-identifier='" + fastIdentifier + "']");
						if (notificationObj.hasClass("notification-unread")) {
							User.Notification.unread--;
						} else if (notificationObj.hasClass("notification-read")) {
							User.Notification.read--;
						}
						notificationObj.parent().remove();
						User.Notification.renderCount();
					}
					NProgress.done();
				});
			});
		},
		refresh : function() {

		},
		markAllRead : function() {
			if ($(".notification-unread").length == 0) {
				return;
			}
			NProgress.start();
			Utils.getToken(function(token) {
				$.ajax({
					url: '/svc/notifications' + __svcargs + 'action=mark-all-read&token=' + token,
					type: 'GET'
				}).always(function(resp) {
					if (resp.error) {
						Debug.log(resp.message);
					} else {
						$(".notification-unread").removeClass("notification-unread").addClass("notification-read");
						User.Notification.read += User.Notification.unread;
						User.Notification.unread = 0;
						User.Notification.renderCount();
					}
					NProgress.done();
				});
			});
		},
		removeAllRead : function() {
			if ($(".notification-read").length == 0) {
				return;
			}
			NProgress.start();
			Utils.getToken(function(token) {
				$.ajax({
					url: '/svc/notifications' + __svcargs + 'action=remove-all-read&token=' + token,
					type: 'GET'
				}).always(function(resp) {
					if (resp.error) {
						Debug.log(resp.message);
					} else {
						$(".notification-read,.remove-all-read-icon").remove();
						User.Notification.read = 0;
						User.Notification.renderCount();
					}
					NProgress.done();
				});
			});
		},
		addToList : function(notification) {
			var squashedDetails = notification.details.length > 150 ? notification.details.substr(0, 150) + " ..." : notification.details;
			$("#user-bar .notification-list").append('\
			<div style="position: relative;"><div fast-identifier="' + notification.fastIdentifier + '" href="/notifications/view?id=' + notification.fastIdentifier + '" class="notification-item ' + (notification.unread ? "notification-unread" : "notification-read") + '">\
		  		<div class="notification-title">' + notification.title + '</div>\
				<div class="notification-detail"><pre style="margin: 0px;white-space: pre-wrap;">' + squashedDetails + '</pre></div>\
				<div class="notification-type notification-type-' + notification.typeclass + '"></div>\
				<div class="notification-time">' + notification.time + '</div>\
			</div><a class="link-small-icon fa fa-close remove-notification" notooltip title="Permanently Delete" rel="nofollow"></a></div>\
			');
		},
		renderCount : function() {
			// Use user-bar selector to deal when you are on notification page
			var counter = $("#u-notification-icon > sup");
			var totalUnread = User.Notification.unread;
			counter.text(totalUnread > 0 ? totalUnread : "");
			if (totalUnread > 0) {
				$(".mark-all-read-icon").show();
				$(".notification-more-avail").remove();
				counter.removeAttr("title");
				if (totalUnread > User.Notification.maxItems) {
					counter.text(User.Notification.maxItems + "+");
					counter.css("width", "33px");
					counter.attr("title", totalUnread + " unread notifications");
					$( "<a href='/notifications' class='local notification-more-avail' rel='nofollow'>" + (totalUnread - 20) + " more unread notifications</a>").insertAfter("#user-bar .notification-item.notification-unread:last")
				} else if (totalUnread > 9) {
					counter.css("width", "22px");
				} else {
					counter.css("width", "13px");
				}
				User.Notification.notifyIcon.badge(counter.text());
			} else {
				$(".mark-all-read-icon").hide();
				User.Notification.notifyIcon.reset();
			}
			if (User.Notification.unread === 0 && User.Notification.read === 0) {
				$("#user-bar .notification-list").html("<div class='notification-none'>You have no notification.</div>");
			}
		},
		refreshFull : function() {

		},

		refreshRead : function() {

		}
	},
	addUserBar : function() {
//		if ($("#user-bar").length > 0) {
//			return;
//		}
		var height = 30;
		var relativeHeightPx = (height + 1) + "px";
		$("#body-head-large").css("top", relativeHeightPx);
		$("#body-head-medium").css("top", relativeHeightPx);
		$("#body-head-small").css("top", relativeHeightPx);
		$("#header-bar").css("top", relativeHeightPx);
		var userBar = $("<div></div>").attr("id", "user-bar").css({
			//"position" : "absolute",
			"height" : height
		});
		$("#body-head").prepend(userBar);
		$("#header-bar").prepend(userBar);
		$("#user-bar").append("<div style='clear:both'></div>");
		User.refreshUI();
		return userBar;
	},
	refreshUI : function() {
	/*	if ($("#user-bar").length == 0) {
			return;
		}
		*/
		$("#user-bar>div").remove();
		var usernameTopLink = $("<div></div>").attr({
			"id" : "username-top-link",
			"class" : "username-top-link icon"
		});
		var notificationIcon = $("<div></div>").attr({
			"id" : "u-notification-icon",
			"class" : "notification-icon fa fa-globe icon"
		});
		var salahIcon = $("<div></div>").attr({
			"id" : "u-salah-icon",
			"class" : "salah-icon fa fa-moon-o icon"
		});
		notificationIcon.html("<sup></sup>");
		var menuItems = [];
		//menuItems.push("<li><a href='/notifications/' class='local' rel='nofollow'><i class='fa fa-globe'></i> Notifications</a></li>");
    menuItems.push("<li><a href='/bookmarks' class='local' rel='nofollow'><i class='fa fa-bookmark'></i> Bookmarks</a></li>");
    menuItems.push("<li><a href='/memorization' class='local' rel='nofollow'><i class='fa fa-lightbulb-o'></i> Memorization</a></li>");
    menuItems.push("<li><a href='/revision' class='local' rel='nofollow'><i class='fa fa-calendar'></i> Revision</a></li>");
//    menuItems.push("<li><a href='/settings/' class='local' rel='nofollow'><i class='fa fa-gear'></i> Settings</a></li>");
//		menuItems.push("<li><a href='/signout/?redr=" + location.href + "' class='transition link-signout' rel='nofollow'><i class='fa fa-sign-out'></i> Sign out</a></li>");

		var userMenu = $("<div></div>").attr({
			"id" : "u-usermenu",
			"class" : "usermenu dropdownmenu"
		}).html("<ul>" + menuItems.join("") + "</ul>");

		var notificationMenu = $("<div></div>").attr({
			"id" : "u-notification-menu",
			"class" : "notification-menu dropdownmenu"
		}).html("<div id='notification-head'><span class='title'>Notifications</span><span class='notif-update-icon'>Update</span><span class='mark-all-read-icon'>Mark all as read</span></div><div style='clear:both;display:none;height:0;'></div><div class='notification-list'></div><div id='notification-foot'><a href='/notifications/' class='local read-all-icon' rel='nofollow'>Read all</span></div>");

		var salahMenu = $("<div></div>").attr({
			"id" : "u-salah-menu",
			"class" : "salah-menu dropdownmenu"
		}).html("<div id='salah-head'><span class='title'>Salah Times</span><div class='salah-times-list'></div></div>");

		usernameTopLink.html('My Account' + " <div class='caret'></div> <i class='fa fa-user'></i>");
		$("#user-bar").append(usernameTopLink);
		//$("#user-bar").append(notificationIcon);
		//$("#user-bar").append(salahIcon);
		$("#user-bar").append(userMenu);
		//$("#user-bar").append(notificationMenu);
		//$("#user-bar").append(salahMenu);

		bindMenu("#username-top-link", "#u-usermenu", ".username-top-link", ".usermenu", "user-bar-icon-hover");
		bindMenu("#u-notification-icon", "#u-notification-menu", ".notification-icon", ".notification-menu", "user-bar-icon-hover");
		bindMenu("#u-salah-icon", "#u-salah-menu", ".salah-icon", ".salah-menu", "user-bar-icon-hover");
		updateUserPrayerMenu();
		// Following causes too many calls :(
		User.Notification.refresh();
		if (User.Preferences.manualUpdateNofication) {
			$('.notif-update-icon').show();
		} else {
			$(".notif-update-icon").hide();
		}
		$(document).on("click", ".notif-update-icon", function(e) {
			User.Notification.refresh();
		});
		$(document).on("click", "#u-notification-icon", function() {
			setTimeout(User.Notification.markAllRead, 3000);
		});
		$(document).on("click", ".notification-item", function(e) {
			obj = $(this);
			href = obj.attr("href");
			obj.prepend("<i class='fa fa-spin fa-circle-o-notch marking-read'></i>");
			User.Notification.markRead(obj, function() {
				/*if (e.which != 2 && obj.attr('target') != '_blank') {
					e.preventDefault();
					if (href.indexOf("/") == 0) {
						Utils.ajaxPageLoad(href, true);
					} else {
						Utils.transitionPageLoad(href);
					}
				}*/
				Utils.fastPageLoad("/notifications/view?id=" + obj.attr("fast-identifier"));
				obj.find(".marking-read").remove();
			});
			return (e.which == 2 || obj.attr('target') == '_blank');
		});
		$(document).on("click", ".mark-all-read-icon", function() {
			User.Notification.markAllRead();
			$("body").click(); // Hide menu
		});
		$(document).on("click", ".remove-all-read-icon", function() {
			User.Notification.removeAllRead();
			$("body").click(); // Hide menu
		});
		$(document).on("click", ".remove-notification", function() {
			User.Notification.remove($(this));
			$("body").click(); // Hide menu
		});

	}
}

$(document).ready(function() { User.addUserBar(); })
