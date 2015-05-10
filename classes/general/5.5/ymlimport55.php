<?

/* Это самый главный класс. к нему обращаются все остальные. 
 * Берем файл YML и кидаем его в каталог
 */

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bedrosova.ymlimport/import_setup_templ.php');

global $USER;
global $APPLICATION;

class CYmlImport
{



    var $bTmpUserCreated = false;
    var $strImportErrorMessage = "";
    var $strImportOKMessage = "";
    var $max_execution_time = 0;
	var $price_modifier = 1.0;
    var $AllLinesLoaded = true;
    var $FILE_POS=0;

    var $fp;

    function file_get_contents($filename)
    {
        $fd = fopen("$filename", "rb");
        $content = fread($fd, filesize($filename));
        fclose($fd);
        return $content;
    }


    function CSVCheckTimeout($max_execution_time)
    {
        return ($max_execution_time <= 0) || (getmicrotime()-START_EXEC_TIME <= $max_execution_time);
    }


    // получаем xml-объект
    // смысл в том, что нельзя определить кодировку файла. всегда определяется utf-8
    // поэтому открываем файл как есть, если он объект создается - хорошо
    // если не создается - перекодируем и опять создаем.
    // ну уж если и тут не создался, я уже не знаю что делать
    function GetXMLObject($FilePath)
    {
        // содержимое
        $file_content = file_get_contents ($FilePath);

        // пытаемся получить объект
        $xml =  simplexml_load_string($file_content);
        if (!is_object($xml->shop))
        {
            //не могу создать объект
            $file_content = iconv("UTF-8", "Windows-1251", $file_content);

            // кодировка произведена
            // еще разик
            $xml =  simplexml_load_string($file_content);
        }

        return $xml;
    }


    // а это жирная функция как раз все делает
    function ImportYML ($DATA_FILE_NAME, $IBLOCK_ID, $IMPORT_CATEGORY, $ONLY_PRICE, $max_execution_time, $CUR_FILE_POS,$IMPORT_CATEGORY_SECTION, $URL_DATA_FILE2, $ID_SECTION, $CAT_FILTER_I, $price_modifier)
    {

        if (!isset($USER) || !(($USER instanceof CUser) && ('CUser' == get_class($USER))))
        {
            $bTmpUserCreated = true;
            if (isset($USER))
            {
                $USER_TMP = $USER;
                unset($USER);
            }

            $USER = new CUser();
        }


        if ($max_execution_time <= 0)
            $max_execution_time = 0;
        if (defined('BX_CAT_CRON') && true == BX_CAT_CRON)
            $max_execution_time = 0;

        if (defined("CATALOG_LOAD_NO_STEP") && CATALOG_LOAD_NO_STEP)
            $max_execution_time = 0;

        $bAllLinesLoaded = true;


        if (strlen($URL_DATA_FILE) > 0)
        {
            $URL_DATA_FILE = Rel2Abs("/", $URL_DATA_FILE);
            if (file_exists($_SERVER["DOCUMENT_ROOT"].$URL_DATA_FILE) && is_file($_SERVER["DOCUMENT_ROOT"].$URL_DATA_FILE))
                $DATA_FILE_NAME = $URL_DATA_FILE;
        }
		
		if (!(strlen($DATA_FILE_NAME) > 0))
        {
		     $DATA_FILE_NAME = $URL_DATA_FILE2;
		
		}

        //if (strlen($DATA_FILE_NAME) <= 0)
         //   $strImportErrorMessage .= GetMessage("CATI_NO_DATA_FILE")."<br>";

        $IBLOCK_ID = intval($IBLOCK_ID);
        if ($IBLOCK_ID <= 0)
        {
            $strImportErrorMessage .= GetMessage("CATI_NO_IBLOCK")."<br>";
        }
        else
        {
            $arIBlock = CIBlock::GetArrayByID($IBLOCK_ID);
            if (false === $arIBlock)
            {
                $strImportErrorMessage .= GetMessage("CATI_NO_IBLOCK")."<br>";
            }
        }


        if (strlen($strImportErrorMessage) <= 0)
        {
            $bIBlockIsCatalog = false;
            if (CCatalog::GetByID($IBLOCK_ID))
                $bIBlockIsCatalog = true;

            //Здесь начинаем загрузку xml файла

            $xml;


            if (file_exists($_SERVER["DOCUMENT_ROOT"].$DATA_FILE_NAME))
            {
                $xml = $this->GetXMLObject($_SERVER["DOCUMENT_ROOT"].$DATA_FILE_NAME);
            }
			else{
			
				$uf=file_get_contents($URL_DATA_FILE2);
				//file_put_contents($_SERVER["DOCUMENT_ROOT"]."/upload/file_for_import.xml",$uf);
				$handle = fopen($_SERVER["DOCUMENT_ROOT"]."/upload/file_for_import.xml", 'w+');
				fwrite($handle, $uf);
				fclose($handle);
				$DATA_FILE_NAME="/upload/file_for_import.xml";
				$xml = $this->GetXMLObject($_SERVER["DOCUMENT_ROOT"].$DATA_FILE_NAME);
			
			}

            

            if (!is_object($xml->shop))
            {
                $strImportErrorMessage .= GetMessage("CICML_INVALID_FILE")."<br>";
            }

        }



        if (strlen($strImportErrorMessage) <= 0)
        {
		
			set_time_limit(0);
		
            $arPriceType = Array();

            //Импортирую категории из yml файла

            $ResCatArr=array();//Сюда буду складывать найденные или добавленные айдишники для каждой категории

            $CategiriesList=$xml->shop->categories->category;

            foreach($CategiriesList as $Categoria){

                $CATEGORIA_XML_ID = 'yml_'.$Categoria['id'];
				
				
				
					if ($IMPORT_CATEGORY_SECTION=='Y'){
				
						$CATEGORIA_PARENT_XML_ID = $Categoria['parentId'] ? 'yml_'.$Categoria['parentId'] : $ID_SECTION;
				
					}else{
						$CATEGORIA_PARENT_XML_ID = $Categoria['parentId'] ? 'yml_'.$Categoria['parentId'] : 0;//Если родитель не указан - пусть катгория идет в корень
					}
				
				
				
                $CATEGORIA_NAME=iconv('utf-8',SITE_CHARSET,$Categoria);


                //Ищем, существует ли такая категория на сайте

                $find_section_res=CIBlockSection::GetList(array(), Array("IBLOCK_ID"=>$IBLOCK_ID, "XML_ID"=>$CATEGORIA_XML_ID),false, array("ID"),false);
                if($find_section_res2=$find_section_res->GetNext()){
                    $ResCatArr[''.$CATEGORIA_XML_ID.'']=$find_section_res2["ID"];
					
					
					if ($ResCatArr[''.$CATEGORIA_XML_ID.'']==0 && $IMPORT_CATEGORY_SECTION=='Y'){
				
						$ResCatArr[''.$CATEGORIA_XML_ID.'']=$ID_SECTION;
				
					}


                    $bs = new CIBlockSection;
                    $arFields = Array(
                        "ACTIVE" => "Y",
                        "IBLOCK_ID" => $IBLOCK_ID,
                        "NAME" => $CATEGORIA_NAME,
                        "IBLOCK_SECTION_ID"=>$ResCatArr[''.$CATEGORIA_PARENT_XML_ID.''],
                        "XML_ID"=>$CATEGORIA_XML_ID,
                        //  "CODE"=>$section_code,
                    );



                    if ($IMPORT_CATEGORY=='Y'){
                        $bs->Update($find_section_res2["ID"],$arFields);
                    }

                }
                else
                {
                    //Добавляю
					
					if ($ResCatArr[''.$CATEGORIA_PARENT_XML_ID.'']==0 && $IMPORT_CATEGORY_SECTION=='Y'){
				
						$ResCatArr[''.$CATEGORIA_PARENT_XML_ID.'']=$ID_SECTION;
				
					}

                    $section_code=CUtil::translit($CATEGORIA_NAME, LANGUAGE_ID, array(
                        "max_len" => 50,
                        "change_case" => 'U', // 'L' - toLower, 'U' - toUpper, false - do not change
                        "replace_space" => '_',
                        "replace_other" => '_',
                        "delete_repeat_replace" => true,
                    ));
                    if(preg_match('/^[0-9]/', $section_code))
                        $section_code = '_'.$section_code;


                    $bs = new CIBlockSection;
                    $arFields = Array(
                        "ACTIVE" => "Y",
                        "IBLOCK_ID" => $IBLOCK_ID,
                        "NAME" => $CATEGORIA_NAME,
                        "IBLOCK_SECTION_ID"=>$ResCatArr[''.$CATEGORIA_PARENT_XML_ID.''],
                        "XML_ID"=>$CATEGORIA_XML_ID,
                        "CODE"=>'yml_'.$section_code,
                    );



                    if ($IMPORT_CATEGORY=='Y'){
                        $ResCatArr[''.$CATEGORIA_XML_ID.'']= $bs->Add($arFields);

                        if(!$ResCatArr[''.$CATEGORIA_XML_ID.''])
                            echo $bs->LAST_ERROR;
                    }
                }
            }
			
			
			

            $offerlists = $xml->shop->offers->offer;


            $SITE_ID = 'ru';
            $dbSite = CSite::GetByID($SITE_ID);
            if (!$dbSite->Fetch())
            {
                $dbSite = CSite::GetList($by="sort", $order="desc");
                $arSite = $dbSite->Fetch();
                $SITE_ID = $arSite['ID'];
            }

            $tmpid = md5(uniqid(""));
            $arCatalogs = Array();
            $arCatalogsParams = Array();


            $ib = new CIBlock;
            $res = CIBlock::GetList(Array(), Array("=TYPE" => $IBLOCK_TYPE_ID, "IBLOCK_ID"=>$IBLOCK_ID, 'CHECK_PERMISSIONS' => 'Y', 'MIN_PERMISSION' => 'W'));

            if(!$res)
            {
                $strImportErrorMessage .= str_replace("#ERROR#", $ib->LAST_ERROR, str_replace("#NAME#", "[".$IBLOCK_ID."] \"".$IBLOCK_NAME."\" (".$IBLOCK_XML_ID.")", GetMessage("CICML_ERROR_ADDING_CATALOG"))).".<br>";
                $STT_CATALOG_ERROR++;
            }
            else
            {
                
				$el = new CIBlockElement();
                $arProducts = Array();
                $products = $xml->shop->offers->offer;

                print GetMessage("CET_PROCESS_GOING");
				print ("</br>");
				print (GetMessage("IMPORT_MSG1").$CUR_FILE_POS);
				print (GetMessage("IMPORT_MSG2").count($xml->shop->offers->offer));
				print (GetMessage("IMPORT_MSG3"));
                for ($j = $CUR_FILE_POS; $j < count($xml->shop->offers->offer); $j++)
                {

                    // устанваливаем значние до которого добрались
                    $CUR_FILE_POS=$j;

                    $xProductNode = $xml->shop->offers->offer[$j];

                    $PRODUCT_XML_ID = 'yml_'.$xProductNode['id'];

                    $PRODUCT_TYPE = $xProductNode['type'];

                    // выцепляем тип товара и получаем его название
                    switch ($PRODUCT_TYPE)
                    {
                        case "vendor.model":
                            $PRODUCT_NAME_UNCODED = $xProductNode->vendor." ".$xProductNode->model;
                            break;

                        case "book":
                        case "audiobook":
                            $PRODUCT_NAME_UNCODED = $xProductNode->author." ".$xProductNode->name;
                            break;

                        case "artist.title":
                        $PRODUCT_NAME_UNCODED = $xProductNode->artist." ".$xProductNode->title;
                            break;

                        default:
                        $PRODUCT_NAME_UNCODED = $xProductNode->name;
                    }

					//$PRODUCT_NAME_UNCODED = $xProductNode->typePrefix." ".$xProductNode->model;
                   // $PRODUCT_NAME_UNCODED = $xProductNode->model;
					//if (!isset($PRODUCT_NAME_UNCODED)) $PRODUCT_NAME_UNCODED=$xProductNode->name;

					$PRODUCT_NAME=iconv('utf-8',SITE_CHARSET, trim($PRODUCT_NAME_UNCODED));
					$det_text=$xProductNode->description;
					
					$is_import_by_filter=false;
					$import_by_filter=array();
					if (!empty($CAT_FILTER_I)){
						$import_by_filter=explode(',',$CAT_FILTER_I);
						$is_import_by_filter=true;
					}
					
					
					$is_filtreded=true;
					if ($is_import_by_filter){
						$is_filtreded=false;
						foreach($import_by_filter as $val){
							
							if (strpos($PRODUCT_NAME,$val)!==false || strpos($det_text,$val)!==false){
								$is_filtreded=true;
							}
						}
					}
					
                    

                    //$fp = @fopen($_SERVER["DOCUMENT_ROOT"]."/log.txt", "a");

                    $PRODUCT_XML_CAT_ID='yml_'.$xProductNode->categoryId;


                    $ProductPrice=$xProductNode->price;
					
					//price changing
					if (doubleval($price_modifier)!==1.00)
					{
						$ProductPrice=$ProductPrice*doubleval($price_modifier);
					}

                    global $USER;
					
					if ($is_filtreded){
					
					
					//Бедросова 9.09.2014 - вот этот мод по свойствам должен выйти в основной релиз
					
						$params=$xProductNode->param;
						$strana="";
						$material="";
						$size="";
						$vendor="";
						$vendor=$xProductNode->vendor;
						$articul=$xProductNode->vendorCode;
						$strana="";
						$strana=$xProductNode->country_of_origin;
						
						foreach($xProductNode->param as $param){
							
							$atr=array();
							$atr=$param[0]->attributes();
							//print $atr['name'];
							
							if ($atr['name']=='Цвет') $color=''.$param;
							if ($atr['name']=='Материал') $material=''.$param;
							if ($atr['name']=='Размер') $size=''.$param;
							//if ($atr['name']=='vendor') $vendor=$param;
							//if ($atr['name']=='vendor') $vendor=$param;
						
						}
						//die();
						$more_photo=array();
						$n=0;
						$p=0;
						
						$count_pik=0;
						foreach ($xProductNode->picture as $dop_pic){
							$count_pik++;//Первую картинку не фигачим в дополнительные картинки - она уже в детальную ушла
							if ($count_pik>1){
								$dop_pic_arr=CFile::MakeFileArray($dop_pic);
								$dop_pic_arr["MODULE_ID"] = "iblock";
								$more_photo['n'.$p]=$dop_pic_arr;
								$p++;
							}
						

						}
					//Бедросова конец 9.09.2014 - вот этот мод должен выйти в основной релиз
					

//$description=iconv('utf-8',SITE_CHARSET,(string)$xProductNode->description);
$description=$xProductNode->description;

					
                    $arLoadProductArray = Array(
                        "MODIFIED_BY"		=>	$USER->GetID(),
                        "IBLOCK_SECTION"	=>	$ResCatArr["".$PRODUCT_XML_CAT_ID.""],
                        "IBLOCK_ID"			=>	$IBLOCK_ID,
                        "NAME"				=>	$PRODUCT_NAME,
                        "XML_ID"				=>	$PRODUCT_XML_ID,
                        "ACTIVE"=>$xProductNode['available']=='true'?'Y':'N',
                        "DETAIL_PICTURE" => CFile::MakeFileArray($xProductNode->picture[0]),
						"PREVIEW_PICTURE" => CFile::MakeFileArray($xProductNode->picture[0]),
                        //"DETAIL_TEXT"=>iconv('utf-8',SITE_CHARSET,$xProductNode->description),
                        "DETAIL_TEXT"=>$xProductNode->description,
                        "DETAIL_TEXT_TYPE"=>'html',
                        // получаем код товара
                      // "CODE" => CUtil::translit(($vendor?$vendor:$PRODUCT_NAME)." ".$articul, 'ru', array()),
						 "CODE" => $articul,
                    );

					
					  $arLoadProductArray2 = Array(
                        "MODIFIED_BY"		=>	$USER->GetID(),
                        "IBLOCK_ID"			=>	$IBLOCK_ID,
                        "NAME"				=>	$PRODUCT_NAME,
                        "XML_ID"				=>	$PRODUCT_XML_ID,
                        "ACTIVE"=>$xProductNode['available']=='true'?'Y':'N',
                        "DETAIL_PICTURE" => CFile::MakeFileArray($xProductNode->picture[0]),
						"PREVIEW_PICTURE" => CFile::MakeFileArray($xProductNode->picture[0]),
                        //"DETAIL_TEXT"=>iconv('utf-8',SITE_CHARSET,$xProductNode->description,false),
                        "DETAIL_TEXT"=>$xProductNode->description,false,
						//"IBLOCK_SECTION"	=>	$ResCatArr["".$PRODUCT_XML_CAT_ID.""],
                    );


                    $res = CIBlockElement::GetList(Array(), Array("IBLOCK_ID"=>$IBLOCK_ID, "XML_ID"=>$PRODUCT_XML_ID));
                    $bNewRecord_tmp = False;

                    // флажок что все ништяк
                    $flag_ok = 0;

                    $PRODUCT_ID=false;
					// товар уже есть?
                    if ($arr = $res->Fetch())
                    {
                        $PRODUCT_ID = $arr["ID"];

                        if ($ONLY_PRICE!='Y')
                        {
                            // обновляем
                            $flag_ok = $el->Update($PRODUCT_ID, $arLoadProductArray2);
                            //fwrite($fp, "already was. updated ".$PRODUCT_XML_ID." ".$PRODUCT_NAME."\n");

                            // уже есть такой код
                            if (!$flag_ok)
                            {
                                // да жалко что ли. поменяем
                                $arLoadProductArray["CODE"] = $arLoadProductArray["XML_ID"];
                                // еще раз обновляй
                                $flag_ok = $el->Update($PRODUCT_ID, $arLoadProductArray);
                              //fwrite($fp, "code changed to xmlid ".$PRODUCT_XML_ID." ".$PRODUCT_NAME."\n");
                            }
							
						
							CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "YML_IMPORT_BRAND",$vendor);
							CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "MAKER_COUNTRY",$strana);
							CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "CML2_ARTICLE",$articul);

							
							$va_props = CIBlockElement::GetProperty($IBLOCK_ID, $PRODUCT_ID, array(), Array("CODE" => "MORE_PHOTO"));
								while($pic_props = $va_props->Fetch())
								{
									if($pic_props["VALUE"])
									{
										$ar_del[$pic_props["PROPERTY_VALUE_ID"]] = Array("VALUE" => Array("del" => "Y"));
										CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "MORE_PHOTO", $ar_del);
										CFile::Delete($pic_props["VALUE"]);
									}
								}
								
								
						 CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "MORE_PHOTO", $more_photo);
							  
							  


							
							//конец Бедросова 09.09.2014 мод по свойствам - не забыть включить в релиз

                        }
						else{
						
							$flag_ok =true;
						
						}
                    }
                    // такого товара еще не было
                    else
                    {
                        if ($ONLY_PRICE!='Y')
                        {
                            // добавляем
							$flag_ok=false;
                            $PRODUCT_ID  = $el->Add($arLoadProductArray);
							if ($PRODUCT_ID) $flag_ok=true;
                            //fwrite($fp, "new record ".$PRODUCT_XML_ID." ".$PRODUCT_NAME."\n");

                            // не добавился! такой код уже есть
                            if (!$flag_ok)
                            {
                                // поменяли
                                $arLoadProductArray["CODE"] = $arLoadProductArray["XML_ID"];
                                // еще раз добавляй
                                $PRODUCT_ID = $el->Add($arLoadProductArray);
								if ($PRODUCT_ID) $flag_ok=true;
                              //  fwrite($fp, "code changed to xmlid ".$PRODUCT_XML_ID." ".$PRODUCT_NAME."\n");
                            }
							
							
							CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "YML_IMPORT_BRAND",$vendor);
							CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "MAKER_COUNTRY",$strana);
							CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "CML2_ARTICLE",$articul);
							
							$va_props = CIBlockElement::GetProperty($IBLOCK_ID, $PRODUCT_ID, array(), Array("CODE" => "MORE_PHOTO"));
								while($pic_props = $va_props->Fetch())
								{
									if($pic_props["VALUE"])
									{
										$ar_del[$pic_props["PROPERTY_VALUE_ID"]] = Array("VALUE" => Array("del" => "Y"));
										CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "MORE_PHOTO", $ar_del);
										CFile::Delete($pic_props["VALUE"]);
									}
								}
								
								
						 CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "MORE_PHOTO", $more_photo);
							//конец Бедросова 09.09.2014 мод по свойствам - не забыть включить в релиз

                        }
						else{
						
								$flag_ok=true;
						}

                    }

                    if ($flag_ok)
                    {

                        
						
						
						$arFieldsProduct = array
                        (
                            "ID" => $PRODUCT_ID,
                            "QUANTITY" => 10,
                            "CAN_BUY_ZERO" => "Y"
                        );
                        CCatalogProduct::Add($arFieldsProduct);
						
						
						$arFieldsSklad = Array(
								"PRODUCT_ID" => $PRODUCT_ID,
								"STORE_ID" => 1,
								"AMOUNT" => 10,
							);
							
						CCatalogStoreProduct::Add($arFieldsSklad);


                        //Обновляем базовую цену для товара
                        $price_ok=CPrice::SetBasePrice($PRODUCT_ID,$ProductPrice,"RUB");

                      // if ($price_ok) print "Цена успешно обновлена<br>";
					   
					   
					   

                        if ($ONLY_PRICE!='Y')
                        {


                            $PROPERTY_VALUE=array();
                            $count=0;
                            if (isset ($xOfferListNode))
							{
								foreach($xOfferListNode->param as $param)
								{
									//print $param['name']."<br>";
									$PROPERTY_VALUE['n'.$count]=array('VALUE'=>iconv('utf-8',SITE_CHARSET,$param),'DESCRIPTION'=>iconv('utf-8',SITE_CHARSET,$param['name']));
									$count++;

								}
							}
							if (isset ($xProductNode))
							{
								foreach($xProductNode->param as $param){

	//                                print $param['name']."<br>";
									$PROPERTY_VALUE['n'.$count]=array('VALUE'=>iconv('utf-8',SITE_CHARSET,$param),'DESCRIPTION'=>iconv('utf-8',SITE_CHARSET,$param['name']));
									$count++;

								}
							}

                            $ELEMENT_ID = $PRODUCT_ID;  // код элемента
                            $PROPERTY_CODE = "CML2_ATTRIBUTES";  // код свойства
                            //$PROPERTY_VALUE = $prop_array;  // значение свойства

                            // Установим новое значение для данного свойства данного элемента
                            CIBlockElement::SetPropertyValuesEx($ELEMENT_ID, $IBLOCK_ID, array($PROPERTY_CODE=>$PROPERTY_VALUE));

                        }

                    }
                    else
                    {
                        echo "\nError: ".$el->LAST_ERROR."\n";
                        echo $PRODUCT_XML_ID." ".$PRODUCT_NAME."\n\n";
                       // fwrite($fp, "here was error ".$PRODUCT_XML_ID." ".$PRODUCT_NAME."\n");
                    }
					
					}

                   // fclose($fp);

                    // если таймер закончился, $bAllLinesLoaded = false
                    if (!($bAllLinesLoaded = $this->CSVCheckTimeout($max_execution_time))){

							
							break;
							
							
							}

                }

            }
        }
		
		//die();

        // не успели закончить до таймера
        if (!$bAllLinesLoaded)
        {
            // увеличиваем позицию
            $CUR_FILE_POS++;
            $this->FILE_POS = $CUR_FILE_POS;
            // флажок что надо перезагрузиться
            $this->AllLinesLoaded = false;

        }


        if ($bTmpUserCreated)
        {

            unset($USER);
            if (isset($USER_TMP))
            {
                $USER = $USER_TMP;
            }
            unset($USER_TMP);
        }
        return $strImportErrorMessage;
    }
}
?>
