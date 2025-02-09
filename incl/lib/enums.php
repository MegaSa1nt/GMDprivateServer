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
	const Success = "1";

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
?>