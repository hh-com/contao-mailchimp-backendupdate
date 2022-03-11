<?php

namespace Hhcom\ContaoMailchimpBackendUpdate\Classes;

use Contao\ContentModel;
use Contao\StringUtil;
use Contao\Database;
use Contao\Message;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Oneup\Contao\MailChimpBundle\Model\MailChimpModel;
use Oneup\MailChimp\Client;


class MCHelper extends \Controller
{

    /**
     * DCA
     */
    public static function subscribeUnsubscribeUser($value, $dc) {

        
        $allLists = MailChimpModel::findAll();
        $mailChimpListId = $allLists[0]->listId;
        $mailChimp = new Client($allLists[0]->listApiKey);

        $firstname = $dc->activeRecord->firstname;
        $lastname = $dc->activeRecord->lastname;
        $email = $dc->activeRecord->email;

        $status = $mailChimp->getSubscriberStatus($mailChimpListId, $email);


        $requestData = [
            'id' => $mailChimpListId,
            'email_address' => $email,
            'status' => 'subscribed',
        ];

        /**
         * PRÜFEN7NACHSEHEN welche Felder es in Mailchimp gibt:
         */
        // $allLists = MailChimpModel::findAll();
        // echo '<pre>';
        // var_dump($allLists);
        // exit;

        $mergeVars['FNAME'] = $firstname;
        $mergeVars['LNAME'] = $lastname;
        
        if (\count($mergeVars) > 0) {
            $requestData['merge_fields'] = $mergeVars;
        }
        
        if (is_array($dc->activeRecord->mailchimp_newsletter_interests)) {
            $checktInterests = $dc->activeRecord->mailchimp_newsletter_interests;
        } elseif(is_string($dc->activeRecord->mailchimp_newsletter_interests)) {
            $checktInterests = unserialize($dc->activeRecord->mailchimp_newsletter_interests);
        } else {
            $checktInterests = [];
        }
        
        $allInterests = MCHelper::getExternalInterestsForFirstList();

        if ( $allInterests ) {

            $preparedInterests = [];
            foreach ($allInterests as $interestKey => $readableName) {
                if (in_array($interestKey, $checktInterests)) {
                    $preparedInterests[$interestKey] = true;
                } else {
                    $preparedInterests[$interestKey] = false;
                }
            }
            $requestData['interests'] = $preparedInterests;
        }

        $endpoint = sprintf('lists/%s/members', $mailChimpListId);

        $response = $mailChimp->put($endpoint.'/'. md5(strtolower($email)), $requestData);

        if (null === $response) {
            throw new ErrorException('Could not connect to API. Check your credentials.');
        }

        return "";

    }
    

    public static function getCurrentlySetInMailchimp( $value, $dc) {
      
        $allLists = MailChimpModel::findAll();
        $mailChimpListId = $allLists[0]->listId;
        $mailChimp = new Client($allLists[0]->listApiKey);


        $email = $dc->activeRecord->email;
        $endpoint = sprintf('lists/%s/members/%s', $mailChimpListId,  md5(strtolower($email)));
        $response = $mailChimp->get($endpoint);
        $body = json_decode($response->getBody());


        $return = [];
        if ( $body->interests) {
            foreach ($body->interests as $ik => $interest) {
                if ($interest == true) {
                    $return[] = $ik;
                }
            }
        }
        
        return $return;
    }
    /**
     * DCA
     * Returns all external Interests prepared for DCA
     */
    public static function getExternalInterestsForFirstList() {

        $return = [];

        $allLists = MailChimpModel::findAll();

        if ($allLists){
            foreach ($allLists as $list) {
                $allInterests = StringUtil::deserialize($list->groups, true);
                if ($allInterests) {
                    foreach ($allInterests as $group) {
                        if ($group) {
                            $groupTmp = json_decode ($group);
                            if ($groupTmp) {
                                foreach ($groupTmp as $tmp) {
                                    foreach ($tmp->interests as $intTmp) {
                                        
                                        $return[$intTmp->id] = $intTmp->name;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $return;
    
    }

    /*
    * Infofeld in der DCA zur Verwendung der Icons
    */
    public function getStatusInformationToUser(\DataContainer $dc, $label)
    {
        $allLists = MailChimpModel::findAll();
        $mailChimpListId = $allLists[0]->listId;
        $mailChimp = new Client($allLists[0]->listApiKey);
        $email = $dc->activeRecord->email;

        $status = $mailChimp->getSubscriberStatus($mailChimpListId, $email);
        
        if ($status == "404") {

            
            $statusInfoText = "<b style='color: red;' >Dieser User ist nicht in Mailchimp eingetragen.</b>
            <br>Er hat den Newsletter nicht abboniert. 
            <br>Beachten Sie die Datenschutzbestimmungen, wenn Sie diesen User hier aktivieren (holen Sie sich die Erlaubnis).
            
            ";

        } elseif ($status == "unsubscribed") {

            
            $statusInfoText = "<b style='color: red;' >Dieser User hat sich vom Newsletter abgemeldet!</b><br> Beachten Sie die Datenschutzbestimmungen, wenn Sie diesen User hier wieder aktivieren (holen Sie sich die Erlaubnis).";

        } elseif ($status == "subscribed") {

            $statusInfoText = "<b style='color: black;' >Dieser User ist in Mailchimp eingetragen. Er hat den Newsletter abboniert.</b><br>Sie können hier die Kategorie-Zuweisungen anpassen.";

        } elseif ($status == "archived") {

            $statusInfoText = "<b style='color: orange;' >Dieser User ist in Mailchimp archiviert. Er hat den Newsletter nicht abboniert.</b><br> Beachten Sie die Datenschutzbestimmungen, wenn Sie diesen User hier wieder aktivieren (holen Sie sich die Erlaubnis).";

        } else {
            $statusInfoText = "<b style='color: red;' >Der Status zu diese Kontakt konnte nicht aus Mailchimp geladen werden.</b> Evlt. existiert er dort nicht oder es gibt andere Probleme.";
        }


        $icon = $this->generateImage('show.gif', $GLOBALS['TL_LANG']['tl_calendar_events']['helpmode'][0], ' style="vertical-align:-4px"');
        return '<div style="margin-left: 15px;margin-right: 15px;line-height:1.3rem;">
            <h3 '.$styleColor.'><label style="color:#8ab858">'.$icon. ' Information zum Kontakt-Status in Mailchimp</label></h3>
            <div style="margin:5px 0">
            <br>
            '.$statusInfoText.'

            <br>
            <i>Gruppen werden u.U. weiterhin angezeigt.</i>
            </div>
        </div>';
    } 


    /**
     * Übertragen aller Kontakte zu Mailchimp
     * Diese Funktion is in tl_member auskommentiert und wird nur einmal ausgeführt...
     */
    public static function uebertrageAlleZuMC() {

        $allLists = MailChimpModel::findAll();
        $mailChimpListId = $allLists[0]->listId;

        $allInterests = MCHelper::getExternalInterestsForFirstList();

        $membersObj = Database::getInstance()->prepare("SELECT * FROM tl_member where email not like '%@kein-mail.at' " )
        ->limit(1)
        ->execute()
        ;

        echo '<pre>';
        var_dump($membersObj->numRows);
        exit;

        if ($membersObj->numRows > 0) {
            foreach ($membersObj->fetchAllAssoc() as $member) {
                
                $mailChimp = new Client($allLists[0]->listApiKey);

                $requestData = [
                    'id' => $mailChimpListId,
                    'email_address' => $member['email'],
                    'status' => 'subscribed',
                ];

                $preparedInterests = [
                    '111222333' => false, # Contao- Mitglieder-Gruppe 1
                    '222333444' => true, # Contao- Mitglieder-Gruppe 1
                    '333444555' => false, # Contao- Mitglieder-Gruppe 1
                ];

                foreach (unserialize($member['groups']) as $groupId) {

                    if ($groupId == "1") {
                        $preparedInterests['111222333'] = true;
                    }
                    if ($groupId == "2") {
                        $preparedInterests['222333444'] = true;
                    }
                    if ($groupId == "3") {
                        $preparedInterests['333444555'] = true;
                    }
                }

                $requestData['interests'] = $preparedInterests;

                $mergeVars['FNAME'] = $member['firstname']?:'';
                $mergeVars['LNAME'] = $member['lastname']?:'';
                
                $requestData['merge_fields'] = $mergeVars;
                
                $endpoint = sprintf('lists/%s/members', $mailChimpListId);
                $response = $mailChimp->put($endpoint.'/'. md5(strtolower($member['email'])), $requestData);

            }
        }

    }
    
}
