<?php
declare(strict_types=1);

#Hhcom\ContaoMailchimpBackendUpdate\Classes\MCHelper::uebertrageAlleZuMC();

$GLOBALS['TL_DCA']['tl_member']['fields']['mailchimp_newsletter_subscribed'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50 m12', 'submitOnChange'=>true),
    'sql'                     => "char(1) NOT NULL default '0'",
    'save_callback' => array
    (
        array('Hhcom\ContaoMailchimpBackendUpdate\Classes\MCHelper', 'subscribeUnsubscribeUser')
    ),
];

$GLOBALS['TL_DCA']['tl_member']['fields']['mailchimp_newsletter_interests'] = [
	'exclude'                 => true,
    'filter'                  => true,
    'inputType'               => 'checkboxWizard',
    'options_callback'        => array('Hhcom\ContaoMailchimpBackendUpdate\Classes\MCHelper', 'getExternalInterestsForFirstList'),
    'eval'                    => array('multiple'=>true, 'tl_class'=>'clr w50'),
	'sql'                     => "blob NULL",
    'load_callback' => array
    (
        array('Hhcom\ContaoMailchimpBackendUpdate\Classes\MCHelper', 'getCurrentlySetInMailchimp')
    ),
];

$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = str_replace(
    '{groups_legend}',
    
    '{mailchimp_legend},statusInformation,mailchimp_newsletter_interests,mailchimp_newsletter_subscribed;{groups_legend}', 
    $GLOBALS['TL_DCA']['tl_member']['palettes']['default']
);


$GLOBALS['TL_DCA']['tl_member']['fields']['statusInformation'] = [
    'input_field_callback'    => array('Hhcom\ContaoMailchimpBackendUpdate\Classes\MCHelper', 'getStatusInformationToUser'),
]; 

class tl_member_mc_extend extends Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import(BackendUser::class, 'User');
	}
  

}