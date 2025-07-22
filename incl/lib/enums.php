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
	
	const Filter = "-15";
	const Automod = "-16";
	
	const BannedByServer = "-17";
}

class LevelUploadError {
	const Success = "1";
	
	const NothingFound = "-20";

	const UploadingDisabled = "-2";
	const ReuploadingDisabled = "-19";
	const TooFast = "-3";
	
	const FailedToWriteLevel = "-5";
	
	const NotYourLevel = "-14";
	const SameServer = "-18";
}

class CommentsError {
	const NothingFound = "-2";
}

class Action { // Last action ID is 70
	const AccountRegister = 1;
	const UserCreate = 51;
	
	const SuccessfulLogin = 2;
	const FailedLogin = 6;
	const GJPSessionGrant = 16;
	
	// To be done with dashboard
	const SuccessfulAccountActivation = 3;
	const FailedAccountActivation = 4;
	
	const SuccessfulAccountBackup = 5;
	const FailedAccountBackup = 7;
	
	const LevelUpload = 22;
	const LevelChange = 23;
	const LevelDeletion = 8;
	const LevelMalicious = 57;
	
	const ProfileStatsChange = 9;
	const ProfileSettingsChange = 27;
	const UsernameChange = 49;
	const PasswordChange = 50;
	
	const SuccessfulAccountSync = 10;
	const FailedAccountSync = 11;
	
	const AccountCommentUpload = 14;
	const AccountCommentDeletion = 12;
	
	const CommentUpload = 15;
	const CommentDeletion = 13;
	
	const ClanCommentUpload = 60;
	const ClanCommentDeletion = 61;
	
	const ListUpload = 17;
	const ListChange = 18;
	const ListDeletion = 19;
	
	const DiscordLink = 24;
	const DiscordUnlink = 25;
	const DiscordLinkStart = 26;
	const FailedDiscordLinkStart = 47;
	const FailedDiscordLink = 48;
	
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
	const CronClansRanks = 59;
	
	const LevelVoteNormal = 45;
	const LevelVoteDemon = 46;
	
	const GlobalLevelUploadRateLimit = 52;
	const PerUserLevelUploadRateLimit = 53;
	const AccountRegisterRateLimit = 54;
	const UserCreateRateLimit = 55;
	const FilterRateLimit = 56;
	const AccountBackupRateLimit = 58;
	const AudioUploadRateLimit = 68;
	
	const SongUpload = 62;
	const SongDeletion = 63;
	const SongChange = 64;
	const SFXUpload = 65;
	const SFXDeletion = 66;
	const SFXChange = 67;
	
	const ReuploadLevelToGDPS = 69;
	const ReuploadLevelFromGDPS = 70;
	
	// Unused
	const LevelReport = 20;
	const LevelDescriptionChange = 21;
}

class ModeratorAction { // Last action ID is 47
	const LevelRate = 1;
	const LevelDailySet = 5;
	const LevelDeletion = 6;
	const LevelCreatorChange = 7;
	const LevelRename = 8;
	const LevelPasswordChange = 9;
	const LevelCreatorPointsShare = 11;
	const LevelPrivacyChange = 12;
	const LevelDescriptionChange = 13;
	const LevelChangeSong = 16;
	const LevelLockUpdating = 29;
	const LevelLockCommenting = 38;
	const LevelSuggest = 41;
	const LevelEventSet = 44;
	const LevelScoreDelete = 45;

	const PersonBan = 28;
	const PersonUnban = 46;
	const PersonBanChange = 47;
	
	// To be done with dashboard
	const LevelSuggestRemove = 40;
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
	const VaultCodeCreate = 42;
	const VaultCodeChange = 43;
	
	const ListRate = 30;
	const ListSuggest = 31;
	const ListPrivacyChange = 33;
	const ListDeletion = 34;
	const ListCreatorChange = 35;
	const ListRename = 36;
	const ListDescriptionChange = 37;
	const ListLockCommenting = 39;
	
	// Unused
	const LevelFeature = 2;
	const LevelCoinsVerify = 3;
	const LevelEpic = 4;
	const LevelDemonChange = 10;
	const LevelToggleLDM = 14;
	const LeaderboardsBan = 15;
	const ListFeature = 32;
}

class Color {
	const Blue = "b";
	const Green = "g";
	const LightBlue = "l";
	const JeansBlue = "j";
	const Yellow = "y";
	const Orange = "o";
	const Red = "r";
	const Purple = "p";
	const Violet = "a";
	const Pink = "d";
	const LightYellow = "c";
	const SkyBlue = "f";
	const Gold = "s";
	const Undefined = "";
}

class Person {
	const AccountID = 0;
	const UserID = 1;
	const IP = 2;
}

class Ban {
	const Leaderboards = 0;
	const Creators = 1;
	const UploadingLevels = 2;
	const Commenting = 3;
	const Account = 4;
	const UploadingAudio = 5;
}

class SongError {
	const UnknownError = "-6";

	const Banned = "-1";
	const Disabled = "-8";
	const RateLimit = "-9";
	
	const InvalidURL = "-2";
	const InvalidFile = "-4";
	const NotAnAudio = "-7";

	const AlreadyUploaded = "-3";

	const TooBig = "-5";
}

class RateLimit {
	const GlobalLevelsUpload = 0;
	const PerUserLevelsUpload = 1;
	const AccountsRegister = 2;
	const UsersCreation = 3;
	const Filter = 4;
	const LoginTries = 5;
	const ACEExploit = 6;
	const AccountBackup = 7;
	const AudioUpload = 8;
}

class AutomodAction {
	const LevelsSpamWarning = 1;
	
	const LevelUploadingDisable = 2;
	const LevelCommentingDisable = 3;
	const LevelLeaderboardDisable = 4;
	
	const AccountsSpamWarning = 5;
	
	const AccountRegisteringDisable = 6;
	const AccountPostingDisable = 7;
	const AccountUpdatingStatsDisable = 8;
	const AccountMessagingDisable = 9;
	
	const CommentsSpammingWarning = 10;
	const CommentsSpammerWarning = 11;
	
	const AccountPostsSpammingWarning = 12;
	const AccountPostsSpammerWarning = 13;
	
	const PostRepliesSpammingWarning = 14;
	const PostRepliesSpammerWarning = 15;
	
	const ClanPostsSpammingWarning = 16;
	const ClanPostsSpammerWarning = 17;
}
?>