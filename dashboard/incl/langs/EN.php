<?php
/*
	Welcome to GDPS core's dashboard language file!
	You're currently at: English
	Credits: *your username*
*/

/* General strings */
$language['account'] = 'Account';
$language['userName'] = 'Username';
$language['password'] = 'Password';
$language['profile'] = 'Profile';
$language['settings'] = 'Settings';
$language['main'] = 'Main';
$language['userProfile'] = '%1$s\'s profile';
$language['clanProfile'] = 'Clan %1$s';
$language['levelProfile'] = 'Level %1$s';
$language['listProfile'] = 'List %1$s';
$language['nothingIsPlaying'] = 'Nothing is playing...';
$language['manage'] = 'Manage';
$language['view'] = 'View';
$language['levels'] = 'Levels'; // Levels in general

/* Panel strings */
$language['hidePanel'] = 'Hide panel';
$language['home'] = 'Home';

$language['changeUsernameTitle'] = 'Change username';
$language['changePasswordTitle'] = 'Change password';
$language['yourLevelsTitle'] = 'Your levels';
$language['yourListsTitle'] = 'Your lists';
$language['yourSongsTitle'] = 'Your songs';
$language['favouriteSongsTitle'] = 'Favourite songs';
$language['yourSFXsTitle'] = 'Your SFXs';

$language['browse'] = 'Browse';
$language['accountsTitle'] = 'Accounts'; // "Accounts" as page title
$language['levelsTitle'] = 'Levels'; // "Levels" as page title
$language['listsTitle'] = 'Lists';
$language['mapPacksTitle'] = 'Map Packs';
$language['gauntletsTitle'] = 'Gauntlets';
$language['songsTitle'] = 'Songs';
$language['sfxsTitle'] = 'SFXs';

$language['upload'] = 'Uploads';
$language['uploadSongTitle'] = 'Add a song';
$language['uploadSFXTitle'] = 'Add a SFX';
$language['reuploadLevelTitle'] = 'Reupload a level';
$language['runCron'] = 'Run Cron';

$language['moderatorTools'] = 'Moderator tools';
$language['addMapPackTitle'] = 'Add Map Pack';
$language['addGauntletTitle'] = 'Add Gauntlet';
$language['unlistedLevelsTitle'] = 'Unlisted levels';
$language['unlistedListsTitle'] = 'Unlisted lists';
$language['manageRolesTitle'] = 'Manage roles';

$language['clans'] = 'Clans';
$language['clansListTitle'] = 'Clans list';

$language['messengerTitle'] = 'Messenger';
$language['loginToAccountTitle'] = 'Login to account';
$language['yourProfileTitle'] = 'Your profile';
$language['logoutFromAccountTitle'] = 'Logout';
$language['registerAccountTitle'] = 'Register account';

/* Error strings */
$language['errorTitle'] = 'An error has occured';

$language['errorNoPermission'] = 'You have no permission to view this page.';

$language['errorFailedToLoadPage'] = 'Failed to load page!';
$language['errorAlreadyLoggedIn'] = 'You\'re already logged in!';
$language['errorWrongLoginOrPassword'] = 'Wrong username or password!';
$language['errorLoginRequired'] = 'Login to account!';

$language['errorUsernameIsTaken'] = 'This username is taken.';
$language['errorBadUsername'] = 'Please choose another username.';

$language['errorSamePasswords'] = 'Old and new passwords are the same.';
$language['errorBadPassword'] = 'Please choose another password.';

$language['errorLevelNotFound'] = 'Level wasn\'t found!';

$language['errorCantDeleteComment'] = 'You can\'t delete this comment.';
$language['errorCantDeleteScore'] = 'You can\'t delete this score.';
$language['errorBadComment'] = 'Your comment contains a bad word.';
$language['errorCommentingIsDisabled'] = 'Commenting is currently disabled.';
$language['errorLevelCommentingIsDisabled'] = 'Commenting on this level is currently disabled.';
$language['errorListCommentingIsDisabled'] = 'Commenting on this list is currently disabled.';

$language['errorCantDeletePost'] = 'You can\'t delete this post.';
$language['errorBadPost'] = 'Your post contains a bad word.';
$language['errorPostingIsDisabled'] = 'Creating posts is disabled.';

$language['errorSongNotFound'] = 'Song wasn\'t found!';

$language['errorListNotFound'] = 'List wasn\'t found!';

$language['errorCouldntReadFile'] = 'An error has occured when processing this file!';
$language['errorIsNotAnAudio'] = 'This is not an audio!';
$language['errorMaxFileSize'] = 'Maximum file size is %1$s MB!';
$language['errorFileIsEmpty'] = 'This file is empty!';
$language['errorInvalidURL'] = 'Invalid song URL!';
$language['errorAlreadyReuploaded'] = 'This song already exists under ID <text dashboard-copy>%1$s</text>!';

$language['errorPageIsDisabled'] = 'This page is disabled!';
$language['errorSongRateLimit'] = 'You\'re uploading too many audio in a short amount of time, try again in a few minutes!';

$language['errorReuploadSameServer'] = 'You specified same server for reuploading!';
$language['errorReuploadTooFast'] = 'You\'re reuploading too many levels in a short amount of time, try again in a few minutes!';
$language['errorReuploadAutomod'] = 'You may not reupload levels for now!';
$language['errorUploadingLevelsDisabled'] = 'Uploading levels is currently disabled!';
$language['errorServerBanned'] = 'You may not reupload levels to this server, as it banned yours!';
$language['errorIncorrectCredentials'] = 'You entered incorrect credentials for your account!';
$language['errorServerConnection'] = 'An error has occured while trying to connect to this server!';
$language['errorNotYourLevel'] = 'This level is not yours!';
$language['errorFailedToWriteLevel'] = 'An error has occured while trying to save reuploaded level!';

$language['errorCronTooFast'] = 'Please wait a few minutes before running Cron again!';

$language['errorPlayerNotFound'] = 'Player wasn\'t found!';
$language['errorBadName'] = 'Please choose another name!';
$language['errorBadDesc'] = 'Please choose another description!';

$language['errorAccountsAutomod'] = 'You may not create accounts for now!';
$language['errorUsernameTooShort'] = 'This username is too short!';
$language['errorPasswordTooShort'] = 'This password is too short!';
$language['errorPasswordsDontMatch'] = 'Your passwords don\'t match!';
$language['errorEmailsDontMatch'] = 'Your emails don\'t match!';
$language['errorBadEmail'] = 'Please choose another email!';
$language['errorEmailInUse'] = 'There is an account using this email!';

$language['errorGauntletNotFound'] = 'Gauntlet wasn\'t found!';
$language['errorGauntletWrongLevelsCount'] = 'Gauntlet can have only 5 levels!';
$language['errorMultipleLevelsNotFound'] = 'You specified non-existing levels!';

$language['errorFailedToGetGMD'] = 'An error occured while trying to get level data!';

$language['errorMapPackNotFound'] = 'Map Pack wasn\'t found!';
$language['errorMapPackNoLevels'] = 'You didn\'t specify any levels!';

$language['errorListNotFound'] = 'List wasn\'t found!';

$language['errorGauntletAlreadyExists'] = 'This Gauntlet already exists!';

$language['errorAccountNotFound'] = 'Account wasn\'t found!';

$language['errorRoleNotFound'] = 'Role wasn\'t found!';

/* Success strings */
$language['successCopiedText'] = 'Copied text!';

$language['successLoggedIn'] = 'You successfully logged in!';
$language['successLoggedOut'] = 'You successfully logged out!';
$language['successChangedUsername'] = 'You successfully changed username! Relogin to account.';

$language['successChangedPassword'] = 'You successfully changed password! Relogin to account.';

$language['successDeletedComment'] = 'You deleted this comment!';
$language['successDeletedScore'] = 'You deleted this score!';
$language['successUploadedComment'] = 'You successfully uploaded a comment!';

$language['successFavouritedSong'] = 'You successfully favourited this song!';
$language['successUnfavouritedSong'] = 'You successfully unfavourited this song!';

$language['successAppliedSettings'] = 'You successfully applied settings!';

$language['successDeletedPost'] = 'You deleted this post!';
$language['successUploadedPost'] = 'You successfully created a post!';

$language['successUploadedSong'] = 'You successfully uploaded a song!';
$language['successUploadedSFX'] = 'You successfully uploaded a SFX!';

$language['successReuploadToServer'] = 'You successfully reuploaded a level to this server!';
$language['successReuploadFromServer'] = 'You successfully reuploaded a level to another server! Level ID: <text dashboard-copy>%1$s</text>';

$language['successRanCron'] = 'You successfully executed Cron!';

$language['successCreatedAccount'] = 'You successfully created an account!';

$language['successDownloadNow'] = 'Download will start in a few seconds...';

$language['successAddedMapPack'] = 'You successfullty added a Map Pack!';
$language['successAddedGauntlet'] = 'You successfullty added a Gauntlet!';

$language['successCreatedRole'] = 'You successfullty created a role!';

/* Page strings */
$language['changeUsernameOld'] = 'Old username';
$language['changeUsernameNew'] = 'New username';

$language['loginToAccountButton'] = 'Login';

$language['changePasswordOld'] = 'Old password';
$language['changePasswordNew'] = 'New password';

$language['levelTitle'] = '<text class="big">%1$s</text> by %2$s'; // %1$s — level name, %2$s — username
$language['levelTitlePlain'] = '%1$s by %2$s'; // %1$s — level name, %2$s — username
$language['stars'] = 'Stars';
$language['requestedStars'] = 'Requested stars';
$language['noDescription'] = 'No description provided';
$language['levelID'] = 'Level ID';
$language['levelLength'] = 'Level length';
$language['downloads'] = 'Downloads';
$language['likes'] = 'Likes';
$language['dislikes'] = 'Dislikes';
$language['rating'] = 'Rating';
$language['viewLevel'] = 'View level';
$language['viewComments'] = 'View comments';
$language['viewLeaderboards'] = 'View leaderboards';
$language['viewSongs'] = 'View songs';
$language['viewSFXs'] = 'View SFXs';
$language['unknownSong'] = 'Unknown song';
$language['uploadDate'] = 'Upload date';
$language['noLevels'] = 'No levels!';

$language['comments'] = 'Comments';
$language['deleteComment'] = 'Delete comment';
$language['scores'] = 'Scores';
$language['nothingOpened'] = 'Nothing is opened!';
$language['manageLevel'] = 'Manage level';
$language['noComments'] = 'No comments!';
$language['noScores'] = 'No scores!';

$language['sortByLikes'] = 'Sort by likes';
$language['sortByTime'] = 'Sort by time';
$language['friends'] = 'Friends';
$language['all'] = 'All';
$language['forWeek'] = 'For week';
$language['sortByPoints'] = 'Sort by points';
$language['normalScores'] = 'Normal scores';
$language['dailyScores'] = 'Scores from \'Daily\' tab';

$language['percent'] = 'Percent';
$language['attempts'] = 'Attempts';
$language['coins'] = 'Coins';
$language['clicks'] = 'Clicks';
$language['time'] = 'Time';
$language['points'] = 'Points';

$language['writeSomething'] = 'Write something!';
$language['bannedToast'] = 'You\'re banned: "%1$s", ban will expire %2$s'; // %1$s — ban reason, %2$s — in X time

$language['songs'] = 'Songs';
$language['sfxs'] = 'SFXs';

$language['pageText'] = 'Page %1$s of %2$s';

$language['dashboardSettingsTitle'] = 'Dashboard settings';

$language['languageTitle'] = 'Language';
$language['languageDesc'] = 'Dashboard has many languages, choose one you know most!';

$language['notInClan'] = 'Not in clan';

$language['songTitle'] = '<text class="big">%1$s</text> — <text class="big">%2$s</text>'; // %1$s — song artist, %2$s — song title
$language['songID'] = 'Song ID';
$language['usageCount'] = 'Usage';
$language['favouritesCount'] = 'Favourites';
$language['noSongs'] = 'No songs!';
$language['editSong'] = 'Edit song';
$language['downloadSong'] = 'Download song';

$language['filters'] = 'Filters';
$language['searchText'] = 'Search...';

$language['originalLevelsTitle'] = 'Original levels';
$language['originalLevelsDesc'] = 'Levels made without copying other levels';
$language['coinsTitle'] = 'Coins';
$language['coinsDesc'] = 'Levels with coins';
$language['twoPlayerTitle'] = 'Two-player mode';
$language['twoPlayerDesc'] = 'Levels with two-player mode';
$language['notRatedTitle'] = 'Unrated levels';
$language['notRatedDesc'] = 'Levels with no stars';
$language['ratedTitle'] = 'Rated levels';
$language['ratedDesc'] = 'Levels with stars';
$language['featuredDesc'] = 'Levels with Featured rating';
$language['epicDesc'] = 'Levels with Epic rating';
$language['legendaryDesc'] = 'Levels with Legendary rating';
$language['mythicDesc'] = 'Levels with Mythic rating';
$language['songSettingTitle'] = 'Songs';
$language['songSettingDesc'] = 'Levels with custom songs';

$language['levelLengthTinyDesc'] = 'Levels with Tiny length';
$language['levelLengthShortDesc'] = 'Levels with Short length';
$language['levelLengthMediumDesc'] = 'Levels with Medium length';
$language['levelLengthLongDesc'] = 'Levels with Long length';
$language['levelLengthXLDesc'] = 'Levels with XL length';
$language['levelLengthPlatformerDesc'] = 'Platformer levels';

$language['resetFilters'] = 'Reset filters';
$language['applyFilters'] = 'Apply filters';

$language['reset'] = 'Reset';
$language['applySettings'] = 'Apply settings';

$language['noPosts'] = 'No posts!';
$language['accountID'] = 'Account ID';
$language['userID'] = 'User ID';
$language['moons'] = 'Moons';
$language['diamonds'] = 'Diamonds';
$language['goldCoins'] = 'Gold coins';
$language['userCoins'] = 'User coins';
$language['demons'] = 'Demons';
$language['creatorPoints'] = 'Creator Points';
$language['registerDate'] = 'Register date';
$language['personPosts'] = 'User posts';
$language['personComments'] = 'User comments';
$language['personScores'] = 'User scores';
$language['personSongs'] = 'User songs';
$language['personSFXs'] = 'User SFXs';
$language['personBans'] = 'User bans';

$language['unknownLevel'] = 'Unknown level';
$language['unknownList'] = 'Unknown list';

$language['sfxID'] = 'SFX ID';
$language['noSFXs'] = 'No SFXs!';
$language['downloadSFX'] = 'Download SFX';
$language['editSFX'] = 'Edit SFX';
$language['library'] = 'Library';

$language['rank'] = 'Rank';

$language['accountSettings'] = 'Account settings';
$language['viewAccount'] = 'View account';
$language['banPerson'] = 'Ban user';
$language['blockPerson'] = 'Block user';

$language['deleteLevel'] = 'Delete level';
$language['copyLink'] = 'Copy link';

$language['emptyComment'] = 'Empty comment';
$language['emptyPost'] = 'Empty post';
$language['emptyMessage'] = 'Empty message';

$language['viewList'] = 'View list';
$language['manageList'] = 'Manage list';
$language['deleteList'] = 'Delete list';

$language['deletePost'] = 'Delete post';
$language['deleteScore'] = 'Delete score';
$language['deleteSong'] = 'Delete song';
$language['deleteSFX'] = 'Delete SFX';

$language['listID'] = 'List ID';
$language['countForReward'] = 'Amount of levels to beat';
$language['noLists'] = 'No lists!';
$language['manageList'] = 'Manage list';

$language['mapPackID'] = 'Map Pack ID';
$language['viewMapPack'] = 'View Map Pack';
$language['manageMapPack'] = 'Manage Map Pack';
$language['deleteMapPack'] = 'Delete Map Pack';
$language['noMapPacks'] = 'No Map Packs!';

$language['gauntletID'] = 'Gauntlet ID';
$language['viewGauntlet'] = 'View Gauntlet';
$language['manageGauntlet'] = 'Manage Gauntlet';
$language['deleteGauntlet'] = 'Delete Gauntlet';
$language['noGauntlets'] = 'No Gauntlets!';

$language['clanID'] = 'Clan ID';
$language['creationDate'] = 'Creation date';
$language['clanOwnerTitle'] = 'Owner of clan %1$s';
$language['clanClosed'] = 'Closed clan';
$language['yourClan'] = 'Your clan';
$language['clanMembers'] = 'Clan members';
$language['clanPosts'] = 'Clan posts';
$language['clanSettings'] = 'Clan settings';
$language['noClanPosts'] = 'No clan posts!';
$language['joinClan'] = 'Join clan';
$language['sendClanJoinRequest'] = 'Send join request';
$language['viewClan'] = 'View clan';
$language['clan'] = 'Clan';

$language['uploadSongViaFile'] = 'Upload song via file';
$language['uploadSongViaURL'] = 'Upload song via URL';

$language['uploadType'] = 'Upload type';
$language['chooseUploadType'] = 'Choose upload type...';
$language['loadingTitle'] = 'Loading...';
$language['songFile'] = 'Song file';
$language['songURL'] = 'Song URL';
$language['chooseSong'] = 'Choose song...';
$language['songArtistText'] = 'Song artist';
$language['songTitleText'] = 'Song title';
$language['songSize'] = 'Song size';
$language['songSizeTemplate'] = '%1$s MB';

$language['uploadSongProcessing'] = 'Processing your audio...';
$language['uploadSongConverting'] = 'Converting your audio to the proper format...';
$language['uploadSongUploading'] = 'Uploading your audio...';
$language['done'] = 'Done!';

$language['sfxFile'] = 'SFX file';
$language['chooseSFX'] = 'Choose SFX...';
$language['sfxTitleText'] = 'SFX title';

$language['reuploadType'] = 'Reupload type';
$language['chooseReuploadType'] = 'Choose reupload type...';
$language['reuploadLevelToServer'] = 'Reupload a level to %1$s'; // Reupload level to *GDPS NAME*
$language['reuploadLevelFromServer'] = 'Reupload a level from %1$s'; // Reupload level from *GDPS NAME*
$language['serverURL'] = 'Server URL';

$language['reuploadLevelToServerDesc'] = 'Reupload a level from another GDPS to the current GDPS';
$language['reuploadLevelFromServerDesc'] = 'Reupload a level from the current GDPS to another GDPS';

$language['clanMembersCount'] = 'Clan members count';

$language['registeredPlayer'] = 'Registered player';
$language['unregisteredPlayer'] = 'Unregistered player';

$language['levelName'] = 'Level name';
$language['levelAuthor'] = 'Level creator';
$language['levelDesc'] = 'Level description';
$language['levelRateType'] = 'Level rate type';
$language['chooseRateType'] = 'Choose level rate type...';
$language['noRating'] = 'No rating';
$language['songType'] = 'Song type';
$language['chooseSongType'] = 'Choose song type...';
$language['officialSong'] = 'Official song';
$language['customSong'] = 'Custom song';
$language['song'] = 'Song';
$language['levelPrivacy'] = 'Level privacy';
$language['chooseLevelPrivacy'] = 'Choose level privacy...';
$language['publicLevel'] = 'Public level';
$language['privateLevel'] = 'Private level';
$language['unlistedLevel'] = 'Unlisted level';
$language['onlyForFriends'] = 'Only for friends';
$language['difficulty'] = 'Difficulty';
$language['chooseDifficulty'] = 'Choose difficulty...';
$language['silverCoins'] = 'Silver coins';
$language['silverCoinsDesc'] = 'Should coins on a level be silver';
$language['updatesLock'] = 'Lock updates';
$language['updatesLockDesc'] = 'If you enable this setting, level creator won\'t be able to update this level anymore';
$language['commentingLock'] = 'Lock commenting';
$language['commentingLockDesc'] = 'If you enable this setting, players wouldn\'t be able to comment on this level anymore';
$language['passwordRemoveHint'] = 'Set 0 or nothing, if you want to remove password';

$language['repeatPassword'] = 'Repeat password';
$language['email'] = 'Email';
$language['repeatEmail'] = 'Repeat email';
$language['invalidPasswordHint'] = 'Your password contains symbols, that you can\'t write in Geometry Dash!';

$language['public'] = 'Public';
$language['private'] = 'Private';
$language['unlisted'] = 'Unlisted';

$language['downloadLevel'] = 'Download level';

$language['mapPackName'] = 'Map Pack name';
$language['textColor'] = 'Text color';
$language['barColor'] = 'Bar color';

$language['listName'] = 'List name';
$language['listAuthor'] = 'List creator';
$language['listDesc'] = 'List description';
$language['listRateType'] = 'List rate type';
$language['chooseListRateType'] = 'Choose list rate type...';
$language['listPrivacy'] = 'List privacy';
$language['chooseListPrivacy'] = 'Choose list privacy...';
$language['publicList'] = 'Public list';
$language['privateList'] = 'Private list';
$language['unlistedList'] = 'Unlisted list';
$language['updatesLockListDesc'] = 'If you enable this setting, list creator won\'t be able to update this list anymore';
$language['commentingLockListDesc'] = 'If you enable this setting, players wouldn\'t be able to comment on this list anymore';

$language['gauntletType'] = 'Gauntlet type';
$language['chooseGauntletType'] = 'Choose Gauntlet type...';

$language['messagesPrivacyTitle'] = 'Messages privacy';
$language['messagesPrivacyDesc'] = 'Who is able to send messages?';
$language['friendRequestsTitle'] = 'Friend requests';
$language['friendRequestsDesc'] = 'Who is able to send friend requests?';
$language['commentHistoryTitle'] = 'Comment history privacy';
$language['commentHistoryDesc'] = 'Who is able to see comment history?';
$language['youTubeChannel'] = 'YouTube channel';
$language['twitterAccount'] = 'X account';
$language['twitchChannel'] = 'Twitch channel';
$language['timezone'] = 'Timezone';
$language['chooseTimezone'] = 'Choose timezone...';
$language['none'] = 'None';

$language['creatorRatingLevel'] = 'Level creator rating';
$language['creatorRatingList'] = 'List creator rating';
$language['creatorRatingLike'] = 'Creator liked';
$language['creatorRatingDislike'] = 'Creator disliked';

$language['createRole'] = 'Create a role';
$language['roleAppearance'] = 'Role appearance';
$language['roleName'] = 'Role name';
$language['roleCommentsCaption'] = 'Role comments caption';
$language['roleCommentsCaptionHint'] = 'Role caption will appear in comments before comment upload date';
$language['roleColor'] = 'Role color';
$language['rolePriority'] = 'Role priority';
$language['rolePriorityHint'] = 'The higher this value, the higher role is in the list';
$language['modBadge'] = 'Moderator badge';
$language['chooseModBadge'] = 'Choose moderator badge...';
$language['elderModeratorRole'] = 'Elder Moderator';
$language['moderatorRole'] = 'Moderator';
$language['leaderboardModeratorRole'] = 'Leaderboard Moderator';
$language['rolePermissions'] = 'Role permissions';
$language['roleIsDefaultTitle'] = 'Default role';
$language['roleIsDefaultDesc'] = 'Every user will have this role';
$language['permissionGameSuggestLevelTitle'] = 'Suggest levels';
$language['permissionGameSuggestLevelDesc'] = 'Allows user to suggest ratings for levels and lists';
$language['permissionGameRateLevelTitle'] = 'Rate levels';
$language['permissionGameRateLevelDesc'] = 'Allows user to set stars for levels and lists';
$language['permissionGameSetDifficultyTitle'] = 'Set difficulty';
$language['permissionGameSetDifficultyDesc'] = 'Allows user to set difficulty of levels and lists';
$language['permissionGameSetFeaturedTitle'] = 'Set level as Featured';
$language['permissionGameSetFeaturedDesc'] = 'Allows user to set levels and lists as Featured';
$language['permissionGameSetEpicTitle'] = 'Set level as Epic';
$language['permissionGameSetEpicDesc'] = 'Allows user to set levels as Epic, Legendary or Mythic';
$language['permissionGameDeleteLevelTitle'] = 'Delete levels';
$language['permissionGameDeleteLevelDesc'] = 'Allows user to delete levels and lists';
$language['permissionGameMoveLevelTitle'] = 'Move levels';
$language['permissionGameMoveLevelDesc'] = 'Allows user to move levels and lists to a different account';
$language['permissionGameRenameLevelTitle'] = 'Rename levels';
$language['permissionGameRenameLevelDesc'] = 'Allows user to rename levels and lists';
$language['permissionGameSetPasswordTitle'] = 'Set password of a level';
$language['permissionGameSetPasswordDesc'] = 'Allows user to set and remove password of levels';
$language['permissionGameSetDescriptionTitle'] = 'Set description of a level';
$language['permissionGameSetDescriptionDesc'] = 'Allows user to set description of levels and lists';
$language['permissionGameSetLevelPrivacyTitle'] = 'Set level privacy';
$language['permissionGameSetLevelPrivacyDesc'] = 'Allows user to set view privacy of levels and lists';
$language['permissionGameShareCreatorPointsTitle'] = 'Share Creator Points of a level';
$language['permissionGameShareCreatorPointsDesc'] = 'Allows user to share Creator Points of levels';
$language['permissionGameSetLevelSongTitle'] = 'Set song of a level';
$language['permissionGameSetLevelSongDesc'] = 'Allows user to set or remove song of levels';
$language['permissionGameLockLevelCommentsTitle'] = 'Lock commenting a level';
$language['permissionGameLockLevelCommentsDesc'] = 'Allows user to lock or unlock commenting of levels';
$language['permissionGameLockLevelUpdatingTitle'] = 'Lock updating a level';
$language['permissionGameLockLevelUpdatingDesc'] = 'Allows user to lock or unlock updating levels';
$language['permissionGameSetListLevelsTitle'] = 'Set levels of a list';
$language['permissionGameSetListLevelsDesc'] = 'Allows user to set levels on lists';
$language['permissionGameDeleteCommentsTitle'] = 'Delete comments';
$language['permissionGameDeleteCommentsDesc'] = 'Allows user to delete any comments on levels and lists';
$language['permissionGameVerifyCoinsTitle'] = 'Verify coins of a level';
$language['permissionGameVerifyCoinsDesc'] = 'Allows user to verify or unverify coins on levels';
$language['permissionGameSetDailyTitle'] = 'Set level as Daily level';
$language['permissionGameSetDailyDesc'] = 'Allows user to set levels as Daily levels';
$language['permissionGameSetWeeklyTitle'] = 'Set level as Weekly level';
$language['permissionGameSetWeeklyDesc'] = 'Allows user to set levels as Weekly levels';
$language['permissionGameSetEventTitle'] = 'Set level as Event level';
$language['permissionGameSetEventDesc'] = 'Allows user to set levels as Event levels';
$language['permissionDashboardModeratorToolsTitle'] = 'Access moderator tools';
$language['permissionDashboardModeratorToolsDesc'] = 'Allows user to access moderator tools, such as banning tool, unlisted levels, etc';
$language['permissionDashboardDeleteLeaderboardsTitle'] = 'Delete leaderboard scores';
$language['permissionDashboardDeleteLeaderboardsDesc'] = 'Allows user to delete leaderboard scores on levels';
$language['permissionDashboardManageMapPacksTitle'] = 'Manage Map Packs';
$language['permissionDashboardManageMapPacksDesc'] = 'Allows user to create, manage and delete Map Packs';
$language['permissionDashboardManageGauntletsTitle'] = 'Manage Gauntlets';
$language['permissionDashboardManageGauntletsDesc'] = 'Allows user to create, manage and delete Gauntlets';
$language['permissionDashboardManageSongsTitle'] = 'Manage songs and SFXs';
$language['permissionDashboardManageSongsDesc'] = 'Allows user to activate, deactivate, manage and delete songs and SFXs';
$language['permissionDashboardManageAccountsTitle'] = 'Manage accounts';
$language['permissionDashboardManageAccountsDesc'] = 'Allows user to manage settings of accounts and change its username and password';
$language['permissionDashboardManageLevelsTitle'] = 'Manage levels';
$language['permissionDashboardManageLevelsDesc'] = 'Allows user to manage settings of levels';
$language['permissionDashboardManageClansTitle'] = 'Manage clans';
$language['permissionDashboardManageClansDesc'] = 'Allows user to manage and delete clans';
$language['permissionDashboardManageAutomodTitle'] = 'Manage Automod';
$language['permissionDashboardManageAutomodDesc'] = 'Allows user to manage Automod and view user actions history';
$language['permissionDashboardManageRolesTitle'] = 'Manage roles';
$language['permissionDashboardManageRolesDesc'] = 'Allows user to create, manage and delete roles';
$language['permissionDashboardManageVaultCodesTitle'] = 'Manage Wraith codes';
$language['permissionDashboardManageVaultCodesDesc'] = 'Allows user to create, manage and delete Wraith codes';
$language['permissionDashboardSetAccountRolesTitle'] = 'Set roles for an account';
$language['permissionDashboardSetAccountRolesDesc'] = 'Allows user to set and remove roles of accounts';
$language['disallow'] = 'Disallow';
$language['inherit'] = 'Inherit';
$language['allow'] = 'Allow';
$language['chooseRole'] = 'Choose a role!';
?>