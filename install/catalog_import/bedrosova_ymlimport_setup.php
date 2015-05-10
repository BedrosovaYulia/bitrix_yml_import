<title>IMPORT_YML</title>
<?
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bedrosova.ymlimport/import_setup_templ.php');

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bedrosova.ymlimport/classes/general/ymlimport.php");

global $APPLICATION, $USER;


$arSetupErrors = array();


$IBLOCK_ID;
$URL_DATA_FILE;
$DATA_FILE_NAME;
if ($IMPORT_CATEGORY=='')$IMPORT_CATEGORY='Y';
$ONLY_PRICE;
$max_execution_time;
if ($price_modifier=='')$price_modifier=1.0;

$IMPORT_CATEGORY_SECTION;
$URL_DATA_FILE2;
$ID_SECTION;
$CAT_FILTER_I;


$counter =1;

$arSetupErrors = array();
if ($STEP <= 1)
{
	if (isset($arOldSetupVars['IBLOCK_ID']))
		$IBLOCK_ID = $arOldSetupVars['IBLOCK_ID'];
	if (isset($arOldSetupVars['DATA_FILE_NAME']))
		$URL_DATA_FILE = $arOldSetupVars['DATA_FILE_NAME'];
	if (isset($arOldSetupVars['IMPORT_CATEGORY']))
		$IMPORT_CATEGORY = $arOldSetupVars['IMPORT_CATEGORY'];
	if (isset($arOldSetupVars['ONLY_PRICE']))
		$ONLY_PRICE = $arOldSetupVars['ONLY_PRICE'];
	if (isset($arOldSetupVars['max_execution_time']))
		$max_execution_time = $arOldSetupVars['max_execution_time'];
	if (isset($arOldSetupVars['SETUP_PROFILE_NAME']))
		$SETUP_PROFILE_NAME = $arOldSetupVars['SETUP_PROFILE_NAME'];
		
	if (isset($arOldSetupVars['IMPORT_CATEGORY_SECTION']))
		$IMPORT_CATEGORY_SECTION = $arOldSetupVars['IMPORT_CATEGORY_SECTION'];
	if (isset($arOldSetupVars['URL_DATA_FILE2']))
		$URL_DATA_FILE2 = $arOldSetupVars['URL_DATA_FILE2'];
	if (isset($arOldSetupVars['URL_DATA_FILE']))
		$URL_DATA_FILE = $arOldSetupVars['URL_DATA_FILE'];
		
	if (isset($arOldSetupVars['ID_SECTION']))
		$ID_SECTION = $arOldSetupVars['ID_SECTION'];
		
	
if (isset($arOldSetupVars['CAT_FILTER_I']))
		$CAT_FILTER_I= $arOldSetupVars['CAT_FILTER_I'];	
		
		if (isset($arOldSetupVars['price_modifier']))
		$price_modifier = $arOldSetupVars['price_modifier'];	
		
		
		
}



// проверка перехода ко 2 вкладке
if ($STEP >1)
{
	// должен быть файл
	if (strlen($URL_DATA_FILE) > 0 && file_exists($_SERVER["DOCUMENT_ROOT"].$URL_DATA_FILE) && is_file($_SERVER["DOCUMENT_ROOT"].$URL_DATA_FILE) && $APPLICATION->GetFileAccessPermission($URL_DATA_FILE)>="R")
		$DATA_FILE_NAME = $URL_DATA_FILE;

	if (strlen($DATA_FILE_NAME) <= 0 && !(strlen($URL_DATA_FILE2) > 0))
		$arSetupErrors[] = GetMessage("CATI_NO_DATA_FILE");

	
	$IBLOCK_ID = IntVal($IBLOCK_ID);
	$arIBlock = array();
	
	// не выбран инфоблок
	if ($IBLOCK_ID <= 0)
	{
		$arSetupErrors[] = GetMessage("CATI_NO_IBLOCK");
	}
	else
	{
		$arIBlock = CIBlock::GetArrayByID($IBLOCK_ID);
		if (false === $arIBlock)
		{
			$arSetupErrors[] = GetMessage("CATI_NO_IBLOCK");
		}
	}
	
	if (!CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, 'iblock_admin_display'))
		$arSetupErrors[] = GetMessage("CATI_NO_IBLOCK_RIGHTS");

	$bIBlockIsCatalog = False;
	if (CCatalog::GetByID($IBLOCK_ID))
		$bIBlockIsCatalog = True;
	
	// не должно быть ошибок
	if (!empty($arSetupErrors))
	{
		// иначе остаемся на месте
		$STEP = 1;
	}
}



if (!empty($arSetupErrors))
	echo ShowError(implode('<br />', $arSetupErrors));
?>



<? // начинается форма ?>
<form method="POST" action="<? echo $APPLICATION->GetCurPage(); ?>" ENCTYPE="multipart/form-data" name="dataload">
<?
$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("CAT_ADM_CSV_IMP_TAB1"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_CSV_IMP_TAB1_TITLE")),
	array("DIV" => "edit2", "TAB" => GetMessage("CAT_ADM_CML1_IMP_TAB2"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_CML1_IMP_TAB2_TITLE"))
// 	
);

$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
$tabControl->Begin();

$tabControl->BeginNextTab();

if ($STEP == 1)
{
	?>
	<tr class="heading">
		<td colspan="2" align="center">
			<? echo GetMessage("CAT_FILE_INFO"); ?>
		</td>
	</tr>
	<tr>
		<td valign="top" width="40%"><? echo GetMessage("CATI_DATA_FILE_SITE"); ?>:</td>
		<td valign="top" width="60%">
			<input type="text" name="URL_DATA_FILE" size="40" value="<? echo htmlspecialcharsbx($URL_DATA_FILE); ?>">
			<input type="button" value="<? echo GetMessage("CATI_BUTTON_CHOOSE")?>" onclick="cmlBtnSelectClick();"><?
CAdminFileDialog::ShowScript(
	array(
		"event" => "cmlBtnSelectClick",
		"arResultDest" => array("FORM_NAME" => "dataload", "FORM_ELEMENT_NAME" => "URL_DATA_FILE"),
		"arPath" => array("PATH" => "/upload/catalog", "SITE" => SITE_ID),
		"select" => 'F',// F - file only, D - folder only, DF - files & dirs
		"operation" => 'O',// O - open, S - save
		"showUploadTab" => true,
		"showAddToMenuTab" => false,
		"fileFilter" => 'xml',
		"allowAllFiles" => true,
		"SaveConfig" => true
		)
				);
		?></td>
	</tr>
	
	<tr>
		<td valign="top" width="40%"><? echo GetMessage("CATI_DATA_FILE_SITE2"); ?>:</td>
		<td valign="top" width="60%">
			<input type="text" name="URL_DATA_FILE2" size="40" value="<? echo htmlspecialcharsbx($URL_DATA_FILE2); ?>">
		</td>
	</tr>
	
	<tr class="heading">
		<td colspan="2" align="center">
			<? echo GetMessage("CAT_IMPORT_IBSET"); ?>
		</td>
	</tr>
	
	<tr>
		<td valign="top" width="40%"><? echo GetMessage("CATI_INFOBLOCK"); ?>:</td>
		<td valign="top" width="60%"><?
			if (!isset($IBLOCK_ID))
				$IBLOCK_ID = 0;
			echo GetIBlockDropDownListEx($IBLOCK_ID, 'IBLOCK_TYPE_ID', 'IBLOCK_ID',array('CHECK_PERMISSIONS' => 'Y','MIN_PERMISSION' => 'W'));
		?></td>
	</tr>
	

	<tr class="heading">
	<td colspan="2" align="center">
		<? echo GetMessage("CAT_IMPORT_SET"); ?>
	</td>
	</tr>
	<tr>
		<td valign="top" width="40%"><label for="IMPORT_CATEGORY"><? echo GetMessage("CAT_IMPORT"); ?></label>:</td>
		<td valign="top" width="60%">
			<input type="hidden" name="IMPORT_CATEGORY" id="IMPORT_CATEGORY_N" value="N">
			<input type="checkbox" name="IMPORT_CATEGORY" id="IMPORT_CATEGORY_Y" value="Y" <? echo (isset($IMPORT_CATEGORY) && 'Y' == $IMPORT_CATEGORY ? "checked": ""); ?>>
		</td>
	</tr>
	
	<tr>
		<td valign="top" width="40%"><label for="IMPORT_CATEGORY"><? echo GetMessage("CAT_IMPORT_SECTION"); ?></label>:</td>
		<td valign="top" width="60%">
			<input type="hidden" name="IMPORT_CATEGORY_SECTION" id="IMPORT_CATEGORY_SECTION_N" value="N">
			<input type="checkbox" name="IMPORT_CATEGORY_SECTION" id="IMPORT_CATEGORY_SECTION_Y" value="Y" <? echo (isset($IMPORT_CATEGORY_SECTION) && 'Y' == $IMPORT_CATEGORY_SECTION ? "checked": ""); ?>>
		</td>
	</tr>
	
	<tr>
		<td valign="top" width="40%"><label for="IMPORT_CATEGORY"><? echo GetMessage("CAT_ID_SECTION"); ?></label>:</td>
		<td valign="top" width="60%">
				<input type="text" name="ID_SECTION" id="ID_SECTION_FOR_I" value="<? echo intval($ID_SECTION); ?>" size="5" >
		</td>
	</tr>
	
	<tr class="heading">
		<td colspan="2" align="center">
			<? echo GetMessage("CAT_FILTER"); ?>
		</td>
	</tr>
	
	<tr>
		<td valign="top" width="40%"><label for="IMPORT_CATEGORY"><? echo GetMessage("CAT_FILTER"); ?></label>:</td>
		<td valign="top" width="60%">
				<input type="text" name="CAT_FILTER_I" id="ID_SECTION_FOR_I" value="<? echo $CAT_FILTER_I; ?>" size="50" >
		</td>
	</tr>
	
	
	<tr class="heading">
		<td colspan="2" align="center">
			<? echo GetMessage("CAT_OTHER_OPTIONS"); ?>
		</td>
	</tr>
	

	<tr>
		<td valign="top" width="40%"><label for="ONLY_PRICE"><? echo GetMessage("CAT_ONLY_PRICE"); ?> </label>:</td>
		<td valign="top" width="60%">
			<input type="hidden" name="ONLY_PRICE" id="ONLY_PRICE_N" value="N">
			<input type="checkbox" name="ONLY_PRICE" id="ONLY_PRICE_Y" value="Y" <? echo (isset($ONLY_PRICE) && 'Y' == $ONLY_PRICE ? "checked": ""); ?> >
		</td>
	</tr>

	<tr>
		<td valign="top" width="40%"><? echo GetMessage("CATI_AUTO_STEP_TIME");?>:</td>
		<td valign="top" width="60%">
			<input type="text" name="max_execution_time" size="40" value="<? echo intval($max_execution_time); ?>" ><br>
			<small><?echo GetMessage("CATI_AUTO_STEP_TIME_NOTE");?></small>
		</td>
	</tr>
	
		<tr class="heading">
		<td colspan="2" align="center">
			<? echo GetMessage("CAT_PRICE_OPTIONS"); ?>
		</td>
	</tr>
		<tr>
		<td valign="top" width="40%"><? echo GetMessage("CAT_PRICE_MODIFIER");?>:</td>
		<td valign="top" width="60%">
			<input type="text" name="price_modifier" size="40" value="<? echo doubleval($price_modifier); ?>" ><br>
			<small><?echo GetMessage("CAT_PRICE_MODIFIER_INFO");?></small>
		</td>
	</tr>

    <? if ($ACTION != "IMPORT")
    {
        ?>
	<tr>
		<td valign="top" width="40%" ><?echo GetMessage("CAT_PROFILE_NAME");?>:</td>
		<td valign="top" width="60%" >
		 <input type="text" name="SETUP_PROFILE_NAME" size="40" value="<? echo htmlspecialcharsbx($SETUP_PROFILE_NAME); ?>">

				<br><br>
		</td>

    </tr>
      <? } ?>

	<?
	

}




$tabControl->EndTab();

$tabControl->BeginNextTab();

if ($STEP == 2)
{
	$FINITE = true;
}

$tabControl->EndTab();

$tabControl->Buttons();

echo bitrix_sessid_post();


if ($ACTION == 'IMPORT_EDIT' || $ACTION == 'IMPORT_COPY')
{
    ?><input type="hidden" name="PROFILE_ID" value="<? echo intval($PROFILE_ID); ?>"><?
}



if ($STEP < 2)
{

	?>    <input type="hidden" name="STEP" value="<?echo intval($STEP) + 1;?>">
    <input type="hidden" name="lang" value="<?echo LANGUAGE_ID; ?>">
    <input type="hidden" name="ACT_FILE" value="<?echo htmlspecialcharsbx($_REQUEST["ACT_FILE"]) ?>">
    <input type="hidden" name="ACTION" value="<?echo htmlspecialcharsbx($ACTION) ?>">

    <input type="hidden" name="SETUP_FIELDS_LIST" value="DATA_FILE_NAME, IBLOCK_ID, IMPORT_CATEGORY, ONLY_PRICE, max_execution_time, IMPORT_CATEGORY_SECTION, URL_DATA_FILE2, ID_SECTION, CAT_FILTER_I, price_modifier">

    <input type="submit" value="<? echo ($ACTION=="IMPORT")?GetMessage("CICML_NEXT_STEP_F")." &gt;&gt;":GetMessage("CET_SAVE"); ?>" name="submit_btn"><?
}

$tabControl->End();

?></form>
<script type="text/javascript">
<?if ($STEP == 1):?>
tabControl.SelectTab("edit1");
tabControl.DisableTab("edit2");
<?endif;?>
</script>