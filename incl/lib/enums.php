<?php
class RegisterError {
	const Success = "1";

	const GenericError = "-1";

	const AccountExists = "-2";
	const EmailIsInUse = "-3";

	const InvalidUserName = "-4";
	const InvalidPassword = "-5";
	const InvalidEmail = "-6";

	const PasswordIsTooShort = "-8";
	const UserNameIsTooShort = "-9";

	const PasswordsDoNotMatch = "-7";
	const EmailsDoNotMatch = "-99";	
}

class LoginError {
	const GenericError = "-1";
	const WrongCredentials = "-11";

	const AlreadyLinkedToDifferentAccount = "-10";

	const PasswordIsTooShort = "-8";
	const UserNameIsTooShort = "-9";

	const AccountIsBanned = "-12";
	const AccountIsNotActivated = "-13";
}

class BackupError {
	const GenericError = "-1";

	const WrongCredentials = "-2";
	const BadLoginInfo = "-5";

	const TooLarge = "-4";
	const SomethingWentWrong = "-6";
}

class CommonError {
	const Success = "1";
	
	const InvalidRequest = "-1";
	const SubmitRestoreInfo = "-9";
	
	const Banned = "-10";
	const Disabled = "-2";
}

class LevelUploadError {
	const Success = "1";

	const UploadingDisabled = "-2";
	const TooFast = "-3";
	
	const FailedToWriteLevel = "-5";
}

class CommentsError {
	const NothingFound = "-2";
}

class Action {
	const AccountRegister = 1;
	
	const SuccessfulLogin = 2;
	const FailedLogin = 6;
	
	// To be done with dashboard
	const SuccessfulAccountActivation = 3;
	const FailedAccountActivation = 4;
	
	const SuccessfulAccountBackup = 5;
	const FailedAccountBackup = 7;
	
	const LevelUpload = 22;
	const LevelChange = 23;
	const LevelDeletion = 8;
	
	const ProfileStatsChange = 9;
	const ProfileSettingsChange = 27;
	
	const SuccessfulAccountSync = 10;
	const FailedAccountSync = 11;
	
	const AccountCommentUpload = 14;
	const AccountCommentDeletion = 12;
	
	const CommentUpload = 15;
	const CommentDeletion = 13;
	
	const ListUpload = 17;
	const ListChange = 18;
	const ListDeletion = 19;
	
	// To be done with profile commands
	const DiscordAccountLink = 24;
	const DiscordAccountUnlink = 25;
	const DiscordAccountLinkStart = 26;
	
	const FriendRequestAccept = 28;
	const FriendRequestDeny = 30;
	const FriendRemove = 31;
	const FriendRequestSend = 33;
	
	const BlockAccount = 29;
	const UnblockAccount = 32;
	
	const LevelScoreSubmit = 34;
	const LevelScoreUpdate = 35;
	
	const PlatformerLevelScoreSubmit = 36;
	const PlatformerLevelScoreUpdate = 37;
	
	const VaultCodeUse = 38;
	
	const CronAutoban = 39;
	const CronCreatorPoints = 40;
	const CronUsernames = 41;
	const CronFriendsCount = 42;
	const CronMisc = 43;
	const CronSongsUsage = 44;
	
	// These three actions are no more/never were used
	const GJPSessionGrant = 16;
	const LevelReport = 20;
	const LevelDescriptionChange = 21;
}

class ModeratorAction {
	const LevelRate = 1;
	const LevelFeature = 2;
	const LevelCoinsVerify = 3;
	const LevelEpic = 4;
	const LevelDailySet = 5;
	const LevelDeletion = 6;
	const LevelCreatorChange = 7;
	const LevelRename = 8;
	const LevelPasswordChange = 9;
	const LevelDemonChange = 10;
	const LevelCreatorPointsShare = 11;
	const LevelPrivacyChange = 12;
	const LevelDescriptionChange = 13;
	const LevelToggleLDM = 14;
	const LeaderboardsBan = 15;
	const LevelChangeSong = 16;
	const MapPackCreate = 17;
	const GauntletCreate = 18;
	const SongChange = 19;
	const ModeratorPromote = 20;
	const MapPackChange = 21;
	const GauntletChange = 22;
	const QuestChange = 23;
	const ModeratorRoleChange = 24;
	const QuestCreate = 25;
	const AccountCredentialsChange = 26;
	const SFXChange = 27;
	const PersonBan = 28;
	const LevelLockUpdating = 29;
	const ListRate = 30;
	const ListSuggest = 31;
	const ListFeature = 32;
	const ListPrivacyChange = 33;
	const ListDeletion = 34;
	const ListCreatorChange = 35;
	const ListNameChange = 36;
	const ListDescriptionChange = 37;
	const LevelLockCommenting = 38;
	const ListLockCommenting = 39;
	const LevelSuggestRemove = 40;
	const LevelSuggest = 41;
	const VaultCodeCreate = 42;
	const VaultCodeChange = 43;
	const LevelEventSet = 44;
}
?>