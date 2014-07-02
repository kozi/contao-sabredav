<?php

$paletteDeafault = $GLOBALS['TL_DCA']['tl_calendar']['palettes']['default'];
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['default']        = $paletteDeafault.';{fullcal_legend:hide},fullcal_type';
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][] = 'fullcal_type';

$GLOBALS['TL_DCA']['tl_calendar']['palettes']['webdav']         = $paletteDeafault.';{fullcal_legend:hide},fullcal_type,fullcal_baseUri,fullcal_path,fullcal_username,fullcal_password';
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['public_ics']     = $paletteDeafault.';{fullcal_legend:hide},fullcal_type,fullcal_ics';


array_insert($GLOBALS['TL_DCA']['tl_calendar']['list']['operations'], 0, array
(
    'fullcal' => array
    (
        'label' => &$GLOBALS['TL_LANG']['tl_calendar']['fullcal'],
        'href'  => 'key=fullcal',
        'icon'  => 'reload.gif',
        'button_callback' => array('tl_calendar_fullcal', 'btnCallback')
    )
));

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_type'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['fullcal_type'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'                 => array('' => 'No Sync', 'webdav' => 'webdav', 'public_ics' => 'public_ics'),
    'sql'                     => "varchar(16) NOT NULL default ''",
    'eval'                    => array('submitOnChange'=>true),
);


$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_baseUri'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['fullcal_baseUri'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'sql'                     => "varchar(255) NOT NULL default ''",
    'eval'                    => array('mandatory' => true, 'tl_class' => 'long'),
);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_ics'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['fullcal_ics'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'sql'                     => "varchar(255) NOT NULL default ''",
    'eval'                    => array('mandatory' => true, 'tl_class' => 'long'),
);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_path'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['fullcal_path'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'sql'                     => "varchar(255) NOT NULL default ''",
    'eval'                    => array('mandatory' => true, 'tl_class' => 'long'),
);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_username'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['fullcal_username'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'sql'                     => "varchar(255) NOT NULL default ''",
    'eval'                    => array('mandatory' => true, 'tl_class' => 'w50'),

);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['fullcal_password'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['fullcal_password'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'sql'                     => "blob NULL",
    'eval'                    => array('mandatory' => true, 'tl_class' => 'w50', 'encrypt' => true),
);

class tl_calendar_fullcal extends Backend {

    public function btnCallback($row, $href, $label, $title, $icon, $attributes) {
        return ($row['fullcal_type'] !== '') ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : '';
    }
}