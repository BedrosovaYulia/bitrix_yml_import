<title>IMPORT_YML_5_5</title>
<?
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bedrosova.ymlimport/import_setup_templ.php');

require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bedrosova.ymlimport/classes/general/5.5/ymlimport55.php");

$ymlimport = new CYmlImport();

$strImportErrorMessage = $ymlimport->ImportYML($DATA_FILE_NAME, $IBLOCK_ID, $IMPORT_CATEGORY, $ONLY_PRICE, $max_execution_time, $CUR_FILE_POS, $IMPORT_CATEGORY_SECTION, $URL_DATA_FILE2, $ID_SECTION, $CAT_FILTER_I, $price_modifier);


if (!$ymlimport->AllLinesLoaded)
{
    $SETUP_VARS_LIST = "DATA_FILE_NAME, IBLOCK_ID, IMPORT_CATEGORY, ONLY_PRICE, max_execution_time, CUR_FILE_POS, IMPORT_CATEGORY_SECTION, URL_DATA_FILE2, ID_SECTION, CAT_FILTER_I, price_modifier";
    
    $CUR_FILE_POS = $ymlimport->FILE_POS;
    $bAllDataLoaded = false; 
    
}

?>