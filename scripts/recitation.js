/**
 * recitation.js requires sm2-min.js
 */
$.data(document, 'soundManagerReady', false);
prepareRecitationForSmallDevices = function() {
	// For small screens we do this trick in order for recitation to work
	if (!ScreenSize.large() && $.data(document, 'soundManagerReady')) {
		$.data(document, 'soundManagerReadyForSmallDevices', true);
		soundManager.mute();
		startReciting(false);
		var smallDeviceRecitationTrickInterval = setInterval(function() {
			pause(false);
			restart(false);
			soundManager.unmute();
			updateMediaStatus("Ready!", true);
			clearInterval(smallDeviceRecitationTrickInterval);
		}, 1000);
	}
}
setupSoundManager = function() {
	soundManager.setup({
		debugMode: Debug !== undefined && Debug.enabled,
		waitForWindowLoad: true,
		url: '/scripts/swf/',
		flashVersion: 9,
		onready: function() {
			$.data(document, 'soundManagerReady', true);
			$("#btn-play").removeAttr("disabled");
			$("#btn-play").removeClass("play-grey").addClass("play");
			updateMediaStatus("Ready!", true);
		},
		ontimeout: function() {
			$("#btn-play").attr("disabled", "true");
			$("#btn-stop").attr("disabled", "true");
			$("#btn-play").removeClass("play").addClass("play-grey");
			$("#btn-stop").removeClass("stop").addClass("stop-grey");
			updateMediaStatus("Time out", true);
			closeAllTooltips();
		}
	});
}
$(document).ready(function() {
	setupSoundManager();
	$("#progress-bar").progressbar({
		value: false,
		change: function() {
			var val = $("#progress-bar").progressbar("value");
			$(".progress-label").text(val == false ? "Buffering" : val);
		},
		complete: function() {
			$(".progress-label").text("Complete!");
		}
	});
});

var playList = [];
var currentSound = null;

loadRecitations = function() {
	$("#btn-play").removeClass("pause").addClass("play");
	reciterChanged();
	prepareRecitationForSmallDevices();
}

verseRepeatChanged = function(v) {
	$("#verse-repeat-internal").val(v == undefined ? intVal("#verse-repeat-input") : v);
}

rangeRepeatChanged = function(v) {
	$("#range-repeat-internal").val(v == undefined ? intVal("#range-repeat-input") : v);
}

reciterChanged = function() {
	stop(false);
	for (var i = 0; i < playList.length; ++i) {
		soundManager.destroySound(playList[i].id);
	}
	if ($("#cbo-reciters option:selected").val() == undefined) {
		return;
	}
	const reciterObj = RECITERS[$("#cbo-reciters option:selected").val()];

	const reciterUrlPattern = reciterObj.urlPattern || '';

	if (!reciterUrlPattern) {
		console.error('Reciter [%s] URL pattern wrong', reciterObj.id);
		return;
	}

	var selectedSurah = intVal("#cbo-surah");
	playList = [];
	var zeroPaddedSurah = paddedNumber(selectedSurah, 3, '0');
	for (var i = 0; i <= totalVerses(); ++i) {
		var verseUrl = null;
		if (i == 0) {
		   if (selectedSurah != 1 && selectedSurah != 9) { // Fatiha has it and tawba should not
			   verseUrl = reciterObj.basmalaUrl;
				 if (!verseUrl) {
					 // pick up from fatiha first
					 verseUrl = reciterUrlPattern
					 		.replace(/%paddedSurah%/, '001')
		 			 		.replace(/%paddedSurahAyah%/, '001001')
				 }
		   }
		} else {
		   var zeroPaddedVerse = paddedNumber(i, 3, '0');
			 verseUrl = reciterUrlPattern
			 		.replace(/%paddedSurahAyah%/, `${zeroPaddedSurah}${zeroPaddedVerse}`)
			 		.replace(/%paddedSurah%/, zeroPaddedSurah)
					.replace(/%paddedAyah%/, zeroPaddedVerse);
		}
		if (verseUrl == null) {
		   continue;
		}
		var sound = soundManager.createSound({
			id: "verse-" + i,
			url: verseUrl,
			volume: 100,
			onbufferchange: function() {
				if (this.isBuffering) {
					//$("#seek-bar").css("display", "none");
					noMediaStatus(true);
					var bytes = (this.bytesLoaded / this.bytesTotal) * 100;
					var perc = bytes + "%";
					$("#progress-bar").progressbar("value", isNaN(bytes) ? false : perc);
					$("#progress-bar").progressbar("value", isNaN(bytes) ? false : perc);
				} else {
					updateMediaStatus("Buffering stopped", true);
				}
			},
			onload: function() {
				if (this.isLoading) {
					this.isLoading = false;
					this.durationSeconds = parseInt(this.duration / 1000);
				}
			}
		});
		sound.isLoading = false;
		sound.durationSeconds = 0;
		playList.push(sound);
	}
	updatePartMp3Link();
	updateMediaStatus("Stopped", true);
	updatePermaUrl();
}

var runnerWait = null;
var waitSecStatusUpdater = null;
var waitSecRangeStatusUpdater = null;
var previousAyahDuration = 0;
var previousAyah = 0;
resetCurrentPos = function(currentSound) {
	var verseStartPos = $("#verse-start-end-pos").slider("values")[0];
	var positiveOrZero = Math.max(verseStartPos, 0);
	// If selected position is more than total durations, then we start from zero
	// otherwise position selected.
	if (!$("#chk-mem-mode").is(":checked")) {
		// There is another condition where we start from zero that is when user has unchecked
		// memorization mode, so that user knows if it's starting from middle, why it's doing so.
		positiveOrZero = 0;
	}
	currentSound.startPos = (positiveOrZero > currentSound.durationSeconds ? 0 : positiveOrZero) * 1000;
	currentSound.setPosition(currentSound.startPos);
}

resetRangeSlider = function(options) {
	var minimum = 0;
	var maximum = 300;
	if (options != undefined) {
		minimum = options.minimum;
		maximum = options.maximum;
	}
	if ($("#verse-start-end-pos").slider("option", "min") == minimum && $("#verse-start-end-pos").slider("option", "max") == maximum) {
		return;
	}
	if (maximum > 0) {
		$("#verse-start-end-pos").slider("option", "min", minimum);
		$("#verse-start-end-pos").slider("option", "max", maximum);
		if (options.rangeOnly == undefined || options.rangeOnly == false) {
			$("#verse-start-end-pos").slider("option", "values", [minimum, maximum]);
		}
		$("#verse-start-end-pos-val").text(minimum + "-" + maximum + " sec");
		$("#verse-start-end-pos").slider("enable");
		$("#verse-start-end-pos-val").css("color", "#fff");
	}

	$($("#verse-start-end-pos > a")[0]).css("left", "0.5%");
}

loadCurrentSelection = function() {
	selection = intVal("#verse-selection-select");
	currentSound = soundManager.getSoundById("verse-" + selection);
	if (currentSound != null) {
		if (!currentSound.loaded) {
			currentSound.load();
		}
		// this is done on onload()
		//resetRangeSlider({"minimum" : 0, "maximum" : currentSound.durationSeconds, "rangeOnly" : true });
	}
}

play = function(verseSelection, verseRepeatWait) {
	if (currentSound != null && currentSound.playState == 1 && !currentSound.paused) {
		pause();
	} else if (currentSound != null && currentSound.playState == 1 && currentSound.paused) {
		resume();
	} else if (verseSelection != undefined && (currentSound == null || currentSound.playState == 0)
			&& runnerWait == null) {
		stop(false);
		var verseStart = intVal("#verse-range-from");
		var verseEnd = intVal("#verse-range-to");
		currentSound = soundManager.getSoundById("verse-" + verseSelection);
		if (currentSound != null) {
			if (!currentSound.loaded) {
				currentSound.isLoading = true;
				currentSound.load(); // Remember, this does not guarantee loading right away!
			}
			$("#btn-play").removeClass("play").addClass("pause");
			$("#btn-stop").removeAttr("disabled");
			$("#btn-stop").removeClass("stop-grey").addClass("stop");
			var waitSec = verseRepeatWait ? 0 : intVal("#verse-gap-input");
			if ($("#chk-wait-duration").is(":checked") && previousAyah != "verse-0") {
				waitSec += previousAyahDuration;
			}
			if (waitSec > 0) {
				updateMediaStatus("Waiting for [" + waitSec + " / " + waitSec + " sec]", true);
				if (waitSecStatusUpdater == null) {
					i = waitSec - 1;
					waitSecStatusUpdater = setInterval(function() {
						updateMediaStatus("Waiting for [" + i + " / " + waitSec + " sec]", true);
						i--;
						if (i <= 0) {
							window.clearInterval(waitSecStatusUpdater);
							waitSecStatusUpdater = null
						}
					}, 1000);
				}
			}
			runnerWait = setInterval(function() {
				if (typeof currentSound === "undefined") {
					stop();
					return;
				}
				// We try to reset the pos and everything but if sound is still loading
				// and hasn't finished yet (for slow connections or server being bad) we
				// use onload(success) to determine it
				resetRangeSlider({"minimum" : 0, "maximum" : currentSound.durationSeconds, "rangeOnly" : false });
				resetCurrentPos(currentSound);

				currentSound.play({
					onload: function(success) {
						if (success && this.isLoading) {
							this.isLoading = false;
							this.durationSeconds = parseInt(this.duration / 1000);
							resetRangeSlider({"minimum" : 0, "maximum" : this.durationSeconds, "rangeOnly" : false });
							//resetCurrentPos(this);
						} // else we display other stuffs
					},
					onfinish: function() {
						emitRecitationEvent("Play Finish");
						var nextItemToPlay = null;
						var reciteIndex = $("#verse-repeat-internal").val();
						reciteIndex = reciteIndex == "" ? 1 : reciteIndex;
						var selectedSurah = intVal("#cbo-surah");
						var recitingBismillah = verseSelection == 0 && (selectedSurah != 1 && selectedSurah != 9);
						var repeatingVerse = false;
						if (reciteIndex <= 1 || recitingBismillah) {
							nextItemToPlay = verseSelection + 1;
							$("#verse-repeat-internal").val(intVal("#verse-repeat-input"));
							repeatingVerse = false;
						} else {
							nextItemToPlay = verseSelection;
							$("#verse-repeat-internal").val(reciteIndex - 1);
							repeatingVerse = true;
						}
						previousAyah = currentSound.id;
						var startPos = 0;
						var maxDuration = currentSound.durationSeconds;
						if (currentSound.startPos) {
							startPos = (currentSound.startPos / 1000)
						}
						// We negate end pos
						if (currentSound.maxDuration) {
							maxDuration = (currentSound.maxDuration / 1000)
						}
						previousAyahDuration = maxDuration - startPos;
						if (nextItemToPlay <= verseEnd && (recitingBismillah || repeatingVerse || $("#chk-continuous").is(":checked"))) {
							if (nextItemToPlay != verseSelection) {
								$("#verse-selection-select").val(nextItemToPlay);
								verseSelectionChanged();
							}
							play(nextItemToPlay, false);
						} else {
							var reciteIndex = $("#range-repeat-internal").val();
							reciteIndex = reciteIndex == "" ? 1 : reciteIndex;
							if (reciteIndex <= 1) {
								stop(false);
							} else {
								stop(false);
								var waitRepeatRangeSec = intVal("#range-gap-input");
								if (waitRepeatRangeSec > 0) {
									updateMediaStatus("Waiting for [" + waitRepeatRangeSec + " / " + waitRepeatRangeSec + " sec]", true);
									if (waitSecStatusUpdater == null) {
										i = waitRepeatRangeSec - 1;
										waitSecRangeStatusUpdater = setInterval(function() {
											updateMediaStatus("Waiting for [" + i + " / " + waitRepeatRangeSec + " sec]", true);
											i--;
											if (i <= 0) {
												window.clearInterval(waitSecRangeStatusUpdater);
												waitSecRangeStatusUpdater = null
											}
										}, 1000);
									}
								}
								var rangeRunner = setInterval(function() {
									$("#verse-selection-select").val(verseStart);
									verseSelectionChanged();
									$("#range-repeat-internal").val(reciteIndex - 1);
									play(verseStart, false);
									window.clearInterval(rangeRunner);
									rangeRunner = null;
								}, waitRepeatRangeSec * 1000);
							}
						}
					},
					whileplaying: function() {
						if (typeof currentSound === "undefined") {
							return;
						}
						resetRangeSlider({"minimum" : 0, "maximum" : currentSound.durationSeconds, "rangeOnly" : true });
						var posSec = parseInt(this.position / 1000);
						var totalDuration = this.durationSeconds;
						updateMediaStatus("Reciting [" + posSec + " / " + totalDuration + "] sec", false);
						var maxDuration = $("#verse-start-end-pos").slider("values")[1] * 1000;
						this.maxDuration = maxDuration;
						if ($("#chk-mem-mode").is(":checked") && posSec < $("#verse-start-end-pos").slider("values")[0]) {
							// We check for chk-mem-mode to be checked so that user knows why recitation is resetting
							// half way through
							//
							// This part is only for starting at user's selected position. Ending is done later in
							// the same scope
							resetCurrentPos(currentSound);
						}
						$("#seek-bar").slider("option", "max", totalDuration);
						$("#seek-bar").slider("value", Math.min(maxDuration / 1000, posSec));
						if (posSec > 0) {
							$("#btn-restart").removeClass("restart-grey").addClass("restart");
							$("#btn-restart").removeAttr("disabled");
							$("#btn-restart-all").removeClass("restart-all-grey").addClass("restart-all");
							$("#btn-restart-all").removeAttr("disabled");
						} else {
							$("#btn-restart").removeClass("restart").addClass("restart-grey");
							$("#btn-restart").attr("disabled", "true");
							$("#btn-restart-all").removeClass("restart-all").addClass("restart-all-grey");
							$("#btn-restart-all").attr("disabled", "true");
						}
						$("#btn-stop").removeAttr("disabled");
						$("#btn-stop").removeClass("stop-grey").addClass("stop");
						if ($("#chk-mem-mode").is(":checked") && maxDuration > 0 && maxDuration > $("#verse-start-end-pos").slider("values")[0] * 1000 && posSec * 1000 > maxDuration ) {
							// Pre-mature end
							var thisRef = this;
							this._onfinish();
							thisRef.unload();
							$("#btn-restart").removeClass("restart").addClass("restart-grey");
							$("#btn-restart").attr("disabled", "true");
							$("#btn-restart-all").removeClass("restart-all").addClass("restart-all-grey");
							$("#btn-restart-all").attr("disabled", "true");
							$("#btn-stop").attr("disabled", "true");
							$("#btn-stop").removeClass("stop").addClass("stop-grey");
							closeAllTooltips();
						}
						if (ScreenSize.large() && $("#chk-continuous").is(":checked") && (totalDuration - posSec <= 5)) {
							var reciteIndex = $("#verse-repeat-internal").val();
							reciteIndex = reciteIndex == "" ? 1 : reciteIndex;
							var nextToLoad = reciteIndex <= 1 ? verseSelection + 1 : verseSelection;
							if (nextToLoad != verseSelection) {
								var sound = soundManager.getSoundById("verse-" + nextToLoad);
								if (sound != null && !sound.loaded) {
									sound.load();
									sound.isLoading = true;
								}
							}
						}
					}
				});
				window.clearInterval(runnerWait);
				runnerWait = null;
			}, waitSec * 1000);
		}
	}
}

pause = function(sendEvt = true) {
	if (currentSound != null && currentSound.playState == 1 && !currentSound.paused) {
		if (sendEvt) { 
			emitRecitationEvent("Pause");
		}
		currentSound.pause();
		$("#btn-play").removeClass("pause").addClass("play");
		updateMediaStatus("Paused", true);
		if (runnerWait != null) {
			window.clearInterval(runnerWait);
			runnerWait = null;
		}
	}
}

resume = function() {
	if (currentSound != null && currentSound.playState == 1 && currentSound.paused) {
		emitRecitationEvent("Resume");
		currentSound.resume();
		$("#btn-play").removeClass("play").addClass("pause");
		updateMediaStatus("Reciting", true);
	}
}

stop = function(sendEvent) {
	if ($.data(document, 'soundManagerReady')) {
		soundManager.stopAll();
		updateMediaStatus("Stopped", true);
	}
	if (sendEvent == undefined || sendEvent) {
		emitRecitationEvent("Stop");
	}
	$("#btn-play").removeClass("pause").addClass("play");
	$("#btn-restart").removeClass("restart").addClass("restart-grey");
	$("#btn-restart").attr("disabled", "true");
	$("#btn-restart-all").removeClass("restart-all").addClass("restart-all-grey");
	$("#btn-restart-all").attr("disabled", "true");
	$("#btn-stop").attr("disabled", "true");
	$("#btn-stop").removeClass("stop").addClass("stop-grey");
	if (runnerWait != null) {
		window.clearInterval(runnerWait);
		runnerWait = null;
	}
	$("#verse-repeat-internal").val(intVal("#verse-repeat-input"));
	$("#range-repeat-internal").val(intVal("#range-repeat-input"));
	// If user has selected memorization mode, then we change seek bar to reset back to user's selected
	// position otherwise we reset it to 0 so that user knows what's going on.
	var startUserPos = $("#chk-mem-mode").is(":checked") ? $("#verse-start-end-pos").slider("values")[0] : 0;
	var seekbarMax = $("#seek-bar").slider("option", "max");
	$("#seek-bar").slider("value", startUserPos > seekbarMax ? 0 : startUserPos);
	if (waitSecStatusUpdater != null) {
		window.clearInterval(waitSecStatusUpdater);
		waitSecStatusUpdater = null;
	}
	closeAllTooltips();
}

restart = function(sentEvt = true) {
	if (currentSound != null && currentSound.playState == 1) {
		resetCurrentPos(currentSound);
		$("#btn-restart").removeClass("restart").addClass("restart-grey");
		$("#btn-restart").attr("disabled", "true");
		closeAllTooltips();
	}
}

restartAll = function() {
	stop(false);
	var startVerse = intVal("#verse-range-from");
	$("#verse-selection-select").val(startVerse);
	verseSelectionChanged();
	play(startVerse, true);
	$("#btn-restart").removeClass("restart").addClass("restart-grey");
	$("#btn-restart-all").removeClass("restart-all").addClass("restart-all-grey");
	$("#btn-restart").attr("disabled", "true");
	$("#btn-restart-all").attr("disabled", "true");
	closeAllTooltips();
}

noMediaStatus = function(showprogressbar) {
	$("#status-bar").text("");
	$("#status-bar").css("display", "none");
	if (showprogressbar != undefined && showprogressbar) {
		$(".progress-bar-elems").show();
	}
}

updateMediaStatus = function(val, hideprogressbar) {
	$("#status-bar").text(val);
	$("#status-bar").css("display", "inline-block");
	if (hideprogressbar != undefined && hideprogressbar) {
		$(".progress-bar-elems").hide();
	}
}

startReciting = function(sendEvt = true) {
	var selection = intVal('#verse-selection-select');
	var selectedSurah = intVal("#cbo-surah");
	var allowedBismillah = selectedSurah != 1 && selectedSurah != 9;
	if (sendEvt) {
		emitRecitationEvent("Play");
	}
	var verseToRecite = allowedBismillah && selection == 1 ? 0 : selection;
	// Extra check to be on safe side
	if (!allowedBismillah && verseToRecite == 0) {
		verseToRecite = 1;
	}
	play(verseToRecite, true);
}

emitRecitationEvent = function(event) {
	Analytics.recitation(event, RECITERS[$("#cbo-reciters option:selected").val()].name, currentSound);
}
