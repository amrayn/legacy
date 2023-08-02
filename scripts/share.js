    shareOnTwitter = function(text, quran, hadith) {
        if (hadith == undefined) hadith = false;
        if (quran == undefined) quran = false;
        var encodedURL = encodeURIComponent(location.href);
        var EXTRA_CHARS = 7 + (hadith ? 8 : 0); // dots, dash and spaces [#Hadith]
        var LIMIT = 140 - location.href.length - EXTRA_CHARS;
        var textToShare = (text == undefined ? encodeURIComponent(document.title) : text);
        var hasDots = textToShare.length > LIMIT;
        var status = textToShare.substr(0, LIMIT);
        if (hasDots) {
            status += " ..";
        }
        status += " - " + encodedURL;
        if (hadith) {
            status += encodeURIComponent(" #Hadith");
        } else if (quran) {
		        status += encodeURIComponent(" #Quran");
		}
        window.open("https://twitter.com/home?status=" + status, "", "toolbar=0,status=0,width=626,height=436");
        setTimeout(function() {
            Analytics.share("Twitter", location.href);
        }, 1000);
        return false;
    }

    shareOnLinkedIn = function() {
        window.open("https://www.linkedin.com/cws/share?url=" + encodeURIComponent(location.href) + "&original_referer="
                + encodeURIComponent(location.href) + "&token=&isFramed=true&lang=en_US&_ts=1416805766418.8633", "", "toolbar=0,status=0,width=600,height=429");
        setTimeout(function() {
            Analytics.share("LinkedIn", location.href);
        }, 1000);
        return false;
    }

    shareViaEmail = function(description) {
		var descriptionParam = description !== undefined ? description : "As-salāmu ʿalaykum " + newLineForEmail() + document.title + " " + newLineForEmail() + location.href;
		descriptionParam = encodeURIComponent(descriptionParam.substr(0, 1500) + "...");
        setTimeout(function() {
            Analytics.share("Email", location.href);
	        location.href = "mailto:?subject=" + encodeURIComponent(document.title) + "&body=" + descriptionParam, "";
        }, 1000);
        return false;
    }

    shareOnGooglePlus = function() {
        window.open("https://plus.google.com/share?url=" + encodeURIComponent(location.href), "",
                "menubar=no,toolbar=no,status=no,resizable=yes,scrollbars=yes,width=626,height=600");
        setTimeout(function() {
            Analytics.share("Google+", location.href);
        }, 1000);
        return false;
    }

    shareOnFacebook = function() {
        window.open("https://www.facebook.com/sharer.php?u=" + encodeURIComponent(location.href) + "&t="
                + encodeURIComponent(document.title), "sharer", "toolbar=0,status=0,width=626,height=436");
        setTimeout(function() {
            Analytics.share("Facebook", location.href);
        }, 1000);
        return false;
    }
    shareOnPinterest = function(description, mediaUrl) {
		var mediaParam = mediaUrl !== undefined ? "&media=" + encodeURIComponent(mediaUrl) : "";
		if (description.length > 50) {
			description = description.substr(0, 50) + "...";
		}
        window.open("https://pinterest.com/pin/create/button/?url=" + encodeURIComponent(location.href) + "&description="
                + encodeURIComponent(description) + mediaParam, "", "toolbar=0,status=0,width=700,height=500");
        setTimeout(function() {
            Analytics.share("Pinterest", location.href);
        }, 1000);
        return false;
    }

    newLineForEmail = function() {
		return escape("\n\n");
	}

    donotremovethisfunction = function() { // something with invisible character
		return "";
	}
