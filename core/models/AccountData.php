<?php

includeOnce("core/models/BaseModel.php");

abstract class AccountDataKeys
{
	const AcceptedHadithEdits = "accepted_hadith_edits";
	const DeclinedHadithEdits = "declined_hadith_edits";
	const CurrentSession = "current_session";
    const TimeZone = "tz";
    const Latitude = "lat";
    const Longitude = "lng";
	const HadithReviewed = "reviewed_hadiths";
	const Theme = "theme";
	const PrayerTimeMethod = "prayer_time_method";
	const PrayerTimeAdjustment = "prayer_time_adj";
	const UserLastSeen = "last_seen";
	const RecentAudios = "rec_lec";
	const MemorizationAvgLinesPerDay = "avg_lines_mem";
	const MemorizationDaysSchedule = "mem_days";
	const MemorizationBreak = "mem_brk";
	const AutoSignInIdGooglePlus = "auto_signin_id_gplus";
	const AutoSignInIdFacebook = "auto_signin_id_fb";
}

class AccountData extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "userId",
            "name",
            "value"));
    }
}

?>
