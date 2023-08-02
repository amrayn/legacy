		<button onclick="javascript:shareViaEmail();" title="Share by Email" class="share-btn" id="email-share-button" class="toolbar-button"><span class="fa fa-envelope"></span></button>
		<button onclick="javascript:shareOnFacebook();" title="Share on Facebook" class="share-btn" id="fb-share-button" class="toolbar-button"><span class="fa fa-facebook"></span></button>
		<?php // For twitter we have several scenerios ?>
		<?php if (isset($_GET["sharing-hadith"])) { ?>
			<button onclick="javascript:shareOnTwitter($('.hadith-text').text(), false, true);" title="Share on Twitter" class="share-btn" id="twitter-share-button" class="toolbar-button"><span class="fa fa-twitter"></span></button>
		<?php } else if (isset($_GET["sharing-quran"])) { ?>
			<button onclick="javascript:shareOnTwitter(undefined, true);" title="Share on Twitter" class="share-btn" id="twitter-share-button" class="toolbar-button"><span class="fa fa-twitter"></span></button>
		<?php } else { ?>
			<button onclick="javascript:shareOnTwitter();" title="Share on Twitter" class="share-btn" id="twitter-share-button" class="toolbar-button"><span class="fa fa-twitter"></span></button>
		<?php } ?>
		<button onclick="javascript:shareOnLinkedIn();" title="Share on LinkedIn" class="share-btn" id="linkedin-share-button" class="toolbar-button"><span class="fa fa-linkedin"></span></button>
