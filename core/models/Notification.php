<?php

includeOnce("core/models/BaseModel.php");
includeOnce("core/models/User.php");

abstract class NotificationType extends BaseEnum
{
	// For category see UserPreference->flag
	// For permissionRequired GeneralUserPermission->flag
	const Welcome = '{"flag":1,"label":"Welcome","category":1}';
	const Other = '{"flag":2,"label":"Other","category":1}';
	const HadithUpdate = '{"flag":8,"label":"Hadith Update","category":4}';
	const ProductUpdate = '{"flag":16,"label":"Product Update","category":1}';
	const HadithReviewSubmit = '{"flag":32,"label":"Hadith Review Submit","category":16,"permissionRequired":16}';
	const PermissionChanged = '{"flag":64,"label":"Permissions Changed","category":1}';
	const NewQuote = '{"flag":128,"label":"New Quote","category":8}';
	const PrivateAudioInvitation = '{"flag":256,"label":"Private Audio Invitation","category":1}';
	const PrivateAudioAdded = '{"flag":512,"label":"Private Audio Added","category":1,"permissionRequired":512}';
	const PrivateBookInvitation = '{"flag":1024,"label":"Private Book Invitation","category":1}';
	const PrivateBookAdded = '{"flag":2048,"label":"Private Book Added","category":1,"permissionRequired":1024}';

	public static function getCategory($enum) 
	{
		$enumObj = json_decode($enum);
		return $enumObj->category;
	}
	public static function getPermissionRequired($enum) 
	{
		$enumObj = json_decode($enum);
		if (isset($enumObj->permissionRequired)) {
			return $enumObj->permissionRequired;
		}
		return null;
	}
	
	public static function getClassName($typeFlag) {
		switch (intval($typeFlag)) {
			case 1: // welcome
				return "welcome";
				break;
			case 8: // HadithUpdate
				return "hadith-update";
				break;
			case 16: // ProductUpdate
				return "product-update";
				break;
			case 32: // Review
				return "review";
				break;
			case 64: // Permission
				return "permission";
				break;
			case 128: // NewQuote
				return "quote";
				break;
			case 256: // PrivateAudioInvitation
			case 512: // PrivateAudioAdded
				return "private-audio";
			case 1024: // PrivateBookInvitation
			case 2048: // PrivateBookAdded
				return "private-book";
				break;
			default: // Other
				return "other";
				break;
		}
	}
	
	public static function flagToEnum($typeFlag) {
		switch (intval($typeFlag)) {
			case 1:
				return NotificationType::Welcome;
				break;
			case 4:
				return NotificationType::AudioUpdate;
				break;
			case 8:
				return NotificationType::HadithUpdate;
				break;
			case 16:
				return NotificationType::ProductUpdate;
				break;
			case 32:
				return NotificationType::HadithReviewSubmit;
				break;
			case 64:
				return NotificationType::PermissionChanged;
				break;
			case 128:
				return NotificationType::NewQuote;
				break;
			case 256:
				return NotificationType::PrivateAudioInvitation;
				break;
			case 512:
				return NotificationType::PrivateAudioAdded;
				break;
			case 1024:
				return NotificationType::PrivateBookInvitation;
				break;
			case 2048:
				return NotificationType::PrivateBookAdded;
				break;
			case 2:
			default:
				return NotificationType::Other;

		}
	}
	
	public static function checkValidForUser($user, $type) {
		$typeFlag = NotificationType::getFlag($type);
		$typeCategory = NotificationType::getCategory($type);
		$typePermission = NotificationType::getPermissionRequired($type);

		$userPreferenceReflection = new ReflectionClass("UserPreference");
		$userPreferenceConstants = $userPreferenceReflection->getConstants();
		foreach ($userPreferenceConstants as $userPreferenceConstant) {
			$prefFlag = UserPreference::getFlag($userPreferenceConstant);
			if ($prefFlag === $typeCategory && $user->hasPreference($userPreferenceConstant)) {
				if ($typePermission === null) {
					return true;
				} else {
					// Check for permission
					$userPermissionReflection = new ReflectionClass("GeneralUserPermission");
					$userPermissionConstants = $userPermissionReflection->getConstants();
					foreach ($userPermissionConstants as $userPermissionConstant) {
						$permFlag = GeneralUserPermission::getFlag($userPermissionConstant);
						if ($permFlag === $typePermission && $user->hasPermission($userPermissionConstant)) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}
}


class Notification extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "seen",
            "link",
            "userId",
            "details",
            "title",
            "type",
            "date"));
    }

	public function shortDetails() {
		$pos = strpos($this->details, "\n----");
		if ($pos) {
			return substr($this->details, 0, $pos);
		}
		return $this->details;
	}
}

?>
