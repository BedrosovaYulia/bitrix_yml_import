<?

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bedrosova.ymlimport/options.php');

?>
<form method="POST" action="<? echo $APPLICATION->GetCurPage(); ?>" ENCTYPE="multipart/form-data" name="dataload">
<?
$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("B_YML_TAB_NAME"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_CSV_IMP_TAB3_TITLE"))
);

$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
$tabControl->Begin();

$tabControl->BeginNextTab();

{
	
	?>
	<tr>
	<td valign="top" width="40%"><? echo GetMessage("B_YML_TAB_TITLE"); ?>
	</td>
	</tr>
	<?
}




$tabControl->EndTab();


$tabControl->Buttons();

echo bitrix_sessid_post();

{
	?><input type="submit" value="OK" name="submit_btn"><?
}



$tabControl->End();



?></form>
<script type="text/javascript">
tabControl.SelectTab("edit1");
</script>