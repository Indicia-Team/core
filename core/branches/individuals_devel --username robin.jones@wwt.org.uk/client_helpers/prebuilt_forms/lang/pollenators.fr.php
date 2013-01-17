<?php
/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

global $custom_terms;

/**
 * Language terms for the pollenators form.
 *
 * @package	Client
 */
$custom_terms = array(
	'LANG_Insufficient_Privileges' => "Cr�ez-vous un compte ou connectez-vous pour acc�der � la page de cr�ation de collection"
	,'LANG_Please_Refresh_Page' => "Une erreur s'est produite. S'il vous pla�t, actualisez la page."
	,'LANG_Collection_Name_Label' => 'Nommer votre collection '
	,'Protocol' => 'Choisir un protocole '
	,'LANG_Modify' => 'MODIFIER'
	,'LANG_Reinitialise' => 'R�INITIALISER'
	,'LANG_Collection_Details' => 'D�tails de la collection'
	,'LANG_Protocol_Title_Label' => 'Protocole'
	,'LANG_Validate' => 'VALIDER'
	,'LANG_Unable_To_Reinit' => 'Impossible de r�initialiser, les valeurs saisies ne peuvent �tre valid�es'
	,'LANG_Confirm_Reinit' => '�tes-vous s�r de vouloir r�initialiser ? Toutes les donn�es de cette collection seront supprim�es.'
	,'LANG_Collection_Trailer_Point_1' => "Vous ne pouvez cr�er qu'une seule collection � la fois."
	,'LANG_Collection_Trailer_Point_2' => 'Autrement dit, vous pourrez cr�er une autre collection lorsque la pr�sente collection sera achev�e ou r�initialis�e.'
	
	,'LANG_Flower_Station' => "VOTRE STATION FLORALE"
	,'LANG_Upload_Flower' => "Charger l'image de la fleur"
	,'LANG_Identify_Flower' => 'Indiquer le nom de cette fleur'
	,'LANG_ID_Flower_Later' => "Vous pr�f�rez l'identifier plus tard :"
	,'LANG_Flower_Species' => "Vous connaissez le taxon correspondant � cette fleur "
	,'LANG_Flower_ID_Key_label' => "Vous ne connaissez pas le nom de cette fleur"
	,'LANG_Launch_ID_Key' => "Lancer la cl� d'identification"
	,'LANG_Cancel_ID' => "Abandonner la cl� d'identification"
	,'LANG_Taxa_Returned' => "Taxons retourn� par la cl� d'identification:"
	,'LANG_ID_Unrecognised' => 'Les suivants ne sont pas reconnus: '
	,'LANG_Taxa_Unknown_In_Tool' => 'Taxon inconnu de la cl�'
	,'LANG_ID_More_Precise' => 'Vous connaissez une d�nomination plus pr�cise :'
	,'LANG_ID_Comment' => 'Commentez �ventuellement votre identification :'
	,'LANG_Choose_Taxon' => "Choisissez un taxon dans la liste"
	,'LANG_Upload_Environment' => "Charger l'image de son environnement"
	,'LANG_Georef_Label' => "Entrer le nom d'une commune, d'une r�gion, d'un d�partement ou d'un code postal "
    ,'msgGeorefSelectPlace' => "S�lectionnez dans les endroits suivants qui correspondent � vos crit�res de recherche, puis cliquez sur la carte pour indiquer l'emplacement exact"
    ,'msgGeorefNothingFound' => "Aucune ville portant ce nom n'a �t� trouv�e. Essayez le nom d'une ville proche."
	,'LANG_Location_Notes' => '<strong>Localiser la fleur</strong> : placez votre rep�re sur la carte ou utilisez les champs ci-dessous :'
	,'LANG_Or' => 'ou :'
	,'LANG_INSEE' => 'No INSEE.'
	,'LANG_NO_INSEE' => "Aucune zone ne correspond � ce num�ro INSEE (nouveau ou ancien)."
	,'LANG_Lat' => 'Lat./Long.'
	,'Latitude' => 'Latitude '
	,'Longitude' => 'Longitude '
	,'Flower Type' => "Cette plante est "
	,'Habitat' => "Type d'habitat "
	,'Nearest House' => "Distance approximative entre votre fleur et la ruche d'abeilles domestiques la plus proche (en m�tres; par exemple : 150) "
	,'Nearest Hive' => "Distance approximative entre votre fleur et la ruche d'abeilles domestiques la plus proche (en m�tres; par exemple : 150) "
	,'within50m' => "Pr�sence dans un rayon de 50m d'une grande culture en fleur "
	,'LANG_Validate_Flower' => 'VALIDER VOTRE STATION FLORALE'
	,'LANG_Must_Provide_Pictures' => "Les photos de la fleur et de son environnement doivent �tre charg�es"
	,'LANG_Must_Provide_Location' => 'Localisez votre station florale'
	
	,'LANG_Sessions_Title' => 'VOTRE SESSION'
	,'LANG_Sessions_Title_Plural' => 'VOS SESSIONS'
	,'LANG_Session' => 'Session'
	,'LANG_Date' => 'Date'
	,'LANG_Validate_Session' => 'Valider votre session'
	,'LANG_Validate_Session_Plural' => 'Valider vos sessions'
	,'LANG_Add_Session' => 'Ajouter une session'
	,'LANG_Delete_Session' => 'Supprimer la session'
	,'LANG_Cant_Delete_Session' => "La session ne peut pas �tre supprim� car il ya encore des insectes qui y sont associ�s."
	,'LANG_Confirm_Session_Delete' => '�tes-vous s�r de vouloir supprimer cette session ?'
	,'Start Time' => 'Heure de d�but (hh:mn) '
	,'Start time' => 'Heure de d�but (hh:mn) '
	,'End Time' => 'Heure de fin (hh:mn) '
	,'End time' => 'Heure de fin (hh:mn) '
	,'Sky' => 'Ciel (couverture nuageuse) '
	,'Temperature' => 'Temp�rature '
	,'Wind' => 'Vent '
	,'Shade' => "Fleur � l\\'ombre "
	
	,'LANG_Photos' => "VOS PHOTOS D'INSECTES"
	,'LANG_Photo_Blurb' => 'T�l�charger ou modifier vos observations.'
	,'LANG_Upload_Insect' => "Charger la photo de l'insecte"
	,'LANG_Identify_Insect' => 'Indiquer le nom de cet insecte :'
	,'LANG_Insect_Species' => "Vous connaissez le taxon correspondant � cet insecte"
	,'LANG_Insect_ID_Key_label' => "Vous ne connaissez pas le nom de cet insecte"
	,'LANG_ID_Insect_Later' => "Vous pr�f�rez l'identifier plus tard :"
	,'LANG_Comment' => 'Commentaire(s)'
	,'Number Insects' => "Nombre maximum d'individus de cette esp�ce vus simultan�ment "
	,'Foraging'=> "Avez-vous photographi� cet insecte ailleurs que sur la fleur de votre station florale"
	,'Foraging_Confirm'=> "Si oui, pouvez-vous nous confirmer que vous l'y avez vu butiner"
	,'Foraging_Validation'=> "Vous avez photographi� cet insecte ailleurs que sur la fleur de votre station florale. Si vous ne confirmez pas que vous l'y avez vu butiner, cela signifie que cette photo ne respecte pas le protocole du Spipoll et qu'elle ne doit pas �tre int�gr�e � cette collection."
	,'LANG_Validate_Insect' => "VALIDER L'INSECTE"
	,'LANG_Validate_Photos' => 'VALIDER VOS PHOTOS'
	,'LANG_Must_Provide_Insect_Picture' => "La photo de l'insecte doit �tre charg�e"
	,'LANG_Confirm_Insect_Delete' => '�tes-vous s�r de vouloir supprimer cet insecte ?'
	,'LANG_Delete_Insect' => "Supprimer l'insecte"
	
	,'LANG_Can_Complete_Msg' => "Vous avez identifi� votre fleur et un pourcentage suffisant d'insectes, vous pouvez maintenant cl�turer la collection"
	,'LANG_Cant_Complete_Msg' => "Vous n'avez pas identifi� la fleur, et/ou pas identifi� un pourcentage suffisant d'insectes (50%), conditions indispensables pour cl�turer votre collection."
	,'LANG_Complete_Collection' => 'Cl�turer la collection'
	,'LANG_Trailer_Head' => 'Apr�s cl�ture'
	,'LANG_Trailer_Point_1' => "Vous ne pourrez plus ajouter d'insectes � votre collection ; les avez-vous tous charg�s ?"
	,'LANG_Trailer_Point_2' => "Vous ne pourrez plus modifier les descriptions de la station floral, de la (ou des) session(s) et des insectes."
	,'LANG_Trailer_Point_3' => "Vous pourrez r�(identifier) vos insectes dans la rubrique �Mes collections�"
	,'LANG_Trailer_Point_4' => "Vous pourrez cr�er une nouvelle collection"
	
	,'validation_required' => "Ce champ est obligatoire"
	,'validation_time' => 'Entrez une heure valide (HH:MM)'
	,'validation_endtime_before_start' => "L'Heure de fin doit �tre post�rieure � l'heure de d�but"
	,'validation_time_less_than_20' => "Votre session dure moins de 20 mn. Veuillez v�rifier les heures de d�but et l'heure de fin de celle-ci"
	,'validation_please_check' => 'Veuillez v�rifier'
	,'validation_time_not_20' => "Votre session dure plus ou moins de 20 mn alors que le protocole Flash requiert une dur�e d'observation de 20 minutes pr�cis�ment. Veuillez v�rifier les heures de d�but et de fin de votre session"
	,'validation_session_date_error' => "Vos sessions s'�chelonnent sur une dur�e sup�rieure � 3 jours. Veuillez v�rifier les dates et heures de vos sessions."
	
	,'ajax_error' => "Une erreur s'est produite dans le transfert de donn�es."
	,'ajax_error_bumpf' => "Maintenant nous ne sommes pas s�r que les donn�es sur le serveur est la m�me que celle de notre formulaire. S'il vous pla�t, actualisez la page."
	,'validation_integer' => "Entrez un nombre entier, laissez en blanc si inconnu."
	,'LANG_Invalid_Location' => "Le format donn� pour ce Lat / Long combinaison n'est pas valide"
	,'LANG_Session_Error' => "Une erreur interne s'est produite. Il ne semble pas �tre une session jointe � la pr�sente collection."
	
	,'close'=>'Fermer'	
  	,'search'=>'Chercher'
	,'Yes' => 'Oui'
	,'No' => 'Non'
	,'LANG_Help_Button' => '?'
	,'LANG_Upload' => 'OK'
	,'click here'=>'Cliquez ici'
	
	,'LANG_Final_1' => 'Cette collection a �t� enregistr�e et ajout�e � votre galerie'
	,'LANG_Final_2' => "Cette collection peut �tre consult�e dans la rubrique �Mes collections�, o� vous pouvez modifier l'identification de vos insectes."
	,'LANG_Consult_Collection' => 'Consulter cette collection'
	,'LANG_Create_New_Collection' => 'Cr�er une nouvelle collection'
	
	,'LANG_Indicia_Warehouse_Error' => 'Erreur renvoy�e par Indicia Warehouse'
	,'loading' => 'Chargement'
	,'Internal Error 1: sample id not filled in, so not safe to save collection' => "Erreur interne 1: Identifiant collection n'est pas remplie, il n'est donc pas s�r de valider la collection"
	,'Internal Error 3: location id not set, so unsafe to delete collection.' => "Erreur interne 3: l'identifiant l'emplacement n'est pas d�fini, de sorte qu'il est dangereux de supprimer la collection."
	,'Internal Error 7: location id not set, so unsafe to delete session.' => "Erreur interne 7: l'id emplacement n'est pas fix�, de sorte qu'il est dangereux de supprimer la session."
	,'Internal Error 8: location id not set, so unsafe to save session.' => "Erreur interne 8: l'id emplacement n'est pas fix�, il n'est donc pas s�r de valider la session"
	,'Internal Error 9: sample id not set, so unsafe to save session.' => "Erreur interne 9: Identifiant collection n'est pas remplie, il n'est donc pas s�r de valider la session"
	,'Internal Error 10: no insect data available for id ' => "Erreur interne 10: pas de donn�es disponibles pour les insectes Identifiant "
	,'Internal Error 11: image could not be loaded into photoreel for insect ' => "Erreur interne 11: l'image n'a pas pu �tre charg�e dans photoreel pour id insectes "
	,'Internal Error 12: image could not be loaded into photoreel for existing insect ' => "Erreur interne 12: l'image n'a pas pu �tre charg�e dans photoreel pour id insectes existants "
	,'Internal Error 13: sample id not set, so unsafe to save insect.' => "Erreur interne 13: Identifiant collection n'est pas remplie, il n'est donc pas s�r de valider insectes"
	,'Internal Error 14: location id not set, so unsafe to save collection.' => "Erreur interne 14: l'id emplacement n'est pas fix�, il n'est donc pas s�r de valider la collection"
	,'Internal Error 15: sample id not set, so unsafe to save collection.' => "Erreur interne 15: Identifiant collection n'est pas remplie, il n'est donc pas s�r de valider la collection"
	,'Internal Error 16: could not load attributes ' => "Erreur interne 16: n'a pas pu charger les attributs "
	,'Internal Error 17: could not load ' => "Erreur interne 17: n'a pas pu charger "
	,'Internal Error 18: could not load data for location ' => "Erreur interne 18: n'a pas pu charger les donn�es de localisation "
	,'Internal Error 19: could not load data for location ' => "Erreur interne 19: n'a pas pu charger les donn�es de localisation "
	,'Internal Error 20: could not load attributes for sample ' => "Erreur interne 20: n'a pas pu charger les attributs de la collection "
	,'Internal Error 21: could not load flower data for collection ' => "Erreur interne 21: n'a pas pu charger les donn�es de fleurs pour la collection "
//	,'Internal Error 22: could not load environment attributes for location ' => "Erreur interne 22: n'a pas pu charger attributs d'environnement pour la localisation "
	,'Internal Error 23: could not load environment image for location ' => "Erreur interne 23: n'a pas pu charger l'image l'environnement pour la localisation "
	,'Internal Error 24: could not load image for flower ' => "Erreur interne 24: n'a pas pu charger l'image pour la fleur "
	,'Internal Error 25: could not load attributes for flower ' => "Erreur interne 25: n'a pas pu charger les attributs de fleur "
	,'Internal Error 26: could not load attributes for session ' => "Erreur interne 26: n'a pas pu charger les attributs de la session "
);
