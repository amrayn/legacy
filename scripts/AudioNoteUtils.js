var AudioNoteUtils = {
	audioNoteItems : [],
	editControls: false,
	lastSelector: null,
	publicId: null,
	audioPublicId: null,
	fepi: null,
	unsavedChanges: false,
	bindViewControls: function() {
		$('.ln-heading-selector').change(function() {
			var headingAttr = $(this).val();
			if (headingAttr == '') {
				$(".ln-item,.ln-head").show();
			} else {
				$(".ln-item,.ln-head").hide();
				$(".ln-item-" + headingAttr + ",.ln-head-" + headingAttr).show();
			}
		});
		if (typeof AudioMedia === "object") {
			$('.ln-time').click(function() {
				AudioMedia.playFrom(parseInt($(this).attr('sec')));
			});
		} else {
			Debug.log("AudioMedia not found. Cannot bind times");
		}
	},
	bindEditingControls: function() {
		$('.ln-controls > .moveup').click(function() {
			AudioNoteUtils.moveUp(parseInt($(this).parent().attr("idx")));
		});
		$('.ln-controls > .movedown').click(function() {
			AudioNoteUtils.moveDown(parseInt($(this).parent().attr("idx")));
		});
		$('.ln-controls > .delete').click(function() {
			AudioNoteUtils.deleteItem(parseInt($(this).parent().attr("idx")));
		});
		$('.ln-controls > .edit').click(function() {
			AudioNoteUtils.editItem(parseInt($(this).parent().attr("idx")));
		});
		$('.add-note-heading,.edit-note-heading').change(function() {
			if ($(this).val() === "add_new") {
				var newHeading = prompt("Please enter heading name", "");
				if (newHeading !== null && newHeading.length > 0) {
					var headingAttr = AudioNoteUtils.headingToValue(newHeading);
					if ($(".ln-heading-selector>option[value=" + headingAttr + "]").length == 0) {
						$("<option value='" + headingAttr + "'></option>").html(newHeading.trim()).insertBefore($(".add-new-heading"));
					}
					$(this).val(headingAttr);
				} else {
					$(this).val($(".ln-heading-selector > option:last").val());
				}
			}
		});
		$('.ln-rename-head').click(function() {
			var currentName = $(this).parent().text();
			currentName = currentName.substr(0, currentName.lastIndexOf(" (Rename)"));
			var headingAttr = AudioNoteUtils.headingToValue(currentName);
			var newName = prompt("Please enter new heading name", currentName);
			if (newName !== null) {
				var newHeadingAttr = AudioNoteUtils.headingToValue(newName);
				$(".ln-heading-selector>option[value=" + headingAttr + "]").val(newHeadingAttr).text(newName);
				for (var i = 0; i < AudioNoteUtils.audioNoteItems.length; ++i) {
					if (AudioNoteUtils.audioNoteItems[i].heading == currentName) {
						AudioNoteUtils.audioNoteItems[i].heading = newName;
					}
				}
				AudioNoteUtils.render(AudioNoteUtils.lastSelector);
			}
		});
	},
	validateInputForEdit: function(type) {
		$('.' + type + '-note-heading').removeClass("error-elem");
		$('#' + type + '-note-dialog-text').removeClass("error-elem");
		$('#' + type + '-note-dialog-time').removeClass("error-elem");
		var hasError = false;
		if ($('.' + type + '-note-heading option:selected').val().trim().length == 0
				|| $('.' + type + '-note-heading option:selected').val().trim() == "add_new") {
			$('.' + type + '-note-heading').addClass("error-elem");
			hasError = true;
		}
		if ($('#' + type + '-note-dialog-time').val().trim().length == 0) {
			$('#' + type + '-note-dialog-time').addClass("error-elem");
			hasError = true;
		}
		if ($('#' + type + '-note-dialog-text').val().trim().length == 0) {
			$('#' + type + '-note-dialog-text').addClass("error-elem");
			hasError = true;
		}
		return !hasError;
	},
	addNew: function() {
		$('.add-note-heading').html($(".ln-heading-selector").html());
		$('.add-note-heading').append("<option value='add_new' class='add-new-heading'>Add New...</option>");
		// Select last one
		$('.add-note-heading').val($(".ln-heading-selector > option:last").val());
		$('#add-note-dialog').dialog({
			title: $('#add-note-dialog').attr('title'),
			height: 'auto',
			width: '80%',
			modal: true,
			dialogClass: 'fixed-dialog',
			open: function(event, ui) {
				$('#add-note-dialog-text').focus();
				$('#add-note-dialog-text').select();
				$('.ui-widget-overlay').bind('click', function() {
					$('#add-note-dialog').dialog('close'); 
				});
			},
			buttons: {
        		'Add & Clear': function() {
					if (!AudioNoteUtils.validateInputForEdit('add')) {
						return;
					}
					var items = AudioNoteUtils.itemsByHeadings()[$('.add-note-heading option:selected').text()];
					AudioNoteUtils.audioNoteItems.push({
						time: AudioNoteUtils.formatNoteTimeInSec($('#add-note-dialog-time').val()),
						text: $('#add-note-dialog-text').val(),
						heading: $('.add-note-heading option:selected').text(),
						id: '',
						sortOrder: items.length
					});
					AudioNoteUtils.render(AudioNoteUtils.lastSelector);
					updateCurrentTime();
					$('#add-note-dialog-text').val("");
					$('#add-note-dialog-text').focus();
				},
        		'Add & Close': function() {
					if (!AudioNoteUtils.validateInputForEdit('add')) {
						return;
					}
					var items = AudioNoteUtils.itemsByHeadings()[$('.add-note-heading option:selected').text()];
					
					AudioNoteUtils.audioNoteItems.push({
						time: AudioNoteUtils.formatNoteTimeInSec($('#add-note-dialog-time').val()),
						text: $('#add-note-dialog-text').val(),
						heading: $('.add-note-heading option:selected').text(),
						id: '',
						sortOrder: items.length
					});
					$('#add-note-dialog').dialog('close');
					AudioNoteUtils.render(AudioNoteUtils.lastSelector);
				},
        		'Cancel': function() {
					$('#add-note-dialog').dialog('close');
				}
			}
		});
	},
	editItem: function(idx) {
		if (idx >= AudioNoteUtils.audioNoteItems.length) {
			return;
		}
		$('.edit-note-heading').html($(".ln-heading-selector").html());
		$('.edit-note-heading').append("<option value='add_new' class='add-new-heading'>Add New...</option>");
		var item = AudioNoteUtils.audioNoteItems[idx];
		$('#edit-note-dialog-time').val(AudioNoteUtils.formatNoteTime(item.time));
		$('#edit-note-dialog-text').val(item.text.split("\\\"").join("\""));
		$('.edit-note-heading').val(AudioNoteUtils.headingToValue(item.heading));
		$('#edit-note-dialog').dialog({
			title: $('#edit-note-dialog').attr('title'),
			height: 'auto',
			width: '80%',
			modal: true,
			dialogClass: 'fixed-dialog',
			open: function(event, ui) {
				$('#edit-note-dialog-text').focus();
				$('#edit-note-dialog-text').select();
				$('.ui-widget-overlay').bind('click', function() {
					$('#edit-note-dialog').dialog('close'); 
				});
			},
			buttons: {
        		'Edit': function() {
					if (!AudioNoteUtils.validateInputForEdit('edit')) {
						return;
					}
					item.time = AudioNoteUtils.formatNoteTimeInSec($('#edit-note-dialog-time').val()),
					item.text = $('#edit-note-dialog-text').val(),
					item.heading = $('.edit-note-heading option:selected').text()
					$('#edit-note-dialog').dialog('close');
					AudioNoteUtils.audioNoteItems[idx] = item;
					AudioNoteUtils.render(AudioNoteUtils.lastSelector);
				},
        		'Cancel': function() {
					$('#edit-note-dialog').dialog('close');
				}
			}
		});
	},
	deleteItem: function(idx) {
		if (idx >= AudioNoteUtils.audioNoteItems.length) {
			return;
		}
		var item = AudioNoteUtils.audioNoteItems[idx];
		if (confirm("Are you sure you wish to delete?\n\n" + AudioNoteUtils.formatNoteTime(item.time) + " — " + item.text)) {
			AudioNoteUtils.audioNoteItems.splice(idx, 1);
			AudioNoteUtils.render(AudioNoteUtils.lastSelector);
		}
	},
	save: function() {
		if ($("#llectureid").val().trim().length === 0 || $("span.lnoteerror").is(":visible")) {
			$(".main-error").html("Please fill/fix the form").show();
			return;
		}
		NProgress.start();
		var initiallyNoPublicId = AudioNoteUtils.publicId === null || AudioNoteUtils.publicId.length == 0;
		AudioNoteUtils.saveNote(function(resp) {
			if (resp.error) {
				Utils.notifyMessage({text: resp.message, type: 'error', timeout: 4000 });
				NProgress.done();
			} else {
				if (typeof resp.newId !== "undefined") {
					AudioNoteUtils.publicId = resp.newId;
				}
				if (typeof resp.fepi !== "undefined") {
					AudioNoteUtils.fepi = resp.fepi;
				}

				if (AudioNoteUtils.unsavedChanges) {
					AudioNoteUtils.saveNoteItem(function(resp) {
						if (!resp.error) {
							AudioNoteUtils.unsavedChanges = false;
							if (initiallyNoPublicId && (AudioNoteUtils.publicId !== null && AudioNoteUtils.publicId.length > 0)) {
								Utils.transitionPageLoad('/audio/writenote/' + AudioNoteUtils.publicId);
							} else {
								Utils.notifyMessage({ text: resp.message, type: 'success', timeout: 4000 });
							}
						} else {
							Utils.notifyMessage({ text: resp.message, type: 'error', timeout: 4000 });
						}
						NProgress.done();
					});
				} else {
					if (initiallyNoPublicId && AudioNoteUtils.publicId !== null) {
						Utils.transitionPageLoad('/audio/writenote/' + AudioNoteUtils.publicId);
					} else {
						Utils.notifyMessage({ text: resp.message, type: 'success', timeout: 4000 });
						NProgress.done();
					}
				}
			}
		});
	},
	saveNote: function(callback) {
		// TODO: Publish
		$.ajax({
			url : '/svc/edit-audio-note' + __svcargs,
			data : {
				data : jQuery.extend(true, {}, jQuery("form").serializeObject(true)),
				fepi : AudioNoteUtils.fepi
			},
			type: 'POST'
		}).always(function(resp) {
			callback(resp);
		});
	},
	deleteNote: function() {
		if (confirm("Are you sure you wish to delete? You cannot undo this!")) {
			$.ajax({
				url : '/svc/edit-audio-note' + __svcargs,
				data : {
					data : jQuery.extend(true, {}, jQuery("form").serializeObject(true)),
					fepi : AudioNoteUtils.fepi,
					del : true
				},
				type: 'POST'
			}).always(function(resp) {
				if (!resp.error) {
					Utils.transitionPageLoad(AudioNoteUtils.audioLink);
				} else {
					Utils.notifyMessage({ text: resp.message, type: 'error', timeout: 4000 });
				}
			});
		}
	},
	saveNoteItem: function(callback) {
		$.ajax({
			url : '/svc/edit-audio-note-items' + __svcargs,
			data : {
				data : AudioNoteUtils.audioNoteItems,
				fepi : AudioNoteUtils.fepi,
				audioNotePublicId : AudioNoteUtils.publicId
			},
			type: 'POST'
		}).always(function(resp) {
			callback(resp);
		});
	},
	headingToValue: function(heading) {
		return heading.replace(/[^a-z0-9]/gi,'').toLowerCase();
	},
	move: function(idx, by) {
		var thisNote = AudioNoteUtils.audioNoteItems[idx];
		var neighbourNote = AudioNoteUtils.audioNoteItems[idx + by];
		thisNote.heading = neighbourNote.heading;
		var tmpSortOrder = thisNote.sortOrder;
		thisNote.sortOrder = neighbourNote.sortOrder;
		neighbourNote.sortOrder = tmpSortOrder;
		AudioNoteUtils.audioNoteItems[idx + by] = thisNote;
		AudioNoteUtils.audioNoteItems[idx] = neighbourNote;
		AudioNoteUtils.render(AudioNoteUtils.lastSelector);
	},
	moveUp: function(idx) {
		if (idx === 0) {
			return;
		}
		AudioNoteUtils.move(idx, -1);
	},
	moveDown: function(idx) {
		if (idx >= AudioNoteUtils.audioNoteItems.length) {
			return;
		}
		AudioNoteUtils.move(idx, 1);
	},
	itemsByHeadings: function() {
		var items = AudioNoteUtils.audioNoteItems;
		var currentHeading = "";
		
		var allHeadings = [];
		for (var i = 0; i < items.length; ++i) {
			if (items[i].heading != currentHeading) {
				allHeadings.push(items[i].heading);
				currentHeading = items[i].heading;
			}
		}
		allHeadings = jQuery.unique(allHeadings);
		for (var i = 0; i < allHeadings.length; ++i) {
			allHeadings[allHeadings[i]] = [];
		}
		for (var i = 0; i < items.length; ++i) {
			allHeadings[items[i].heading].push(items[i]);
		}
		return allHeadings;
	},
	render: function(selector, callback) {
		AudioNoteUtils.lastSelector = selector;
		if (selector === null) {
			return;
		}
		/*function SortByHeading(a, b){
			var aHeading = a.heading.toLowerCase();
			var bHeading = b.heading.toLowerCase();
			return ((aHeading < bHeading) ? -1 : ((aHeading > bHeading) ? 1 : 0));
		}
		items.sort(SortByHeading);*/
		/*function SortBySortOrder(a, b){
			return ((a.sortOrder < b.sortOrder) ? -1 : ((a.sortOrder > b.sortOrder) ? 1 : 0));
		}
		items.sort(SortBySortOrder);*/
			
		var notesHtml = "";
		$(".ln-head,.ln-item,.ln-heading-selector,.ln-heading-selector-label").remove();
		var headingSelectControl = $("<select class='ln-heading-selector'></select>");
		headingSelectControl.append("<option value=''>[Show All]</option>");
		var allHeadings = AudioNoteUtils.itemsByHeadings();
		var renderedHeadings = [];
		for (var i = 0; i < allHeadings.length; ++i) {
			var noteHtml = "";
			var heading = allHeadings[i];
			if (renderedHeadings.indexOf(heading) > -1) {
				continue;//even though we do jQuery.unique in some browsers it doesn't work
			}
			renderedHeadings.push(heading);
			var headingAttr = AudioNoteUtils.headingToValue(heading);
			noteHtml += "<h4 class='ln-head ln-head-" + headingAttr + "'>" + heading;
			if (AudioNoteUtils.editControls) {
				noteHtml += " <a class='ln-rename-head'>(Rename)</a>";
			}
			noteHtml += "</h4>";
			headingSelectControl.append("<option value='" + headingAttr + "'>" + heading + "</option>");
			for (var j = 0; j < allHeadings[heading].length; ++j) {
				var totalItems = allHeadings[heading].length;
				var noteItem = allHeadings[heading][j];
				noteHtml += "<div class='ln-item ln-item-" + headingAttr + "' noteid='" + noteItem.id + "'>";
				var idx = isNaN(noteItem.sortOrder) ? j : noteItem.sortOrder;
				if (AudioNoteUtils.editControls) {
					noteHtml += "<span class='ln-controls' idx='" + idx + "'>";
					if (j > 0) {
						noteHtml += "<a class='fa fa-arrow-circle-up moveup'></a>";
					} else {
						noteHtml += "<a class='fa fa-arrow-circle-up' style='color: #ccc;'></a>";
					}
					if (j < totalItems - 1) {
						noteHtml += "<a class='fa fa-arrow-circle-down movedown'></a>";
					} else {
						noteHtml += "<a class='fa fa-arrow-circle-down' style='color: #ccc;'></a>";
					}
					noteHtml += "<a class='fa fa-edit edit'></a>";
					noteHtml += "<a class='fa fa-close delete'></a>";
					noteHtml += "</span>";
				}
				noteHtml += "<span class='ln-time' sec='" + noteItem.time + "'>" + AudioNoteUtils.formatNoteTime(noteItem.time) + "</span>";
				noteHtml += "<span class='ln-sep'>—</span>";
				noteHtml += "<span class='ln-text'>" + noteItem.text + "</span>";
				noteHtml += "</div>";
			}
			notesHtml += noteHtml;
			AudioNoteUtils.unsavedChanges = true;
		}
		selector.append("<span class='ln-heading-selector-label'>Filter: </span>");
		headingSelectControl.appendTo(selector);
		selector.append(notesHtml);
		if (headingSelectControl.children().length <= 2) { // Show ALL and one heading
			$(".ln-heading-selector-label,.ln-heading-selector,.ln-head").hide();
		}
		AudioNoteUtils.bindViewControls();
		if (AudioNoteUtils.editControls) {
			AudioNoteUtils.bindEditingControls();
			if ($("#chk-autosave").is(":checked")) {
				AudioNoteUtils.save();
			}
		}
		if (jQuery.isFunction(callback)) {
			callback();
		}
	},
	formatNoteTime : function(totalSec) {
		var hours = parseInt( totalSec / 3600 ) % 24;
		var minutes = parseInt( totalSec / 60 ) % 60;
		var seconds = totalSec % 60;
		return (hours < 10 ? '0' + hours : hours) + ':' + (minutes < 10 ? '0' + minutes : minutes) + ':' + (seconds  < 10 ? '0' + seconds : seconds);
	},
	formatNoteTimeInSec : function(totaltime) {
		var parts = totaltime.toString().split(":");
		var hours = parseInt(parts[0]) * 60 * 60;
		var minutes = parseInt(parts[1]) * 60;
		var seconds = parseInt(parts[2]);
		return hours + minutes + seconds;
	}
};
