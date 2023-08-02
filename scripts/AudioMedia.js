AudioMedia = {
  isReady: false,
  isYoutube: false,
  isVideo: false,
  isSoundCloud: false,
  isDailyMotion: false,
  isVimeo: false,
  isUsingSM2: false,
  mediaObj: null,
  paused: true, // Only for soundCloud because it doesn't support direct getting of value
  currentPos: -1, // Only for callbacks
  duration: -1, // Only for callbacks
  endingCallback: null,
  onfinish: function() {
    if ($.isFunction(AudioMedia.endingCallback)) {
      AudioMedia.endingCallback();
    }
  },
  setCurrentTime: function(sec) {
    if (!AudioMedia.isReady) return;
    if (AudioMedia.isYoutube) {
      AudioMedia.mediaObj.seekTo(sec, true);
    } else if (AudioMedia.isUsingSM2) {
      AudioMedia.mediaObj.actions.seek(sec * 1000);
    } else if (AudioMedia.isVideo) {
      AudioMedia.mediaObj.seek(sec);
    } else if (AudioMedia.isSoundCloud) {
      AudioMedia.mediaObj.seekTo(sec * 1000);
    } else if (AudioMedia.isVimeo) {
      AudioMedia.mediaObj.setCurrentTime(sec);
    } else if (AudioMedia.isDailyMotion) {
      AudioMedia.mediaObj.seek(sec);
    } else {
      AudioMedia.mediaObj.currentTime = sec;
    }
  },
  getCurrentTime: function() {
    if (!AudioMedia.isReady) return;
    if (AudioMedia.isYoutube) {
      return AudioMedia.mediaObj.getCurrentTime();
    } else if (AudioMedia.isUsingSM2) {
      var soundObj = AudioMedia.mediaObj.actions.getSoundObject();
      return typeof soundObj === "undefined" ? 0 : parseInt(soundObj.position / 1000)
    } else if (AudioMedia.isVideo) {
      return parseInt(AudioMedia.mediaObj.getPosition());
    } else if (AudioMedia.isSoundCloud) {
      return parseInt(AudioMedia.currentPos / 1000);
    } else if (AudioMedia.isVimeo) {
      return parseInt(AudioMedia.currentPos);
    } else if (AudioMedia.isDailyMotion) {
      return parseInt(AudioMedia.mediaObj.currentTime);
    } else {
      return AudioMedia.mediaObj.currentTime;
    }
  },
  getLength: function() {
    if (!AudioMedia.isReady) return;
    if (AudioMedia.isYoutube) {
      return AudioMedia.mediaObj.getDuration();
    } else if (AudioMedia.isUsingSM2) {
      var soundObj = AudioMedia.mediaObj.actions.getSoundObject();
      return typeof soundObj === "undefined" ? 0 : parseInt(soundObj.duration / 1000)
    } else if (AudioMedia.isVideo) {
      return parseInt(AudioMedia.mediaObj.getDuration());
    } else if (AudioMedia.isSoundCloud) {
      return parseInt(AudioMedia.duration / 1000);
    } else if (AudioMedia.isVimeo) {
      return parseInt(AudioMedia.duration);
    } else if (AudioMedia.isDailyMotion) {
      return parseInt(AudioMedia.mediaObj.duration);
    } else {
      return AudioMedia.mediaObj.duration;
    }
  },
  formatCurrentTime: function() {
    var totalSec = AudioMedia.getCurrentTime();
    var hours = parseInt(totalSec / 3600) % 24;
    var minutes = parseInt(totalSec / 60) % 60;
    var seconds = parseInt(totalSec % 60);
    return (hours < 10 ? '0' + hours : hours) + ':' + (minutes < 10 ? '0' + minutes : minutes) + ':' + (seconds < 10 ? '0' + seconds : seconds);
  },
  formatCurrentTimeInSec: function(totaltimestr) {
    var parts = totaltimestr.toString().split(":");
    var hours = parseInt(parts[0]) * 60 * 60;
    var minutes = parseInt(parts[1]) * 60;
    var seconds = parseInt(parts[2]);
    return hours + minutes + seconds;
  },
  setRepeat: function(value) {
    return
  },
  isRepeating: function(value) {
    return false;
  },
  rewindBy: function(sec) {
    if (!AudioMedia.isReady) return;
    AudioMedia.setCurrentTime(AudioMedia.getCurrentTime() - sec);
  },
  forwardBy: function(sec) {
    if (!AudioMedia.isReady) return;
    AudioMedia.setCurrentTime(AudioMedia.getCurrentTime() + sec);
  },
  playFrom: function(sec) {
    if (!AudioMedia.isReady) return;
    if (AudioMedia.isUsingSM2) {
      // We first play
      var soundObj = AudioMedia.mediaObj.actions.getSoundObject();
      if (typeof soundObj === "undefined") {
        AudioMedia.play();
        setTimeout(function() {
          AudioMedia.setCurrentTime(sec)
        }, 2000);
      } else {
        AudioMedia.play();
        AudioMedia.setCurrentTime(sec);
        if (!AudioMedia.isPlaying()) {
          AudioMedia.play(); // kick again as we pause it otherwise
        }
      }
    } else if (AudioMedia.isVideo) {
      var doublePlay = AudioMedia.isPlaying(); // jwplayer pauses it otherwise
      AudioMedia.setCurrentTime(sec);
      AudioMedia.play();
      if (doublePlay) {
        AudioMedia.play();
      }
    } else if (AudioMedia.isSoundCloud) {
      if (!AudioMedia.isPlaying()) {
        // soundcloud otherwise ignores it
        AudioMedia.play();
      }
      AudioMedia.setCurrentTime(sec);
    } else if (AudioMedia.isVimeo) {
      AudioMedia.setCurrentTime(sec);
    } else if (AudioMedia.isDailyMotion) {
      AudioMedia.setCurrentTime(sec);
      AudioMedia.play();
    } else {
      AudioMedia.setCurrentTime(sec);
      AudioMedia.play();
    }
  },
  play: function() {
    if (!AudioMedia.isReady) return;
    if (AudioMedia.isYoutube) {
      AudioMedia.mediaObj.playVideo();
    } else if (AudioMedia.isUsingSM2) {
      AudioMedia.mediaObj.actions.play();
    } else if (AudioMedia.isVideo) {
      AudioMedia.mediaObj.play();
    } else if (AudioMedia.isVimeo) {
      AudioMedia.mediaObj.play();
    } else if (AudioMedia.isDailyMotion) {
      AudioMedia.mediaObj.play();
    } else {
      AudioMedia.mediaObj.play();
    }
    AudioMedia.paused = false;
  },
  pause: function() {
    if (!AudioMedia.isReady) return;
    if (AudioMedia.isYoutube) {
      AudioMedia.mediaObj.pauseVideo();
    } else if (AudioMedia.isUsingSM2) {
      AudioMedia.mediaObj.actions.pause();
    } else if (AudioMedia.isVideo) {
      AudioMedia.mediaObj.pause();
    } else if (AudioMedia.isVimeo) {
      AudioMedia.mediaObj.pause();
    } else if (AudioMedia.isDailyMotion) {
      AudioMedia.mediaObj.pause();
    } else {
      AudioMedia.mediaObj.pause();
    }
    AudioMedia.paused = true;
  },
  isPlaying: function() {
    if (!AudioMedia.isReady) return;
    if (AudioMedia.isYoutube) {
      return AudioMedia.mediaObj.getPlayerState() == 1;
    } else if (AudioMedia.isUsingSM2) {
      var soundObj = AudioMedia.mediaObj.actions.getSoundObject();
      return typeof soundObj === "undefined" ? false : !soundObj.paused;
    } else if (AudioMedia.isVideo) {
      return AudioMedia.mediaObj.getState() == "playing";
    } else if (AudioMedia.isSoundCloud) {
      return !AudioMedia.paused;
    } else if (AudioMedia.isVimeo) {
      return !AudioMedia.paused;
    } else if (AudioMedia.isDailyMotion) {
      return !AudioMedia.paused;
    } else {
      return !AudioMedia.mediaObj.paused;
    }
  },
  trigger: function() {
    if (!AudioMedia.isReady) return;
    if (AudioMedia.isPlaying()) {
      AudioMedia.pause();
    } else {
      AudioMedia.play();
    }
  }
}
