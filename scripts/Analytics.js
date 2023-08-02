var Analytics = {
	enabled: true,
	pageView: function() {
		if (Analytics.enabled == false) {
			Debug.log("Analytics disabled [" + location.href + "]");
			return;
		}
		ga('send', 'pageview');
		Debug.log("Analytics :: PageView [" + location.href + "]");

		if(typeof mixpanel !== 'undefined') {
			mixpanel.track('Page View', { 'Event Source': 'Manual Legacy', 'Page Title': document.title, 'Site Context': 'quran', 'Page Context': 'Legacy' });
		}
	},
	send : function(category, action, label, value, callback, mixpanelData = {}) {
		if (Analytics.enabled == false) {
			Debug.log("Analytics disabled [" + category + " [" + action + "] [" + label + "]" + (value != 0 ? " [" + value + "]" : "") + "]");
			return;
		}
		if (label != undefined && typeof label != "string") {
			label = String(label);
		}
		if (value == undefined) {
			value = 0;
		}
		if (callback == undefined) {
			callback = function() {};
		}
		if (typeof ga === "function") {
			ga('send', 'event', {
			   'eventCategory': category,
			   'eventAction': action,
			   'eventLabel': label,
			   'eventValue': value,
			   'hitCallback': callback
			});
			Debug.log(category + " [" + action + "] [" + label + "]" + (value != 0 ? " [" + value + "]" : ""), "Analytics");
		}
		if(typeof mixpanel !== 'undefined') {
			mixpanel.track(category, { action, label, 'Page Title': document.title, 'Site Context': 'quran', 'Page Context': 'Legacy', ...mixpanelData });
		}
	},
	quran : function(event, ref) {
		Analytics.send('Quran', event, ref);
	},
	recitation : function(event, reciter, currentSound) {
		const mixpanelData = {};
		if (currentSound) {
			mixpanelData['Recitation URL'] = currentSound.url;
			mixpanelData['Duration'] = currentSound.durationSeconds + ' sec';
		} else {
			mixpanelData['Recitation Status'] = 'Sound not ready';
		}
		Analytics.send('Recitation', event, reciter, 0, () => {}, mixpanelData);
	},
	resource: function(url, callback) {
		Analytics.send('Resource', url, undefined, undefined, callback);
	},
	audio: function(title) {
		Analytics.send('Audio', title);
	},
	book: function(title) {
		Analytics.send('Book', title);
	},
	article: function(title) {
		Analytics.send('Article', title);
	},
	hadith : function(ref) {
		Analytics.send('Hadith', 'View');
	},
	hadithArabic : function(ref) {
		Analytics.send('Hadith', 'Open_Arabic_Tab');
	},
	userAction : function(actionName) {
		Analytics.send("User Action", actionName);
	},
    share : function(sharedOn, url) {
        Analytics.send('Share', sharedOn, url);
    },
    search : function(query, core, page) {
        Analytics.send('Search', core, query + ":" + page);
    },
	time : function(action, timeTakenSecond) {
		// Google analytics does not allow 0.xx values hence we send ms values
		//var timeTakenMs = timeTakenSecond * 1000;
		//Analytics.send('LoadTime', action, location.pathname + location.search, timeTakenMs);
	},
	signin : function(origin, displayName) {
		Analytics.send('SignIn', origin, displayName);
	}
};
