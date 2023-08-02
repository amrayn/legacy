var Debug = {
	enabled : false,
	log : function(string, category) {
		if (Debug.enabled) {
			if (category === undefined) {
				category = "";
			} else {
				category = category + " :: ";
			}
			if (typeof console === "object" && typeof console.log === "function") {
				console.log(category + string);
			}
		}
	}
};