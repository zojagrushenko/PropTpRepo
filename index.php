private function propertiesTP()
	{
		//Проверяем свойства в инфоблоке и добавляем если не хватает
		// $this->PropDef();

		if(!CModule::IncludeModule("iblock")) return false;
		if(empty($this->iblockIdTP)) return false;

		//Далее получаем свойства у элеметов и добавляем если не хватает
	 	$arSelect = Array('ID', 'XML_ID');
		$arFilter = Array("IBLOCK_ID"=>$this->iblockIdTP);
		$res = CIBlockElement::GetList(
		    array("ID" => "ASC"),
		    $arFilter,
		    false,
		    false,
		    $arSelect
		    );
		  
		while($ob = $res->GetNextElement())
		    {
		    $arFields = $ob->GetFields();
		    $arIdProdSKU[$arFields['XML_ID']] = $arFields['ID'];
 		    // $arResult[$arFields['ID']] = $this->getProperty($arFields['ID']);
		    }

		
		if(empty($_SESSION['lastid']['propertiesTP'])){
			$lastId = 0;
		} else {
			$lastId = $_SESSION['lastid']['propertiesTP'];
		}

		$Properties = $this->send("getTP", "properties", $lastId);
		if(!empty($Properties)) {
			$_SESSION['lastid']['propertiesTP'] = max(array_keys($Properties));
			echo '<script>	startCountdown();	function reload (){document.location.href = location.href};setTimeout("reload()", 2000);</script>';
		} else {
			$_SESSION['lastid']['propertiesTP'] = 0;
			echo 'Все необходимые элементы добавлены и обновлены<br>';
		}
				// pp($lastId);


    	$idExtra = array();
		$res_ex = CExtra::GetList();
		  while($res_ex_list = $res_ex->GetNext())
		  {
		    $idExtra[$res_ex_list['PERCENTAGE']] = $res_ex_list['ID'];
		  }

		$res_measure = CCatalogMeasure::getList();
        while($measure = $res_measure->Fetch()) {
            $arMes[$measure['MEASURE_TITLE']] = $measure['ID'];
        } 
		$idsprod = array_column($Properties, 'PRODUCT_ID');
        $arSel = Array('ID', 'XML_ID');
		$arFil = Array("IBLOCK_ID"=>$this->iblockId, "XML_ID"=>$idsprod);
		$res1 = CIBlockElement::GetList(
				    array("ID" => "ASC"),
				    $arFil,
				    false,
				    false,
				    $arSel
				    );
				  
				while($ob2 = $res1->GetNextElement())
				    {
				    $arFiel = $ob2->GetFields();
				    $arIdProd[$arFiel['XML_ID']] = $arFiel['ID'];
				    }


		foreach ($Properties as $kProd => $vProd) {

			if(array_key_exists($vProd["ID"], $arIdProdSKU)){
				$ID = $arIdProdSKU[$vProd["ID"]];
			} else {
				$ID = 0;
			}

			$IBLOCK_SECTION_ID = $vProd["IBLOCK_SECTION_ID"];

			$arCatalog = CCatalog::GetByID($this->iblockIdTP);
			if (!$arCatalog) return false;


			$intProductIBlock = $arCatalog['PRODUCT_IBLOCK_ID']; // ID инфоблока товаров
			$intSKUProperty = $arCatalog['SKU_PROPERTY_ID']; // ID свойства в инфоблоке предложений типа "Привязка к товарам (SKU)"

			$arProp = array();
			if ($vProd['PRODUCT_ID'])
			{
			 	$arProp[$intSKUProperty] = $arIdProd[$vProd['PRODUCT_ID']];
			}
			$code = randString(3);
			$arFields = array(
				//"MODIFIED_BY"    => $USER->GetID(),
				"IBLOCK_SECTION_ID" => $IBLOCK_SECTION_ID,
				"IBLOCK_ID"     	=> $this->iblockIdTP,
				"PROPERTY_VALUES"	=> $arProp,
				"NAME"         	 	=> htmlspecialchars_decode($vProd['NAME']),
				"CODE"			 	=> $vProd['CODE'].$code,		
				"ACTIVE"         	=> $vProd['ACTIVE']

				);

			$elem = new CIBlockElement;

			if($ID > 0)
			{
				$res = $elem->Update($ID, $arFields);
				$countUpdate++;
					$backRes[$kProd] = array(
						'ADDID' 	=>	$ID
						);

				if(!empty($vProd["PROPERTY"])){
						foreach ($vProd["PROPERTY"] as $kElprop => $vElprop) {
															
							if($vElprop["CODE"] != 'CML2_LINK'){

								if($vElprop['PROPERTY_TYPE'] == 'L'){
									$db_enum_list = CIBlockProperty::GetPropertyEnum($vElprop["CODE"], array("ID"=>"ASC", "SORT"=>"ASC"), array("IBLOCK_ID"=>$this->iblockIdTP));
										while($ar_enum_list = $db_enum_list->GetNext())
										{
											$idEnum[$ar_enum_list['XML_ID']] = $ar_enum_list['ID'];
										}
										
										if ($vElprop['MULTIPLE']=='Y' ){
										$PROPERTY_VALUE = array();
							              	foreach($vElprop['VALUE_XML_ID'] as $val){
												$PROPERTY_VALUE[] = $idEnum[$val];
							              	};
							            } else {
											$PROPERTY_VALUE = $idEnum[$vElprop['VALUE_XML_ID']];
										}

								} else {
									$PROPERTY_VALUE = $vElprop["VALUE"];
								}

								$PROPERTY_CODE = $vElprop["CODE"];
								CIBlockElement::SetPropertyValuesEx($ID, false, array($PROPERTY_CODE => $PROPERTY_VALUE));

							}

						}
					}



				$ar_res = CCatalogProduct::GetByID($ID);

				$arFieldsProd = array(
					"ID" 			=> $ID,
					"AVAILABLE"		=> 'Y',
					"TYPE"			=> 4,
					"MEASURE"		=> $arMes[$vProd["PRODUCT"]["MEASURE"]]
					);
				if(empty($ar_res)){

			   		CCatalogProduct::Add($arFieldsProd);

			    } else {

					CCatalogProduct::Update($ID, $arFieldsProd);
			    }
			 //    if(!empty($vProd["SET"])){
			 //    //Создаем набор  	
				// 	foreach ($vProd["SET"] as $kSet => $vSet) {

				// 		$arSaveSet = array(
				// 		    'TYPE'    => $vSet['TYPE'],
				// 		    'ITEM_ID' => $vSet['ITEM_ID'],
				// 		    'ACTIVE'  => $vSet['ACTIVE'],

				// 		);

				// 			foreach ($vSet['ITEMS'] as $iSet) {
				// 			$arSaveSet['ITEMS'][] = array(

				// 	            'ITEM_ID'          => $iSet['ITEM_ID'],
				// 	            'QUANTITY'         => $iSet['QUANTITY'],
				// 	            // 'DISCOUNT_PERCENT' => $iSet['DISCOUNT_PERCENT'],
				// 	            'SORT'             => $iSet['SORT']

				// 	        	);
				// 			}

				// 		$setId = CCatalogProductSet::add($arSaveSet); // создание самого "комплекта"

				// 	}
				// }
			      	//обнавляем цену

					foreach ($vProd["PRODUCT"]['PRICE'] as $keyP => $valP) {

						if(!empty($valP['EXTRA_ID']) && empty($idExtra[$valP['EXTRA_ID']])){
							CExtra::Add(array('NAME' => $valP['EXTRA_ID'].'%', 'PERCENTAGE'=>$valP['EXTRA_ID']));
						}

							$arFieldsEx = array(
							    "PRODUCT_ID" => $ID,
							    "CATALOG_GROUP_ID" => $valP['CATALOG_GROUP_ID'],
							    "PRICE" => $valP['PRICE'],
							    "CURRENCY" => "RUB",

							);

							if(!empty($valP['EXTRA_ID'])) $arFieldsEx['EXTRA_ID'] = $idExtra[$valP['EXTRA_ID']];

							$res = CPrice::GetList(
							        array(),
							        array(
							                "PRODUCT_ID" => $ID,
							                "CATALOG_GROUP_ID" => $valP['CATALOG_GROUP_ID']
							            )
							    );

							if ($arr = $res->Fetch())
							{
							    CPrice::Update($arr["ID"], $arFieldsEx,true);
							}
							else
							{
							    CPrice::Add($arFieldsEx,true);
							}
					}

						$rsRatios = CCatalogMeasureRatio::getList(array(), array('PRODUCT_ID' => $ID), false, false, array('*'));
				        while ($arRatio = $rsRatios->Fetch()) {
				            $arMeasId[$arRatio['PRODUCT_ID']] = $arRatio['ID'];
				        }

						if(empty($arMeasId[$ID])){
							CCatalogMeasureRatio::add (array('PRODUCT_ID' => $ID, 'RATIO' => $vProd["PRODUCT"]['MEASURE_RATIO']['RATIO'], 'IS_DEFAULT' => 'Y'));

				        }else {
							CCatalogMeasureRatio::update($arMeasId[$ID], array('PRODUCT_ID' => $ID, 'RATIO' => $vProd["PRODUCT"]['MEASURE_RATIO']['RATIO'], 'IS_DEFAULT' => 'Y'));
						}

			} else {

				$ID = $elem->Add($arFields);

				if ($ID > 0)
				{
					$countAdd++;
					$backRes[$kProd] = array(
						'ADDID' 	=>	$ID
						);

					$PROPERTY_VALUE = $vProd["ID"];
					$PROPERTY_CODE = 'ORIGINAL_ID';
					CIBlockElement::SetPropertyValuesEx($ID, false, array($PROPERTY_CODE => $PROPERTY_VALUE));

					if(!empty($vProd["PROPERTY"])){
						foreach ($vProd["PROPERTY"] as $kElprop => $vElprop) {
															
							if($vElprop["CODE"] != 'CML2_LINK'){

								if($vElprop['PROPERTY_TYPE'] == 'L'){
									$db_enum_list = CIBlockProperty::GetPropertyEnum($vElprop["CODE"], array("ID"=>"ASC", "SORT"=>"ASC"), array("IBLOCK_ID"=>$this->iblockIdTP));
										while($ar_enum_list = $db_enum_list->GetNext())
										{
											$idEnum[$ar_enum_list['XML_ID']] = $ar_enum_list['ID'];
										}
										
										if ($vElprop['MULTIPLE']=='Y' ){
										$PROPERTY_VALUE = array();
							              	foreach($vElprop['VALUE_XML_ID'] as $val){
												$PROPERTY_VALUE[] = $idEnum[$val];
							              	};
							            } else {
											$PROPERTY_VALUE = $idEnum[$vElprop['VALUE_XML_ID']];
										}

								} else {
									$PROPERTY_VALUE = $vElprop["VALUE"];
								}

								$PROPERTY_CODE = $vElprop["CODE"];
								CIBlockElement::SetPropertyValuesEx($ID, false, array($PROPERTY_CODE => $PROPERTY_VALUE));

							}

						}
					}

					$ar_res = CCatalogProduct::GetByID($ID);
						$arFieldsProd = array(
							"ID" 			=> $ID,
							"AVAILABLE"		=> 'Y',
							"TYPE"			=> 4,
							"MEASURE"		=> $arMes[$vProd["PRODUCT"]["MEASURE"]]
							);
					
					if(empty($ar_res)){

				      	CCatalogProduct::Add($arFieldsProd);

				      	//Создаем набор 
				      	if(is_array($vProd["SET"])){
							foreach ($vProd["SET"] as $kSet => $vSet) {

								$arSaveSet = array(
								    'TYPE'    => $vSet['TYPE'],
								    'ITEM_ID' => $ID,
								    'ACTIVE'  => $vSet['ACTIVE'],

								);

									foreach ($vSet['ITEMS'] as $iSet) {
									$arSaveSet['ITEMS'][] = array(

							            'ITEM_ID'          => $itemSet,
							            'QUANTITY'         => $iSet['QUANTITY'],
							            // 'DISCOUNT_PERCENT' => $iSet['DISCOUNT_PERCENT'],
							            'SORT'             => $iSet['SORT']

							        	);
									}

								$setId = CCatalogProductSet::add($arSaveSet); // создание самого "комплекта"
								CCatalogProductSet::recalculateSetsByProduct($ID);
								unset($itemSet);

							}
						} elseif ($vProd["SET"] == 'itemSet')$itemSet = $ID;

				      	//обнавляем цену
						foreach ($vProd["PRODUCT"]['PRICE'] as $keyP => $valP) {
							if(!empty($valP['EXTRA_ID']) && empty($idExtra[$valP['EXTRA_ID']])){
								CExtra::Add(array('NAME' => $valP['EXTRA_ID'].'%', 'PERCENTAGE'=>$valP['EXTRA_ID']));
							}
								$arFieldsEx = array(
								    "PRODUCT_ID" => $ID,
								    "CATALOG_GROUP_ID" => $valP['CATALOG_GROUP_ID'],
								    "PRICE" => $valP['PRICE'],
								    "CURRENCY" => "RUB",

								);

								if(!empty($valP['EXTRA_ID'])) $arFieldsEx['EXTRA_ID'] = $idExtra[$valP['EXTRA_ID']];

								$res = CPrice::GetList(
								        array(),
								        array(
								                "PRODUCT_ID" => $ID,
								                "CATALOG_GROUP_ID" => $valP['CATALOG_GROUP_ID']
								            )
								    );

								if ($arr = $res->Fetch())
								{
								    CPrice::Update($arr["ID"], $arFieldsEx,true);
								}
								else
								{
								    CPrice::Add($arFieldsEx,true);
								}

						}

						$rsRatios = CCatalogMeasureRatio::getList(array(), array('PRODUCT_ID' => $ID), false, false, array('*'));
				        while ($arRatio = $rsRatios->Fetch()) {
				            $arMeasId[$arRatio['PRODUCT_ID']] = $arRatio['ID'];
				        }

						if(empty($arMeasId[$ID])){
							CCatalogMeasureRatio::add (array('PRODUCT_ID' => $ID, 'RATIO' => $vProd["PRODUCT"]['MEASURE_RATIO']['RATIO'], 'IS_DEFAULT' => 'Y'));

				        }else {
							CCatalogMeasureRatio::update($arMeasId[$ID], array('PRODUCT_ID' => $ID, 'RATIO' => $vProd["PRODUCT"]['MEASURE_RATIO']['RATIO'], 'IS_DEFAULT' => 'Y'));
						}

					}
				}
			}
			// if(!$res) echo $elem->LAST_ERROR;
		}

		if(count($arIdProdSKU)>0) echo count($arIdProdSKU).' элементов в базе<br>';
		if($countAdd>0) echo 'Добавлено '.$countAdd.' элементов<br>';
		if($countUpdate>0) echo 'Обновлено '.$countAdd.' элементов<br>';
	
	}
