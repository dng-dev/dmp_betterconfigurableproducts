<?php

// path to config.xml must be set !!!
$configFile = __DIR__ . "/../../app/code/community/DerModPro/BCP/etc/config.xml";

// extension name must be set!!!
$extensionName = 'BCP';

$extensionVersion = getVersion($configFile);
$tarArchive = getArchive($extensionName, $extensionVersion);
$workspace = getWorkspaceDirectory();

function getWorkspaceDirectory()
{
    $dir = realpath(__DIR__ .'/../../');
    return $dir;
}
    
function getArchive($name, $version)
{
    $archive = __DIR__ . '/../../'. $name . '_' . $version .'.tar' ;
    if (file_exists($archive)) {
        return $name . '_' . $version .'.tar' ;
    }
    
}

function getVersion($file)
{
    $xml = simplexml_load_file($file);
    return current(current($xml->xpath('//version')));
}




return array(
    'base_dir'          => $workspace,
    'archive_files'     => $tarArchive,
    'extension_name'    => $extensionName,
    'extension_version' => $extensionVersion,
    'path_output'       => $workspace,
    'stability'         => 'stable',
    'license'           => 'OSL3',
    'channel'           => 'community',
    'summary'           => 'some text',
    'description'       => 'some description',
    'notes'             => 'test release',
    'author_name'       => 'Sebastian Ertner',
    'author_user'       => 'sebastianertner',
    'author_email'      => 'sebastian.ertner@netresearch.de',
    'php_min'           => '5.2.0',
    'php_max'           => '6.0.0',
    'skip_version_compare' => false,
);

