<?php

// DESCRIPTION

include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/HadithForReviewQueries.php");
includeOnce("core/queries/CrossReferenceQueries.php");
// Minimum permission to access this service is EditHadith
AccountUtils::verifyAccessToUrl(GeneralUserPermission::EditHadith);
// Requery user permissions (ensure session is not tampered)
if (!AccountUtils::isLoggedIn()) {
	echo json_encode(array("message" => "Please login before you edit hadith", "error" => true));
	die();
}
// -------------------------------------- INCLUDES --------------------------------------
includeOnce("pages/page.php");
includeOnce("core/models/Hadith.php");
includeOnce("core/queries/HadithQueries.php");
includeOnce("core/utils/UrlUtils.php");
includeOnce("core/utils/CacheUtils.php");
CacheUtils::ignoreCache();
$config = Config::getInstance();
// -------------------------------------- CONTENTS --------------------------------------

$collectionName = UrlUtils::paramValue("collectionName");
//$noEnglishAvailable = UrlUtils::paramValue("no-english", false);
$bookNumber = UrlUtils::paramValue("book");
$hadithNumber = UrlUtils::paramValue("hadith");
$hadithId = UrlUtils::paramValue("id");
if (!empty($hadithId)) {

  $hadithById = HadithQueries::queryById($hadithId);
  if ($hadithById !== null) {
    $collectionName = Hadith::determineShortNameFromCollectionId($hadithById->collectionId);
    $bookNumber = $hadithById->book;
    $hadithNumber = $hadithById->hadith;
  } else {
    die("Hadith by id not found $hadithId");
  }
}
$collectionId = Hadith::determineCollectionIdFromShortName($collectionName);

$collectionId = $collectionId === null ? 1 : $collectionId;
$bookNumber = $bookNumber === null ? 1 : $bookNumber;
$hadithNumber = $hadithNumber === null ? 1 : $hadithNumber;

//$_SERVER["DB_CONFIG_PATH"] = "core/database/amraynDb_zs.php";
//includeOnce($_SERVER["DB_CONFIG_PATH"]);

$hadith = HadithQueries::queryHadithByRef(1, $collectionId, $bookNumber, $hadithNumber);
$hadithArabic = HadithQueries::queryHadithByRef(2, $collectionId, $bookNumber, $hadithNumber);

if (empty($hadith) && empty($hadithId)) {
  die("English version of hadith not found. use /edithadith/[id]");
} else if (!empty($hadithById) && $hadithById->databaseId == 2) {
  $hadithArabic = $hadithById;
  $contents .= "<span style='color:red;font-weight:bold'>Using fake english version </span>";
  if (!empty($hadith)) {
    $contents .= " [corresponding english hadith might be: <a href='/edithadith/$hadith->id'>$hadith->id</a>]";
  }
  $hadith = new Hadith(array(
    "databaseId" => 1,
    "collectionId" => $hadithById->collectionId,
    "hadith" => $hadithById->hadith,
    "book" => $hadithById->book,
    "volume" => $hadithById->volume,
    "gradeFlag" => $hadithById->gradeFlag,
    "references" => $hadithById->references,
    "grade" => $hadithById->grade,
    "text" => "Translation not available",
    "status" => 1,
  ));
} else if (!empty($hadithById) && $hadithById->databaseId == 1) {
  $hadith = $hadithById;
}

// Find if we have existing review submitted from this user
$user = AccountUtils::currentUser();
$hadithRef = $hadith->referenceLinkNumbered();
$hadithForReview = HadithForReviewQueries::queryByUserAndHadithRef($user->id, $hadithRef);
$crossRefs = array();
$crossReferences = CrossReferenceQueries::queryByTypeId(CrossReferenceType::Hadith, $hadith->id);
foreach ($crossReferences as $c) {
    $crossRefs[] = array("l" => $c->link, "t" => $c->text);
}

if ($hadithForReview != null) {
	// Temporarily change the $hadith and $hadithArabic obj
	$hadith->text = $hadithForReview->newText;
	$crossRefs = json_decode($hadithForReview->newLinks);
	$hadith->references = $hadithForReview->newReferences;
	$hadith->tags = $hadithForReview->newTags;
	$hadith->gradeFlag = $hadithForReview->newGradeFlag;
	if ($hadithArabic != null) {
		$hadithArabic->text = $hadithForReview->newArabicText;
	}
}

$title = "Hadith";
$metaDescription = "Advanced editor lets you make comprehensive edit to hadith (unlike in-page editor which only lets you edit text)";
$metaKeywords = "";
$canEditHadith = AccountUtils::verifyAccess(GeneralUserPermission::EditHadith) ? "true" : "false";
$canMergeHadith = AccountUtils::verifyAccess(GeneralUserPermission::MergeHadith) ? "true" : "false";
$contents .= getJSResources(array("/scripts/are-you-sure.js", "/scripts/hadith.js"));
$json = array("fastIdentifier" => $hadith->getPublicId(), "collectionId" => $hadith->collectionId, "volume" => $hadith->volume, "book" => $hadith->book, "hadith" => $hadith->hadith, "text" => nl2br($hadith->text), "notes" => nl2br($hadith->notes), "gradeFlag" => $hadith->gradeFlag, "links" => $crossRefs, "references" => $hadith, "references_data" => $hadith->references, "tags" => $hadith->tags, "lastUpdated" => $hadith->lastUpdated);
if ($hadithArabic != null) {
	$json["fastIdentifier2"] = $hadithArabic->getPublicId();
	$json["textArabic"] = nl2br(trim($hadithArabic->text));
	$json["notesArabic"] = nl2br(trim($hadithArabic->notes));
}
$embeddedJS = "\nHadithEditor.original = " . json_encode($json) . ";HadithEditor.modified=HadithEditor.original;\n";
if ($hadithForReview != null) {
	$fastIdentifierJson = array("fastIdentifier" => $hadithForReview->getPublicId());
	$embeddedJS .= "\nHadithEditor.hadithForReview =" . json_encode($fastIdentifierJson) . ";\n";
}
$embeddedJS .= <<<JS
	var HADITH_VERSION = $config->HADITH_VERSION;
	var advancedEdit = true;
	var collection = "$collectionId";
	var book = "$bookNumber";
  var hadith = "$hadithNumber";
  var volume = "$hadith->volume";
	User.Permissions.canEditHadith = $canEditHadith;
	User.Permissions.canMergeHadith = $canMergeHadith;
	HadithEditor.canEdit = User.Permissions.canEditHadith;
	HadithEditor.canMerge = User.Permissions.canMergeHadith;

	buildCrossReferenceHTMLForEdit = function(links) {
		if (links == null || links == undefined) {
			return "";
		}
		var finalHTML = "";
		for (var i = 0; i < links.length; ++i) {
			var link = links[i];
			finalHTML += "&nbsp;&nbsp;&nbsp;&nbsp;<a href='" + link.l + "' class='hadith-ref local' idx='" + i + "'>" + link.t.replace("Quraan", "Qur'an").trim() + "</a>&nbsp;&nbsp;<a class='edit-cross-ref fa fa-pencil' title='Edit' notooltip idx='" + i + "'></a>&nbsp;&nbsp;<a class='moveup-cross-ref fa fa-arrow-circle-up' title='Move Up' notooltip idx='" + i + "'></a>&nbsp;&nbsp;<a class='movedown-cross-ref fa fa-arrow-circle-down' title='Move Down' notooltip idx='" + i + "'></a>&nbsp;&nbsp;&nbsp;<a class='remove-cross-ref fa fa-close' title='Remove' notooltip idx='" + i + "'></a><br/>";
		}
		return finalHTML;
	}

	save = function() {
    HadithEditor.modified.collectionId = collection;
    HadithEditor.modified.hadith = $("#hadithnumber").val();
    HadithEditor.modified.book = $("#booknumber").val();
    HadithEditor.modified.volume = $("#volumenumber").val();
    HadithEditor.modified.text = $("#english-text").val();
		HadithEditor.modified.textArabic = $("#arabic-text").val();
		HadithEditor.modified.notes = $("#english-notes").val();
		HadithEditor.modified.notesArabic = $("#arabic-notes").val();
		HadithEditor.modified.gradeFlag = 0;
		$.each($(".hadith-tag-check"), function(k, v) {
			var obj = $(v);
			if (obj.is(":checked")) {
				HadithEditor.modified.gradeFlag += parseInt(obj.attr("flag"));
			}
		});
		if (HadithEditor.modified.gradeFlag == 0) {
			HadithEditor.modified.gradeFlag = 4194304; //no data
		}
		HadithEditor.modified.references_data = $("#references").val();
		HadithEditor.modified.references = HadithEditor.modified.references_data;
		HadithEditor.modified.tags = $("#tags").val();
		HadithEditor.saveHadith($("#savebtn"), function() { Utils.transitionPageLoad(location.href); });
	}
	cancel = function() {
		HadithEditor.cancelProposal($("#cancelbtn"), function() { Utils.transitionPageLoad(location.href); });
	}
	$(document).on('click', '.remove-cross-ref', function() {
		var idx = $(this).attr("idx");
		HadithEditor.modified.links.splice(idx, 1);
		if (HadithEditor.modified.links.length == 0) {
			HadithEditor.modified.links = null;
		}
		$("#cross-ref-links").html(buildCrossReferenceHTMLForEdit(HadithEditor.modified.links));
	});

	$(document).on('click', '.moveup-cross-ref', function() {
		var idx = parseInt($(this).attr("idx"));
		if (idx != 0) {
			var tmp = HadithEditor.modified.links[idx - 1];
			HadithEditor.modified.links[idx - 1] = HadithEditor.modified.links[idx];
			HadithEditor.modified.links[idx] = tmp;
		}
		$("#cross-ref-links").html(buildCrossReferenceHTMLForEdit(HadithEditor.modified.links));
	});
	$(document).on('click', '.movedown-cross-ref', function() {
		var idx = parseInt($(this).attr("idx"));
		if (idx != HadithEditor.modified.links.length - 1) {
			var tmp = HadithEditor.modified.links[idx + 1];
			HadithEditor.modified.links[idx + 1] = HadithEditor.modified.links[idx];
			HadithEditor.modified.links[idx] = tmp;
		}
		$("#cross-ref-links").html(buildCrossReferenceHTMLForEdit(HadithEditor.modified.links));
	});
	$(document).on('click', '.add-cross-ref', function() {
		$('#add-cross-ref-dialog').dialog({
			title: 'Add Cross Reference',
			height: 'auto',
			width: '80%',
			modal: true,
			dialogClass: 'fixed-dialog add-cross-ref-dialog',
			open: function(event, ui) {
				$('#add-cross-ref-dialog-text').focus();
				$('.ui-widget-overlay').bind('click', function() {
					$('#add-cross-ref-dialog').dialog('close');
				});
			},
			buttons: {
        		'Add': function() {
					if ($('#add-cross-ref-dialog-link').val().trim().length > 0 && $('#add-cross-ref-dialog-text').val().trim().length > 0) {
						if (HadithEditor.modified.links == null) {
							HadithEditor.modified.links = [];
						}
						HadithEditor.modified.links.push({l:$('#add-cross-ref-dialog-link').val(),t:$('#add-cross-ref-dialog-text').val()});
					}
					$('#add-cross-ref-dialog').dialog('close');
					$("#cross-ref-links").html(buildCrossReferenceHTMLForEdit(HadithEditor.modified.links));
				}
			}
		});
	});
	$(document).on('click', '.edit-cross-ref', function() {
		var idx = parseInt($(this).attr('idx'));
		$('#add-cross-ref-dialog-link').val($("#cross-ref-links>a.hadith-ref[idx='" + idx + "']").attr('href'));
		$('#add-cross-ref-dialog-text').val($("#cross-ref-links>a.hadith-ref[idx='" + idx + "']").text());
		$('#add-cross-ref-dialog').dialog({
			title: 'Edit Cross Reference',
			height: 'auto',
			width: '80%',
			modal: true,
			dialogClass: 'fixed-dialog add-cross-ref-dialog',
			open: function(event, ui) {
				$('#add-cross-ref-dialog-text').focus();
				$('.ui-widget-overlay').bind('click', function() {
					$('#add-cross-ref-dialog').dialog('close');
				});
			},
			buttons: {
        		'Edit': function() {
					if ($('#add-cross-ref-dialog-link').val().trim().length > 0 && $('#add-cross-ref-dialog-text').val().trim().length > 0) {
						if (HadithEditor.modified.links == null) {
							HadithEditor.modified.links = [];
						}
						HadithEditor.modified.links[idx].l = $('#add-cross-ref-dialog-link').val();
						HadithEditor.modified.links[idx].t = $('#add-cross-ref-dialog-text').val();
					}
					$('#add-cross-ref-dialog').dialog('close');
					$("#cross-ref-links").html(buildCrossReferenceHTMLForEdit(HadithEditor.modified.links));
				}
			}
		});
	});
	onloads[onloads.length++] = function() {
		$("#cross-ref-links").html(buildCrossReferenceHTMLForEdit(HadithEditor.modified.links));
	}
JS;
$contents .= "<script>$embeddedJS</script>";
$contents .= <<<PAGE
	<style>
		.edit-elem {
			width: 90%;
		}
		.remove-cross-ref,.add-cross-ref {
			cursor:pointer;
		}
		.add-cross-ref-dialog {
			background: #fff;
		}

		.hadith-edit-grade {
			display: block;
			padding-left: 1em;
		}
	</style>
    <link rel='stylesheet' type='text/css' href='/styles/hadith.css?v=$config->VERSION' />
	<div id='add-cross-ref-dialog' style='width:80%;display:none;'>
		Text: <input type='text' id='add-cross-ref-dialog-text' style='width:95%;' /><br/><br/>
		Link: <input type='text' id='add-cross-ref-dialog-link' style='width:95%;' />
	</div>
    <h1>Advanced Hadith Editor</h1>
PAGE;
$contents .= "<h2><a href='" . $hadith->referenceLink() . "' class='local' style='text-decoration:none'>" . $hadith->referenceText("Number") . "</a></h2>";
$contents .= "<div style='color:#666'>(English) ID: " . $hadith->id . "</div>";
if ($hadithArabic != null) {
  $contents .= "<div style='color:#666'>(Arabic) ID: " . $hadithArabic->id . "</div>";
}
if ($hadithForReview != null) {
	$contents .= "<div style='font-weight:bold'>You have previously submitted changes. You can continue to make changes and they will be resubmitted.<br/>Reviews: <span style='color:darkgreen'>+$hadithForReview->positiveReviews</span> <span style='color:red'>-$hadithForReview->negativeReviews</span></div>";
}
$contents .= "<h4>English</h4>";
$contents .= "<textarea id='english-text' class='edit-elem' style='font-size: 1em;line-height:1.3em;font-family:Gentium Basic'>" . StringUtils::br2nl($hadith->text) . "</textarea>";
$contents .= "<h4>Arabic</h4>";
$contents .= "<textarea id='arabic-text' class='edit-elem arabic hadith-text-arabic'>";
if ($hadithArabic != null) {
  $contents .= StringUtils::br2nl($hadithArabic->text);
}
$contents .= "</textarea>";
$contents .= "<h4>Notes</h4>";
$contents .= "<textarea id='english-notes' class='edit-elem' style='font-size: 1em;line-height:1.3em;font-family:Gentium Basic'>" . StringUtils::br2nl($hadith->notes) . "</textarea>";
$contents .= "<h4>Arabic Notes</h4>";
$contents .= "<textarea id='arabic-notes' class='edit-elem arabic hadith-text-arabic'>";
if ($hadithArabic != null) {
  $contents .= StringUtils::br2nl($hadithArabic->notes);
}
$contents .= "</textarea>";
$contents .= "<h4>Classification</h4>";
foreach (Hadith::gradeFlagMap() as $idx => $grade) {
	$contents .= "<div class='hadith-edit-grade'><input type='checkbox' class='hadith-tag-check $grade[class_]' id='hadith-class-$grade[id]' flag='$grade[id]' " . ($hadith->gradeFlag & $grade["id"] ? "checked" : "") . " /> <label class='hadith-tag $grade[class_]' for='hadith-class-$grade[id]'>$grade[text]</label></div>";
}
$contents .= "<h4>Cross References</h4>";
$contents .= "<div id='cross-ref-links'></div><a class='add-cross-ref fa fa-plus' notooltip title='Add New'></a>";
$contents .= "<h4>Reference</h4>";
$contents .= "<input type='text' id='references' class='edit-elem' value='" . $hadith->references . "' />";
$contents .= "<h4>Tags <sup>(comma-seperated &amp; expected search term)</sup></h4>";
$contents .= "<input type='text' id='tags' class='edit-elem' value='" . $hadith->tags . "' />";
$contents .= "<h4>Change Volume Number</h4>";
$contents .= "<input type='text' id='volumenumber' class='edit-elem' value='" . $hadith->volume . "' />";
$contents .= "<h4>Change Book Number</h4>";
$contents .= "<input type='text' id='booknumber' class='edit-elem' value='" . $hadith->book . "' />";
$contents .= "<h4>Change Hadith Number</h4>";
$contents .= "<input type='text' id='hadithnumber' class='edit-elem' value='" . $hadith->hadith . "' />";
$contents .= "<br><br><button onclick='save()' id='savebtn' class='taskbtn' style='font-size:1.2em;'><i class='fa fa-save'></i> Save</button>";
if ($hadithForReview != null) {
	$contents .= "&nbsp;&nbsp;<button onclick='cancel()' id='cancelbtn' style='font-size:1.2em;'><i class='fa fa-close'></i> Cancel Current Proposal</button>";
}
// ------------------------------------- INITIALIZE ------------------------------------
init($contents, array(
	"title" => $title,
	"meta_description" => $metaDescription,
	"context" => Context::Hadith,
	"meta_images" => array("https://amrayn.com/assets/images/hadith.png", "https://amrayn.com/assets/images/logo.png"),
	"meta_keywords" => "$collectionName,$title,$metaKeywords",
	"breadcrumbs" => array("Home" => "/", "Hadith" => "/hadith/", Hadith::collectionMap()[$hadith->collectionId]["long"] => "/$collectionName/", "$bookNumber/$hadithNumber" => "/$collectionName/$bookNumber/$hadithNumber/", "Edit" => "")
));
?>
