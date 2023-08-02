<?php

includeOnce("core/models/BaseModel.php");

// General user permission are global permissions and not specific to a record (as oppose to UserPermission which is specific to the record based on type)
abstract class GeneralUserPermission extends BaseEnum
{
	const Basic = '{"flag":1,"label":"Basic","details":"Basic usage of site. This includes ability to bookmark hadiths, audio and articles. See \'About\' page for details."}';
	const SuperUser = '{"flag":2,"label":"Super User","details":"You can do everything and does not need any permissions."}';
	const AccessFileManager = '{"flag":4,"label":"Access File Manager","details":"Able to access file manager (/live/) for administration purposes."}';
	const EditHadith = '{"flag":8,"label":"Edit Hadith","details":"You can edit hadith. This permission allows you to submit your edit for review. You need further permission (<i>Review and Merge Hadith</i>) to merge it directly to live. In this case no review is needed."}';
	const ReviewHadith = '{"flag":16,"label":"Review Hadith","details":"You can review previously edited hadith and send positive or negative reviews."}';
	const MergeHadith = '{"flag":32,"label":"Merge Hadith","details":"You can merge hadith (that was previously reviewed or without reviewing) to live.", "requiredPermission" : 24}'; // Require EditHadith and ReviewHadith permission
	const CanEncryptDecrypt = '{"flag":64,"label":"Can Encrypt/Decrypt","details":"You can use /svc/encrypt and /svc/decrypt."}';
	const EditAudio = '{"flag":128,"label":"Edit Audio","details":"You can edit meta information for audios."}';
	const AddAudio = '{"flag":256,"label":"Add Audio","details":"You can add new audios."}';
	const PrivateAudio = '{"flag":512,"label":"Private Audios","details":"You have access to all private audios on the site"}';
	const PrivateBook = '{"flag":1024,"label":"Private Books","details":"You have access to all private books on the site"}';
	const EditBook = '{"flag":2048,"label":"Edit Book","details":"You can edit meta information for books."}';
	const AddBook = '{"flag":4096,"label":"Add Book","details":"You can add new books."}';
}
abstract class UserPreference extends BaseEnum
{
	const ManualUpdateNofication = '{"flag":32,"label":"Manually Update Notification","details":"Prevents an auto-update for notifications. You will have to click \'Update\' in notification bar to get latest notifications."}';
	const NotificationsGeneral = '{"flag":1,"label":"Notifications - General","details":"Receive general notifications."}';
	const NotificationsHadithUpdate = '{"flag":4,"label":"Notifications - Hadith Updates","details":"Notification when your favourite hadith is updated (spelling fix etc)"}';
	const NotificationsProductUpdate = '{"flag":8,"label":"Notifications - Product Updates","details":"Notification when we make significant changes to the website."}';
	const NotificationsHadithReview = '{"flag":16,"label":"Notifications - Hadith Review","details":"If you are able to merge a hadith, you will get notified of any new hadith changes submitted."}';
	const PreferArabicHadith = '{"flag":64,"label":"Prefer Arabic Hadith","details":"You will see arabic hadith when it\'s loaded"}';
	const PrayerReminder = '{"flag":128,"label":"Prayer Reminder","details":"When it\'s time to pray and site is open in any tab, we will notify you on your desktop"}';
	const ProtectionMode = '{"flag":256,"label":"Protection Mode","details":"All private access will be temporarily revoked"}';


	public static function flagToEnum($typeFlag) {
		switch (intval($typeFlag)) {
			case 4:
				return UserPreference::NotificationsHadithUpdate;
				break;
			case 8:
				return UserPreference::NotificationsProductUpdate;
				break;
			case 16:
				return UserPreference::NotificationsHadithReview;
				break;
			case 32:
				return UserPreference::ManualUpdateNofication;
				break;
			case 64:
				return UserPreference::PreferArabicHadith;
				break;
			case 128:
				return UserPreference::PrayerReminder;
				break;
			case 256:
				return UserPreference::ProtectionMode;
				break;
			case 1:
			default:
				return UserPreference::NotificationsGeneral;

		}
	}
}

class User extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "name",
            "preferenceFlag",
            "permissionFlag",
            "userId",
            "passwordHash",
            "email"));
    }

	public function isSuperUser() {
		return $this->hasPermission(GeneralUserPermission::SuperUser);
	}

	public function hasPermission($userPermission)
	{
		$generalUserPermissionFlag = GeneralUserPermission::getFlag($userPermission);
		$superUserPermissionFlag = GeneralUserPermission::getFlag(GeneralUserPermission::SuperUser);
		$requiredFurtherPermissionFlag =  GeneralUserPermission::getProp($userPermission, "requiredPermission");
		$generalUserPermissionFlag = $requiredFurtherPermissionFlag === null ? $generalUserPermissionFlag : $generalUserPermissionFlag | $requiredFurtherPermissionFlag;
		return ($this->permissionFlag & $superUserPermissionFlag) == $superUserPermissionFlag 
					|| ($this->permissionFlag & $generalUserPermissionFlag) == $generalUserPermissionFlag;
	}

	public function hasPreference($userPreference)
	{
		if ($this->preferenceFlag == null) {
			return false;
		}
		$userPreferenceFlag = UserPreference::getFlag($userPreference);
		return ($this->preferenceFlag & $userPreferenceFlag) == $userPreferenceFlag;
	}
}

?>
