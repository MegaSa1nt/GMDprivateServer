<?php
/*
	Welcome to webhooks translation file!
	You're currently at French (Français) language
	Credits: DimisAIO.be, M336
	
	If you see array instead of simple string, that means you can add as many variations of translation as you want and they will be picked randomly
*/
$webhookLang['rateSuccessTitle'] = ['Un nouveau niveau vient d\'être rated !', 'Nouveau niveau rated !', 'Quelqu\'un vient de rate un niveau !']; // This one is array
$webhookLang['rateSuccessTitleDM'] = ['Ton niveau vient d\'être rated !', 'Quelqu\'un vient de rate ton niveau !'];
$webhookLang['rateSuccessDesc'] = '%1$s a rate un niveau !'; // And this one is string
$webhookLang['rateSuccessDescDM'] = '%1$s vient de rate votre niveau ! %2$s';
$webhookLang['rateFailTitle'] = ['Un niveau vient tout juste d\'être un-rated...', 'Quelqu\'un a un-rate un niveau...'];
$webhookLang['rateFailTitleDM'] = ['Ton niveau a été un-rated...', 'Quelqu\'un vient d\'un-rate votre niveau...'];
$webhookLang['rateFailDesc'] = '%1$s a un-rate un niveau...';
$webhookLang['rateFailDescDM'] = '%1$s vient d\'un-rate votre niveau... %2$s';

$webhookLang['levelTitle'] = 'Niveau';
$webhookLang['levelDesc'] = '%1$s par %2$s'; // Name by Creator
$webhookLang['levelIDTitle'] = 'ID du niveau';
$webhookLang['difficultyTitle'] = 'Difficulté';
$webhookLang['difficultyDesc0'] = '%1$s, %2$s étoile'; // Auto, 1 star
$webhookLang['difficultyDesc1'] = '%1$s, %2$s étoiles'; // Easy, 2 stars
$webhookLang['difficultyDesc2'] = '%1$s, %2$s étoiles'; // Hard, 5 stars
$webhookLang['difficultyDescMoon0'] = '%1$s, %2$s lune'; // Auto, 1 moon (Platformer)
$webhookLang['difficultyDescMoon1'] = '%1$s, %2$s lunes'; // Easy, 2 moons (Platformer)
$webhookLang['difficultyDescMoon2'] = '%1$s, %2$s lunes'; // Hard, 5 moons (Platformer)
$webhookLang['statsTitle'] = 'Statistiques';
$webhookLang['requestedTitle'] = 'Difficulté demandé par le créateur';
$webhookLang['requestedDesc0'] = '%1$s étoile'; // 1 star
$webhookLang['requestedDesc1'] = '%1$s étoiles'; // 2 stars
$webhookLang['requestedDesc2'] = '%1$s étoiles'; // 5 stars
$webhookLang['requestedDescMoon0'] = '%1$s lune'; // 1 moon (Platformer)
$webhookLang['requestedDescMoon1'] = '%1$s lunes'; // 2 moons (Platformer)
$webhookLang['requestedDescMoon2'] = '%1$s lunes'; // 5 moons (Platformer)
$webhookLang['descTitle'] = 'Description';
$webhookLang['descDesc'] = '*Aucune description*';
$webhookLang['footer'] = 'Merci d\'avoir joué sur %1$s !';

$webhookLang['suggestTitle'] = ['Jetez un oeil à ce niveau !', 'Un niveau vient d\'être suggéré !', 'Quelqu\'un a suggéré un niveau !'];
$webhookLang['suggestDesc'] = '%1$s a suggéré un niveau !';
$webhookLang['footerSuggest'] = 'Merci d\'avoir modéré sur %1$s !';

$webhookLang['accountLinkTitle'] = ['Liaison de comptes !', 'Quelqu\'un veut relier son compte au vôtre !'];
$webhookLang['accountLinkDesc'] = 'On dirait que %1$s veut relier son compte en jeu à votre compte Discord. Utilisez la commande **!discord accept *code*** dans votre profil en jeu pour accepter cette liaison de comptes. Si ce n\'est pas vous, **ignorez** ce message !';
$webhookLang['accountCodeFirst'] = 'Premier chiffre';
$webhookLang['accountCodeSecond'] = 'Deuxième chiffre';
$webhookLang['accountCodeThird'] = 'Troisième chiffre';
$webhookLang['accountCodeFourth'] = 'Quatrième chiffre';
$webhookLang['accountUnlinkTitle'] = ['Déliaison de comptes!', 'Vous venez d\'enlever le lien entre vos deux comptes !'];
$webhookLang['accountUnlinkDesc'] = 'Vous venez de retirer le lien entre %1$s et votre compte Discord avec succès !';
$webhookLang['accountAcceptTitle'] = ['Liason de comptes !', 'Vous venez de lier votre compte !'];
$webhookLang['accountAcceptDesc'] = 'Vous venez de relier %1$s à votre compte Discord avec succès !';

$webhookLang['playerBanTitle'] = ['Un joueur vient d\'être banni !', 'Un modérateur a banni un joueur !', 'Ban !'];
$webhookLang['playerBanTitleDM'] = ['Vous avez été banni !', 'Un modérateur vous a banni !', 'Banni !'];
$webhookLang['playerUnbanTitle'] = ['Un joueur vient d\'être unban !', 'Un modérateur a unban quelqu\'un!', 'Unban !'];
$webhookLang['playerUnbanTitleDM'] = ['Vous avez été unbanned !', 'Un modérateur vous a unban !', 'Unbanned !'];
$webhookLang['playerBanTopDesc'] = '%1$s a banni %2$s du classement des meilleurs joueurs !';
$webhookLang['playerBanTopDescDM'] = '%1$s vous a banni du classement des meilleurs joueurs.';
$webhookLang['playerUnbanTopDesc'] = '%1$s a unban %2$s du classement des meilleurs joueurs !';
$webhookLang['playerUnbanTopDescDM'] = '%1$s vous a unban du classement des meilleurs joueurs !';
$webhookLang['playerBanCreatorDesc'] = '%1$s a banni %2$s du classement des meilleurs créateurs !';
$webhookLang['playerBanCreatorDescDM'] = '%1$s vous a banni du classement des meilleurs créateurs.';
$webhookLang['playerUnbanCreatorDesc'] = '%1$s a unban %2$s du classement des meilleurs créateurs !';
$webhookLang['playerUnbanCreatorDescDM'] = '%1$s vous a unban du classement des meilleurs créateurs !';
$webhookLang['playerBanUploadDesc'] = '%1$s a interdit à %2$s d\'uploader d\'autres niveaux!';
$webhookLang['playerBanUploadDescDM'] = '%1$s vous a interdit d\'uploader plus de niveaux.';
$webhookLang['playerUnbanUploadDesc'] = '%1$s a levé l\'interdiction de %2$s d\'uploader d\'autres niveaux !';
$webhookLang['playerUnbanUploadDescDM'] = '%1$s a levé votre interdiction d\'uploader plus de niveaux !';
$webhookLang['playerModTitle'] = 'Modérateur';
$webhookLang['playerReasonTitle'] = 'Raison';
$webhookLang['playerBanReason'] = '*Aucune raison*';
$webhookLang['footerBan'] = '%1$s.';
$webhookLang['playerBanCommentDesc'] = '%1$s a interdit à %2$s de faire des commentaires';
$webhookLang['playerBanCommentDescDM'] = '%1$s vous a interdit de faire des commentaires';
$webhookLang['playerUnbanCommentDesc'] = '%1$s a levé l\'interdiction de %2$s de faire des commentaires !';
$webhookLang['playerUnbanCommentDescDM'] = '%1$s a levé votre interdiction de faire des commentaires !';
$webhookLang['playerBanAccountDesc'] = '%1$s banned %2$s\'s account!';
$webhookLang['playerBanAccountDescDM'] = '%1$s banned your account.';
$webhookLang['playerUnbanAccountDesc'] = '%1$s unbanned %2$s\'s account!';
$webhookLang['playerUnbanAccountDescDM'] = '%1$s unbanned your account!';
$webhookLang['playerExpiresTitle'] = 'Expires';
$webhookLang['playerTypeTitle'] = 'Person type';
$webhookLang['playerTypeName0'] = 'Account ID';
$webhookLang['playerTypeName1'] = 'User ID';
$webhookLang['playerTypeName2'] = 'IP-address';

$webhookLang['dailyTitle'] = 'Nouveau niveau journalier !';
$webhookLang['dailyTitleDM'] = 'Votre niveau a été désigné comme niveau du jour !';
$webhookLang['dailyDesc'] = 'Ce niveau est désormais le niveau du jour !';
$webhookLang['dailyDescDM'] = 'Votre niveau a été désigné comme niveau du jour ! %1$s';
$webhookLang['weeklyTitle'] = 'Nouveau niveau hebdomadaire!';
$webhookLang['weeklyTitleDM'] = 'Votre niveau a été désigné comme niveau de la semaine !';
$webhookLang['weeklyDesc'] = 'Ce niveau est désormais le niveau de la semaine !';
$webhookLang['weeklyDescDM'] = 'Votre niveau a été désigné comme niveau de la semaine ! %1$s';
$webhookLang['eventTitle'] = 'Nouveau niveau événement !';
$webhookLang['eventTitleDM'] = 'Votre niveau a été désigné comme niveau événement !';
$webhookLang['eventDesc'] = 'Ce niveau est désormais un niveau événement !';
$webhookLang['eventDescDM'] = 'Votre niveau a été utilisé lors d\'un événement ! %1$s';

$webhookLang['logsRegisterTitle'] = 'Nouveau compte !';
$webhookLang['logsRegisterDesc'] = 'Quelqu\'un vient de créer un compte !';
$webhookLang['logsUsernameField'] = 'Pseudo du joueur';
$webhookLang['logsPlayerIDField'] = 'ID du joueur';
$webhookLang['logsRegisterTimeField'] = 'Date de création';
$webhookLang['logsIsActivatedField'] = 'Activé ?';
$webhookLang['logsRegisterYes'] = 'Oui';
$webhookLang['logsRegisterNo'] = 'Non';

$webhookLang['logsLevelDeletedTitle'] = 'Niveau supprimé !';
$webhookLang['logsLevelDeletedDesc'] = 'Quelqu\'un a supprimé un niveau !';
$webhookLang['logsLevelChangedTitle'] = 'Niveau modifié !';
$webhookLang['logsLevelChangedDesc'] = 'Quelqu\'un a modifié un niveau !';
$webhookLang['logsLevelUploadedTitle'] = 'Niveau publié !';
$webhookLang['logsLevelUploadedDesc'] = 'Quelqu\'un a publié un niveau !';
$webhookLang['logsLevelChangeNameValue'] = 'Ancien nom :'.PHP_EOL.'%1$s'.PHP_EOL.'Nouveau nom :'.PHP_EOL.'%2$s';
$webhookLang['logsLevelChangeExtIDValue'] = 'Précédent auteur :'.PHP_EOL.'%1$s'.PHP_EOL.'Nouvel auteur:'.PHP_EOL.'%2$s';
$webhookLang['logsLevelChangeDescValue'] = 'Ancienne description :'.PHP_EOL.'%1$s'.PHP_EOL.'New description:'.PHP_EOL.'%2$s';
$webhookLang['logsLevelChangeSongIDValue'] = 'Ancienne musique :'.PHP_EOL.'%1$s'.PHP_EOL.'Nouvelle musique :'.PHP_EOL.'%2$s';
$webhookLang['logsLevelChangeAudioTrackValue'] = 'Ancienne musique officielle :'.PHP_EOL.'%1$s'.PHP_EOL.'Nouvelle musique officielle :'.PHP_EOL.'%2$s';
$webhookLang['logsLevelChangePasswordValue'] = 'Ancien mot de passe :'.PHP_EOL.'||%1$s||'.PHP_EOL.'Nouveau mot de passe :'.PHP_EOL.'||%2$s||';
$webhookLang['logsLevelChangeCoinsValue'] = 'Coins vérifiés précédamment ?'.PHP_EOL.'**%1$s**'.PHP_EOL.'Coins vérifiés maintenant ?'.PHP_EOL.'**%2$s**';
$webhookLang['logsLevelChangeUnlistedValue'] = 'Non-listé avant :'.PHP_EOL.'**%1$s**'.PHP_EOL.'Non-listé maintenant :'.PHP_EOL.'**%2$s**';
$webhookLang['logsLevelChangeUnlisted2Value'] = 'Non-listé avant (2) :'.PHP_EOL.'**%1$s**'.PHP_EOL.'Non-listé maintenant (2) :'.PHP_EOL.'**%2$s**'; // Unused and probably will never be
$webhookLang['logsLevelChangeUpdateLockedValue'] = 'Mises à jour vérouillés avant ?'.PHP_EOL.'**%1$s**'.PHP_EOL.'Mises à jour vérouillés maintenant ?'.PHP_EOL.'**%2$s**';
$webhookLang['logsLevelChangeCommentLockedValue'] = 'Commentaires vérouillés avant ?'.PHP_EOL.'**%1$s**'.PHP_EOL.'Commentaires vérouillés maintenant ?'.PHP_EOL.'**%2$s**';
$webhookLang['logsLevelChangeNameField'] = 'Nom d\'un niveau modifié !';
$webhookLang['logsLevelChangeExtIDField'] = 'L\'auteur de ce niveau a changé';
$webhookLang['logsLevelChangeDescField'] = 'Description d\'un niveau modifié !';
$webhookLang['logsLevelChangeSongIDField'] = 'La musique de ce niveau a changé';
$webhookLang['logsLevelChangeAudioTrackField'] = 'La musique officielle de ce niveau a changé';
$webhookLang['logsLevelChangePasswordField'] = 'Mot de passe du niveau modifié';
$webhookLang['logsLevelChangeCoinsField'] = 'Les coins du niveau ont changés';
$webhookLang['logsLevelChangeUnlistedField'] = 'Les paramètres de confidentialité du niveau ont changés';
$webhookLang['logsLevelChangeUnlisted2Field'] = 'Les paramètres de confidentialité du niveau ont changés (2)'; // Unused and probably will never be
$webhookLang['logsLevelChangeUpdateLockedField'] = 'Le statut de vérrouillage des mises à jour du niveau a changé';
$webhookLang['logsLevelChangeCommentLockedField'] = 'Le statut de vérrouillage des commentaires du niveau a changé';
$webhookLang['logsLevelChangeWhoField'] = 'Qui a modifié ce niveau ?';

$webhookLang['songTitle'] = 'Musique';
$webhookLang['levelIsPublic'] = 'Ce niveau est public';
$webhookLang['levelOnlyForFriends'] = 'Ce niveau ne peut être vu que par les ami(e)s de l\'auteur de celui-ci';
$webhookLang['levelIsUnlisted'] = 'Ce niveau n\'est pas listé';
$webhookLang['unlistedTitle'] = 'Confidentialité du niveau';

$webhookLang['logsAccountChangeWhoField'] = 'Qui a modifié ce compte ?';
$webhookLang['logsAccountChangeUsernameField'] = 'Nom d\'utilisateur modifié';
$webhookLang['logsAccountChangeUsernameValue'] = 'Ancien nom d\'utilisateur :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Nouveau nom d\'utilisateur :'.PHP_EOL.'`%2$s`';
$webhookLang['mS0'] = 'Les messages instantanés de cet utilisateur sont activés';
$webhookLang['mS1'] = 'Les messages instantanés de cet utilisateur ne sont activés que pour ses ami(e)s';
$webhookLang['mS2'] = 'Les messages instantanés de cet utilisateur sont désactivés';
$webhookLang['logsAccountChangeMSField'] = 'Les paramètres de confidentialité des messages instantanés de cet utilisateur ont changés';
$webhookLang['logsAccountChangeMSValue'] = 'Avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Maintenant :'.PHP_EOL.'`%2$s`';
$webhookLang['frS0'] = 'Les demandes d\'amis de cet utilisateur sont activés';
$webhookLang['frS1'] = 'Les demandes d\'amis de cet utilisateur sont désactivés';
$webhookLang['logsAccountChangeFRSField'] = 'Les paramètres de confidentialité des demandes d\'amis de cet utilisateur ont changés';
$webhookLang['logsAccountChangeFRSValue'] = 'Avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Maintenant :'.PHP_EOL.'`%2$s`';
$webhookLang['cS0'] = 'L\'historique de commentaires de cet utilisateur est public';
$webhookLang['cS1'] = 'L\'historique de commentaires de cet utilisateur ne peut être vu que par ses ami(e)s';
$webhookLang['cS2'] = 'L\'historique de commentaires de cet utilisateur est privé';
$webhookLang['logsAccountChangeCSField'] = 'Les paramètres de confidentialité de l\'historique de commentaires de cet utilisateur ont changés';
$webhookLang['logsAccountChangeCSValue'] = 'Avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Maintenant :'.PHP_EOL.'`%2$s`';
$webhookLang['logsAccountChangeYTField'] = 'Le lien de la chaîne YouTube de cet utilisateur a changé';
$webhookLang['logsAccountChangeYTValue'] = 'Avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Maintenant :'.PHP_EOL.'`%2$s`';
$webhookLang['logsAccountChangeNoYT'] = 'Pas de lien vers la chaîne YouTube de cet utilisateur';
$webhookLang['logsAccountChangeTWField'] = 'Le lien du compte X de cet utilisateur a changé';
$webhookLang['logsAccountChangeTWValue'] = 'Avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Maintenant :'.PHP_EOL.'`%2$s`';
$webhookLang['logsAccountChangeNoTW'] = 'Pas de lien vers le compte X de cet utilisateur';
$webhookLang['logsAccountChangeTTVField'] = 'Le lien de la chaîne Twitch de cet utilisateur a changé';
$webhookLang['logsAccountChangeTTVValue'] = 'Avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Maintenant :'.PHP_EOL.'`%2$s`';
$webhookLang['logsAccountChangeNoTTV'] = 'Pas de lien vers la chaîne Twitch de cet utilisateur';
$webhookLang['logsAccountChangeActiveField'] = 'Statut d\'activation d\'un compte modifié';
$webhookLang['logsAccountChangeActiveValue'] = 'Activé avant ?'.PHP_EOL.'**%1$s**'.PHP_EOL.'Activé maintenant ?'.PHP_EOL.'**%2$s**';
$webhookLang['logsAccountChangePasswordField'] = 'Mot de passe modifié';
$webhookLang['logsAccountChangePasswordValue'] = '||... Qu\'est-ce que vous pensiez voir ici ?||';
$webhookLang['logsWhatWasChangedField'] = 'Qu\'est-ce que a changé ?';
$webhookLang['logsAccountChangedTitle'] = 'Un compte a été modifié !';
$webhookLang['logsAccountChangedDesc'] = 'Quelqu\'un a modifié un compte !';

$webhookLang['logsListChangeWhoField'] = 'Qui a modifié cette liste ?';
$webhookLang['logsListDeletedTitle'] = 'Cette liste vient d\'être supprimée !';
$webhookLang['logsListDeletedDesc'] = 'Quelqu\'un a supprimé une liste !';
$webhookLang['logsListUploadedTitle'] = 'Cette liste vient d\'être publiée !';
$webhookLang['logsListUploadedDesc'] = 'Quelqu\'un a publié une liste!';
$webhookLang['listTitle'] = 'Liste';
$webhookLang['listIDTitle'] = 'ID de la liste';
$webhookLang['unlistedListTitle'] = 'Confidentialité de la liste';
$webhookLang['listIsPublic'] = 'Cette liste est publique';
$webhookLang['listIsUnlisted'] = 'Cette liste n\'est pas listée';
$webhookLang['listOnlyForFriends'] = 'Cette liste ne peut être vue que par les ami(e)s de son auteur';
$webhookLang['logsListChangedTitle'] = 'Une liste vient d\'être modifiée !';
$webhookLang['logsListChangedDesc'] = 'Quelqu\'un a modifié cette liste !';
$webhookLang['logsListChangeNameField'] = 'Le nom de cette liste a changé';
$webhookLang['logsListChangeNameValue'] = 'Avant :'.PHP_EOL.'%1$s'.PHP_EOL.'Maintenant :'.PHP_EOL.'%2$s';
$webhookLang['logsListChangeAccountIDField'] = 'L\'auteur de cette liste a changé';
$webhookLang['logsListChangeAccountIDValue'] = 'Avant :'.PHP_EOL.'%1$s'.PHP_EOL.'Maintenant :'.PHP_EOL.'%2$s';
$webhookLang['logsListChangeDescField'] = 'La description de cette liste a changée';
$webhookLang['logsListChangeDescValue'] = 'Avant :'.PHP_EOL.'%1$s'.PHP_EOL.'Maintenant :'.PHP_EOL.'%2$s';
$webhookLang['logsListChangeReward0'] = '**%1$s** diamant'; // 1 diamond
$webhookLang['logsListChangeReward1'] = '**%1$s** diamants'; // 2 diamonds
$webhookLang['logsListChangeReward2'] = '**%1$s** diamants'; // 5 diamonds
$webhookLang['logsListChangeRewardField'] = 'Les récompenses de cette liste ont changées';
$webhookLang['logsListChangeRewardValue'] = 'Avant :'.PHP_EOL.'%1$s'.PHP_EOL.'Après :'.PHP_EOL.'%2$s';
$webhookLang['logsListChangeUnlistedField'] = 'Les paramètres de confidentialité de cette liste ont changés';
$webhookLang['logsListChangeUnlistedValue'] = 'Avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Après :'.PHP_EOL.'`%2$s`';
$webhookLang['logsListChangeDiffField'] = 'La difficulté de cette liste a changée';
$webhookLang['logsListChangeDiffValue'] = 'Avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Après :'.PHP_EOL.'`%2$s`';
$webhookLang['logsListChangeLevelsField'] = 'Les niveaux de cette liste ont changés';
$webhookLang['logsListChangeLevelsValue'] = 'Avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Maintenant :'.PHP_EOL.'`%2$s`';
$webhookLang['logsListChangeRewardCount0'] = '**%1$s** niveau'; // 1 level
$webhookLang['logsListChangeRewardCount1'] = '**%1$s** niveaux'; // 2 levels
$webhookLang['logsListChangeRewardCount2'] = '**%1$s** niveaux'; // 5 levels
$webhookLang['logsListChangeRewardCountField'] = 'Le nombre de niveaux requis pour obtenir les récompenses de cette liste a changé';
$webhookLang['logsListChangeRewardCountValue'] = 'Avant :'.PHP_EOL.'%1$s'.PHP_EOL.'Maintenant :'.PHP_EOL.'%2$s';
$webhookLang['logsListChangeCommentLockedField'] = 'Le statut de vérrouillage des commentaires de cette liste a changé';
$webhookLang['logsListChangeCommentLockedValue'] = 'Commentaires vérouillés avant ?'.PHP_EOL.'**%1$s**'.PHP_EOL.'Commentaires vérouillés maintenant ?'.PHP_EOL.'**%2$s**';
$webhookLang['difficultyListDesc0'] = '%1$s, %2$s diamant'; // Auto, 1 star
$webhookLang['difficultyListDesc1'] = '%1$s, %2$s diamants'; // Easy, 2 stars
$webhookLang['difficultyListDesc2'] = '%1$s, %2$s diamants'; // Hard, 5 stars

$webhookLang['logsModChangeWhoField'] = 'Qui a changé le rôle d\'un utilisateur ?';
$webhookLang['logsModDemotedTitle'] = 'Un utilisateur a été rétrogradé !';
$webhookLang['logsModDemotedDesc'] = 'Quelqu\'un a retrogradé un utilisateur !';
$webhookLang['logsModPromotedTitle'] = 'Un utilisateur a été promu !';
$webhookLang['logsModPromotedDesc'] = 'Quelqu\'un a promu un utilisateur !';
$webhookLang['logsModChangeRoleUnknown'] = '*Rôle inconnu*';
$webhookLang['roleField'] = 'Rôle';
$webhookLang['logsModChangedTitle'] = 'Le rôle d\'un modérateur a changé !';
$webhookLang['logsModChangedDesc'] = 'Quelqu\'un a changé le rôle d\'un modérateur !';
$webhookLang['logsModChangeRoleField'] = 'Le rôle a été changé';
$webhookLang['logsModChangeRoleValue'] = 'Ancien rôle :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Nouveau rôle :'.PHP_EOL.'`%2$s`';

$webhookLang['logsGauntletChangeWhoField'] = 'Qui a changé ce gauntlet ?';
$webhookLang['logsGauntletDeletedTitle'] = 'Gauntlet supprimé !';
$webhookLang['logsGauntletDeletedDesc'] = 'Quelqu\'un a supprimé un gauntlet !';
$webhookLang['logsGauntletCreatedTitle'] = 'Gauntlet créé !';
$webhookLang['logsGauntletCreatedDesc'] = 'Quelqu\'un a créé un gauntlet !';
$webhookLang['gauntletNameField'] = 'Nom du gauntlet';
$webhookLang['level1Field'] = 'Premier niveau';
$webhookLang['level2Field'] = 'Deuxième niveau';
$webhookLang['level3Field'] = 'Troisième niveau';
$webhookLang['level4Field'] = 'Quatrième niveau';
$webhookLang['level5Field'] = 'Cinquième niveau';
$webhookLang['logsGauntletChangedTitle'] = 'Gauntlet modifié !';
$webhookLang['logsGauntletChangedDesc'] = 'Quelqu\'un a modifié un gauntlet !';
$webhookLang['logsGauntletChangeGauntletField'] = 'Ce gauntlet a été modifié';
$webhookLang['logsGauntletChangeGauntletValue'] = 'Ancien Gauntlet :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Nouveau Gauntlet :'.PHP_EOL.'`%2$s`';
$webhookLang['logsGauntletChangeLevel1Field'] = 'Le premier niveau a changé';
$webhookLang['logsGauntletChangeLevelValue'] = 'Ancien niveau :'.PHP_EOL.'%1$s'.PHP_EOL.'Nouveau niveau :'.PHP_EOL.'%2$s';
$webhookLang['logsGauntletChangeLevel2Field'] = 'Le deuxième niveau a changé';
$webhookLang['logsGauntletChangeLevel3Field'] = 'Le troisième niveau a changé';
$webhookLang['logsGauntletChangeLevel4Field'] = 'Le quatrième niveau a changé';
$webhookLang['logsGauntletChangeLevel5Field'] = 'Le cinquième niveau a changé';

$webhookLang['logsMapPackChangeWhoField'] = 'Qui a modifié ce Map Pack?';
$webhookLang['logsMapPackDeletedTitle'] = 'Map Pack supprimé !';
$webhookLang['logsMapPackDeletedDesc'] = 'Quelqu\'un a supprimé un Map Pack !';
$webhookLang['logsMapPackCreatedTitle'] = 'Map Pack créé !';
$webhookLang['logsMapPackCreatedDesc'] = 'Quelqu\'un a créé un Map Pack !';
$webhookLang['packField'] = 'Map Pack';
$webhookLang['packRewardCoins0'] = '%1$s coin'; // 1 coin
$webhookLang['packRewardCoins1'] = '%1$s coins'; // 2 coins
$webhookLang['packRewardCoins2'] = '%1$s coins'; // 5 coins
$webhookLang['packRewardField'] = 'Récompenses';
$webhookLang['packRewardValue'] = '%1$s et %2$s'; // X stars and X coins
$webhookLang['undefinedLevel'] = '*Niveau inconnu*';
$webhookLang['packLevelsField'] = 'Niveaux';
$webhookLang['packColorsField'] = 'Couleurs';
$webhookLang['packColorsValue'] = 'Couleur de la barre de progression : `%1$s`'.PHP_EOL.'Couleur du texte : `%2$s`';
$webhookLang['packTimestampField'] = 'Date de création';
$webhookLang['logsMapPackChangedTitle'] = 'Map Pack modifié !';
$webhookLang['logsMapPackChangedDesc'] = 'Quelqu\'un a modifié un Map Pack!';
$webhookLang['logsMapPackChangeNameField'] = 'Le nom d\'un Map Pack a changé';
$webhookLang['logsMapPackChangeNameValue'] = 'Ancien nom :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Nouveau nom:'.PHP_EOL.'`%2$s`';
$webhookLang['logsMapPackChangeLevelsField'] = 'Les niveaux d\'un Map Pack ont changés';
$webhookLang['logsMapPackChangeLevelsValue'] = 'Anciens niveaux :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Nouveaux niveaux :'.PHP_EOL.'`%2$s`';
$webhookLang['logsMapPackChangeStarsField'] = 'Le nombre d\'étoiles d\'un Map Pack a changé';
$webhookLang['logsMapPackChangeStarsValue'] = 'Etoiles avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Etoiles maintenant :'.PHP_EOL.'`%2$s`';
$webhookLang['logsMapPackChangeCoinsField'] = 'Le nombre de coins d\'un Map Pack a changé';
$webhookLang['logsMapPackChangeCoinsValue'] = 'Coins avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Coins maintenant :'.PHP_EOL.'`%2$s`';
$webhookLang['logsMapPackChangeDifficultyField'] = 'La difficulté d\'un Map Pack a changée';
$webhookLang['logsMapPackChangeDifficultyValue'] = 'Difficulté avant :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Difficulté maintenant :'.PHP_EOL.'`%2$s`';
$webhookLang['logsMapPackChangeColor1Field'] = 'La couleur de la barre de progression d\'un Map Pack a changée';
$webhookLang['logsMapPackChangeColorValue'] = 'Couleur précédente :'.PHP_EOL.'`%1$s`'.PHP_EOL.'Couleur maintenant :'.PHP_EOL.'`%2$s`';
$webhookLang['logsMapPackChangeColor2Field'] = 'La couleur du texte d\'un Map Pack a changée';

$webhookLang['levelsWarningTitle'] = 'Nombre de niveaux publiés élevé !';
$webhookLang['levelsWarningDesc'] = 'Attention ! Une grande quantité de niveaux vient d\'être publié !';
$webhookLang['levelsYesterdayField'] = 'Nombre de niveaux publiés hier';
$webhookLang['levelsTodayField'] = 'Nombre de niveaux publiés aujourd\'hui';
$webhookLang['levelsCompareField'] = 'Combien ?';
$webhookLang['levelsCompareValue'] = '**%1$s** fois';

$webhookLang['accountsWarningTitle'] = 'Nombre de comptes créés élevé !';
$webhookLang['accountsWarningDesc'] = 'Attention ! Une grande quantité de comptes viennent d\'être créés !';
$webhookLang['accountsYesterdayField'] = 'Nombre de comptes créés hier';
$webhookLang['accountsTodayField'] = 'Nombre de comptes créés aujourd\'hui';
$webhookLang['accountsCountValue0'] = '**%1$s** compte'; // 1 account
$webhookLang['accountsCountValue1'] = '**%1$s** comptes'; // 2 accounts
$webhookLang['accountsCountValue2'] = '**%1$s** comptes'; // 5 accounts

$webhookLang['commentsSpammingWarningTitle'] = 'Possible comments spamming!';
$webhookLang['commentsSpammingWarningDesc'] = 'Warning! Possible comments spamming detected!';
$webhookLang['similarCommentsField'] = 'Similar comments amount';
$webhookLang['similarCommentsValue0'] = '**%1$s** comment'; // 1 comment
$webhookLang['similarCommentsValue1'] = '**%1$s** comments'; // 2 comments
$webhookLang['similarCommentsValue2'] = '**%1$s** comments'; // 5 comments
$webhookLang['similarCommentsAuthorsField'] = 'Similar comments authors';
$webhookLang['commentsSpammerWarningTitle'] = 'Possible comments spammer!';
$webhookLang['commentsSpammerWarningDesc'] = 'Warning! Player is making too much similar comments!';
$webhookLang['commentSpammerField'] = 'Similar comments author';
$webhookLang['accountPostsSpammingWarningTitle'] = 'Possible posts spamming!';
$webhookLang['accountPostsSpammingWarningDesc'] = 'Warning! Players are making too much similar posts!';
$webhookLang['similarAccountPostsField'] = 'Similar posts amount';
$webhookLang['similarAccountPostsValue0'] = '**%1$s** post'; // 1 post
$webhookLang['similarAccountPostsValue1'] = '**%1$s** posts'; // 2 posts
$webhookLang['similarAccountPostsValue2'] = '**%1$s** posts'; // 5 posts
$webhookLang['accountPostsSpammerField'] = 'Similar posts author';
$webhookLang['accountPostsSpammerWarningTitle'] = 'Possible posts spammer!';
$webhookLang['accountPostsSpammerWarningDesc'] = 'Warning! Player is making too much similar posts!';
$webhookLang['similarAccountPostsAuthorsField'] = 'Similar posts authors';
$webhookLang['repliesSpammingWarningTitle'] = 'Possible replies spamming!';
$webhookLang['repliesSpammingWarningDesc'] = 'Warning! Players are making too much similar replies!';
$webhookLang['similarRepliesField'] = 'Similar replies amount';
$webhookLang['similarRepliesValue0'] = '**%1$s** reply'; // 1 reply
$webhookLang['similarRepliesValue1'] = '**%1$s** replies'; // 2 replies
$webhookLang['similarRepliesValue2'] = '**%1$s** replies'; // 5 replies
$webhookLang['repliesSpammerField'] = 'Similar replies author';
$webhookLang['repliesSpammerWarningTitle'] = 'Possible replies spammer!';
$webhookLang['repliesSpammerWarningDesc'] = 'Warning! Player is making too much similar replies!';
$webhookLang['similarRepliesAuthorsField'] = 'Similar replies authors';
?>
