<?php
namespace Merconis\Core;

class ls_shop_filterHelper {
    public static function getFilterSummary() {
        $arr_filterSummary = [
            'arr_attributes' => [],
            'arr_producers' => $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['producers'],
            'arr_price' => $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['price']
        ];

        if (is_array($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'])) {
            foreach ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'] as $int_filterAttributeId => $arr_filterValues) {
                $str_filterAttributeName = ls_shop_languageHelper::getMultiLanguage($int_filterAttributeId, 'tl_ls_shop_attributes', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
                $arr_filterSummary['arr_attributes'][$int_filterAttributeId] = [
                    'str_title' => $str_filterAttributeName,
                    'arr_values' => [],
                    'str_logicalOperator' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['general'][$_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'][$int_filterAttributeId]]
                ];

                foreach ($arr_filterValues as $int_filterValueId) {
                    $str_filterValueName = ls_shop_languageHelper::getMultiLanguage($int_filterValueId, 'tl_ls_shop_attribute_values', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
                    $arr_filterSummary['arr_attributes'][$int_filterAttributeId]['arr_values'][$int_filterValueId] = $str_filterValueName;
                }
            }
        }

        $obj_template = new \FrontendTemplate('template_filterSummary_default');
        $obj_template->arr_filterSummary = $arr_filterSummary;
        return $obj_template->parse();
    }

	public static function createEmptyFilterSession() {
		$_SESSION['lsShop']['filter'] = array(
			'criteria' => array(
				'attributes' => array(),
				'price' => array(
					'low' => 0,
					'high' => 0
				),
				'producers' => array()
			),
			'arrCriteriaToUseInFilterForm' => array(
				'attributes' => array(),
				'price' => array(
					'low' => null,
					'high' => null
				),
				'producers' => array()
			),
			'matchedProducts' => array(),
			'matchedVariants' => array(),
			'matchEstimates' => array(
				'attributeValues' => array(),
				'producers' => array()
			),
			'lastResetTimestamp' => time(),
			'noMatchEstimatesDetermined' => false
		);
	}

	public static function getFilterFieldValues($arrFilterFieldInfo = null) {
		if (!$arrFilterFieldInfo) {
			return array();
		}

		$arrFilterFieldValues = array();

		switch ($arrFilterFieldInfo['dataSource']) {
			case 'producer':
				$objFilterFields = \Database::getInstance()
					->prepare("
						SELECT		*
						FROM		`tl_ls_shop_filter_field_values`
						WHERE		`pid` = ?
						ORDER BY	`sorting` ASC
					")
					->execute($arrFilterFieldInfo['id']);

				if ($objFilterFields->numRows) {
					$arrFilterFieldValues = $objFilterFields->fetchAllAssoc();
				}
				break;

			case 'attribute':
				$arrAttributeValues = ls_shop_generalHelper::getAttributeValues($arrFilterFieldInfo['sourceAttribute']);
				foreach ($arrAttributeValues as $attributeValueID => $arrAttributeValue) {
					$tmpFilterFieldValue = $arrAttributeValue;
					$tmpFilterFieldValue['filterValue'] = $attributeValueID;
					$arrFilterFieldValues[] = $tmpFilterFieldValue;
				}
				break;
		}

		return $arrFilterFieldValues;
	}

	public static function getFilterFieldInfos() {
		/** @var \PageModel $objPage */
		global $objPage;

		if (!isset($GLOBALS['merconis_globals']['filterFieldInfos'])) {
			$arrFilterFields = array();

			$objFilterFields = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_ls_shop_filter_fields`
				WHERE		`published` = '1'
				ORDER BY	`priority` DESC
			")
				->execute();

			while ($objFilterFields->next()) {
				$arrFilterFields[$objFilterFields->id] = $objFilterFields->row();
				$arrFilterFields[$objFilterFields->id]['title'] = ls_shop_languageHelper::getMultiLanguage($objFilterFields->id, 'tl_ls_shop_filter_fields', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
				$arrFilterFields[$objFilterFields->id]['fieldValues'] = ls_shop_filterHelper::getFilterFieldValues($arrFilterFields[$objFilterFields->id]);
			}

			$GLOBALS['merconis_globals']['filterFieldInfos'] = $arrFilterFields;
		}

		return $GLOBALS['merconis_globals']['filterFieldInfos'];
	}

	public static function resetCriteriaToUseInFilterForm() {
		$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm'] = array(
			'attributes' => array(),
			'price' => array(
				'low' => null,
				'high' => null
			),
			'producers' => array()
		);
	}

	public static function addPriceToCriteriaUsedInFilterForm($price, $where = 'arrCriteriaToUseInFilterForm') {
		if ($_SESSION['lsShop']['filter'][$where]['price']['low'] === null || $price < $_SESSION['lsShop']['filter'][$where]['price']['low']) {
			$_SESSION['lsShop']['filter'][$where]['price']['low'] = $price;
		}
		if ($_SESSION['lsShop']['filter'][$where]['price']['high'] === null || $price > $_SESSION['lsShop']['filter'][$where]['price']['high']) {
			$_SESSION['lsShop']['filter'][$where]['price']['high'] = $price;
		}
	}

	public static function addProducerToCriteriaUsedInFilterForm($strProducer = '', $where = 'arrCriteriaToUseInFilterForm') {
		if (!$strProducer || in_array($strProducer, $_SESSION['lsShop']['filter'][$where]['producers'])) {
			return;
		}

		$_SESSION['lsShop']['filter'][$where]['producers'][] = $strProducer;
	}

	public static function addAttributeValueToCriteriaUsedInFilterForm($attributeID = null, $varAttributeValueID = null, $where = 'arrCriteriaToUseInFilterForm') {
		if (!$attributeID || !$varAttributeValueID) {
			return;
		}

		if (is_array($varAttributeValueID)) {
			foreach ($varAttributeValueID as $attributeValueID) {
				self::addAttributeValueToCriteriaUsedInFilterForm($attributeID, $attributeValueID, $where);
			}
			return;
		}

		if (!isset($_SESSION['lsShop']['filter'][$where]['attributes'][$attributeID])) {
			$_SESSION['lsShop']['filter'][$where]['attributes'][$attributeID] = array();
		}

		if (!in_array($varAttributeValueID, $_SESSION['lsShop']['filter'][$where]['attributes'][$attributeID])) {
			$_SESSION['lsShop']['filter'][$where]['attributes'][$attributeID][] = $varAttributeValueID;
		}
	}

	/*
	 * This function checks whether a product matches the filter or not. Several
	 * checks are performed in this function and as soon as one filter criteria
	 * doesn't match, this function returns false, indicating that the product
	 * should be filtered out. If that happens other filter criteria will not
	 * be checked. There's one exception though: If a product has variants and the product
	 * itself does not match the filter criteria, the variants will be checked
	 * because if one ore more variants match, the product has to be shown in
	 * product lists as well.
	 */
	public static function checkIfProductMatchesFilter($arrProductInfo = null, $arrCriteriaToFilterWith = null, $blnStoreProductAndVariantMatchesInSession = true, &$numVariantMatches = 0) {
		if (!$arrCriteriaToFilterWith) {
			$arrCriteriaToFilterWith = $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'];
		}

		$blnWholeProductCouldStillMatch = true;
		$blnVariantsCouldStillMatch = true;

		/*
		 * Check the producer first if it is set as a filter criteria
		 * because if it doesn't match, the product is already filtered
		 * out and we don't even need to check it's variants
		 */
		if (is_array($arrCriteriaToFilterWith['producers']) && count($arrCriteriaToFilterWith['producers'])) {
			if (!in_array($arrProductInfo['lsShopProductProducer'], $arrCriteriaToFilterWith['producers'])) {
				$blnWholeProductCouldStillMatch = false;
				$blnVariantsCouldStillMatch = false;
			}
		}

		/*
		 * Check the product's attributes
		 */
		if ($blnWholeProductCouldStillMatch) {
			if (is_array($arrCriteriaToFilterWith['attributes'])) {
				foreach ($arrCriteriaToFilterWith['attributes'] as $attributeID => $attributeValueIDs) {
					/*
					 * The array returned by array_intersect() contains the requested attributeValueIDs which
					 * are also included in the product's attributeValueIDs.
					 *
					 * In filterMode "or": If we get at least one attributeValueID
					 * that matches, the product is a match for this attribute,
					 * otherwise it's not and we return false.
					 *
					 * In filterMode "and": If all the attributeValueIDs match,
					 * the product is a match for this attribute, otherwise it's
					 * not and we return false.
					 */
					if ($_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'][$attributeID] === 'and') {
						if (count(array_intersect($attributeValueIDs, $arrProductInfo['attributeValueIDs'])) !== count($attributeValueIDs)) {
							$blnWholeProductCouldStillMatch = false;
							break;
						}
					} else {
						if (!count(array_intersect($attributeValueIDs, $arrProductInfo['attributeValueIDs']))) {
							$blnWholeProductCouldStillMatch = false;
							break;
						}
					}
				}
			}
		}

		/*
		 * Check the prices
		 */
		if ($blnWholeProductCouldStillMatch) {
			/*
			 * Ignore the price filter if the high filter price is not higher than 0 and not as least as high as the low filter price.
			 * This way it is possible to skip the price filter part by setting both filter parameters to 0.
			 */
			if ($arrCriteriaToFilterWith['price']['high'] >= $arrCriteriaToFilterWith['price']['low'] && $arrCriteriaToFilterWith['price']['high'] > 0) {
				if (!count($arrProductInfo['variants'])) {
					/*
					 * If the product doesn't have variants, the product's price has to be checked
					 */
					if ($arrProductInfo['price'] < $arrCriteriaToFilterWith['price']['low'] || $arrProductInfo['price'] > $arrCriteriaToFilterWith['price']['high']) {
						$blnWholeProductCouldStillMatch = false;
					}
				} else {
					/*
					 * If the product has variants, we have to use it's highest and lowest price to see,
					 * if it is possible to match or filter out the whole product or if we have to
					 * check each variant separately.
					 */
					if ($arrProductInfo['lowestPrice'] > $arrCriteriaToFilterWith['price']['low'] && $arrProductInfo['highestPrice'] < $arrCriteriaToFilterWith['price']['high']) {
						/*
						 * If the product's lowest price is higher than the low filter limit and the product's highest price is lower than the high filter limit,
						 * this means that all product variants must be within the price range and, regarding the price, the product matches as a whole.
						 */
					} else if ($arrProductInfo['highestPrice'] < $arrCriteriaToFilterWith['price']['low'] || $arrProductInfo['lowestPrice'] > $arrCriteriaToFilterWith['price']['high']) {
						/*
						 * If even the product's highest price is lower than the low filter limit or it's lowest
						 * price is higher than the high filter limit, this means that none of it's variants
						 * has a price within the range and the product has to be filtered out as a whole.
						 */
						$blnWholeProductCouldStillMatch = false;
						$blnVariantsCouldStillMatch = false;
					} else {
						/*
						 * If none of the above is true, this means that there's definitely a variant that has a price outside the range
						 * but there could be one ore more variants that have a price within.
						 */
						$blnWholeProductCouldStillMatch = false;
						$blnVariantsCouldStillMatch = true;
					}
				}
			}
		}

		if ($blnWholeProductCouldStillMatch) {
			/*
			 * If the product could still match as a whole and there is no filter criteria left
			 * to check for, the complete product actually matches the filter
			 */

			/*
			 * Count all variants as variant matches
			 */
			$numVariantMatches += count ($arrProductInfo['variants']);
			if ($blnStoreProductAndVariantMatchesInSession) {
				$_SESSION['lsShop']['filter']['matchedProducts'][$arrProductInfo['id']] = 'complete';
			}
			return true;
		} else if ($blnVariantsCouldStillMatch) {
			/*
			 * If the variants of the product could still match while the product itself couldn't,
			 * we have to perform the filter checks for the product's variants
			 */

			$blnPartialMatchForProductConfirmed = false;
			$numMatchingVariants = 0;

			/*
			 * IMPORTANT: Since we have not memorized which attributes might have matched for the
			 * whole product and because a variant could possibly fail to match the filter because
			 * it doesn't have a requested attribute although the product itself has it and should
			 * pass it to the variants, we have to actually pass all of the product's attributes
			 * to the variants! We do this by merging the product's attributeValueIDs with
			 * the variant's attributeValueIDs.
			 */

			foreach ($arrProductInfo['variants'] as $arrVariantInfo) {
				$blnThisVariantCouldStillMatch = true;

				$arrVariantInfo['mergedProductAndVariantAttributeValueIDs'] = array_merge($arrProductInfo['attributeValueIDs'], $arrVariantInfo['attributeValueIDs']);

				/*
				 * Check the variant's attributes
				 */
				if ($blnThisVariantCouldStillMatch) {
					if (is_array($arrCriteriaToFilterWith['attributes'])) {
						foreach ($arrCriteriaToFilterWith['attributes'] as $attributeID => $attributeValueIDs) {
							/*
							 * The array returned by array_intersect() contains
							 * the requested attributeValueIDs which are also included
							 * in the variant's attributeValueIDs.
							 *
							 * In filterMode "or": If we get at least one attributeValueID
							 * that matches, the variant is a match for this attribute,
							 * otherwise it's not and we return false.
							 *
							 * In filterMode "and": If all the attributeValueIDs
							 * match, the variant is a match for this attribute,
							 * otherwise it's not and we return false.
							 */

							if ($_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'][$attributeID] === 'and') {
								if (count(array_intersect($attributeValueIDs, $arrVariantInfo['mergedProductAndVariantAttributeValueIDs'])) !== count($attributeValueIDs)) {
									$blnThisVariantCouldStillMatch = false;
									break;
								}
							} else {
								if (!count(array_intersect($attributeValueIDs, $arrVariantInfo['mergedProductAndVariantAttributeValueIDs']))) {
									$blnThisVariantCouldStillMatch = false;
									break;
								}
							}
						}
					}
				}

				if ($blnThisVariantCouldStillMatch) {
					/*
					 * Ignore the price filter if the high filter price is not higher than 0 and not as least as high as the low filter price.
					 * This way it is possible to skip the price filter part by setting both filter parameters to 0.
					 */
					if ($arrCriteriaToFilterWith['price']['high'] >= $arrCriteriaToFilterWith['price']['low'] && $arrCriteriaToFilterWith['price']['high'] > 0) {
						if ($arrVariantInfo['price'] < $arrCriteriaToFilterWith['price']['low'] || $arrVariantInfo['price'] > $arrCriteriaToFilterWith['price']['high']) {
							$blnThisVariantCouldStillMatch = false;
						}
					}
				}

				/*
				 * If this variant could still match and there's nothing left to check,
				 * this means that the variant actually matches. In this case we definitely
				 * have a partial match for the product but we have to check the other
				 * variants as well because we have to store the information about
				 * which variants matched and which didn't.
				 */
				if ($blnThisVariantCouldStillMatch) {
					/*
					 * Count this variant as match
					 */
					$numVariantMatches++;

					if ($blnStoreProductAndVariantMatchesInSession) {
						$_SESSION['lsShop']['filter']['matchedVariants'][$arrVariantInfo['id']] = true;
					}
					$blnPartialMatchForProductConfirmed = true;
					$numMatchingVariants++;
				} else {
					if ($blnStoreProductAndVariantMatchesInSession) {
						$_SESSION['lsShop']['filter']['matchedVariants'][$arrVariantInfo['id']] = false;
					}
				}
			}

			/*
			 * If we don't have partial match for the product or, in other words, none of the product's
			 * variants matched, the whole product must be filtered out. If we do have a partial match
			 * we have to return true to prevent the product from being filtered out but we have to
			 * store the information that the match was only partial.
			 */
			if (!$blnPartialMatchForProductConfirmed) {
				if ($blnStoreProductAndVariantMatchesInSession) {
					$_SESSION['lsShop']['filter']['matchedProducts'][$arrProductInfo['id']] = 'none';
				}
				return false;
			} else {
				/*
				 * If we have a partial match which means that the product itself didn't match but
				 * one or more variants did, we have to check if perhaps all of the product's variants
				 * matched because in this case we evaluate this as a complete match because the
				 * product itself can not be ordered and since all of it's variants match we can
				 * consider the product a full match even though the matching criteria is not completely
				 * part of the product's main data.
				 */
				if ($numMatchingVariants < count($arrProductInfo['variants'])) {
					if ($blnStoreProductAndVariantMatchesInSession) {
						$_SESSION['lsShop']['filter']['matchedProducts'][$arrProductInfo['id']] = 'partial';
					}
				} else {
					if ($blnStoreProductAndVariantMatchesInSession) {
						$_SESSION['lsShop']['filter']['matchedProducts'][$arrProductInfo['id']] = 'complete';
					}
				}
				return true;
			}

		} else {
			/*
			 * If neither the product nor it's variants could match the filter anymore
			 * we can only return false here and filter out the whole product
			 */
			if ($blnStoreProductAndVariantMatchesInSession) {
				$_SESSION['lsShop']['filter']['matchedProducts'][$arrProductInfo['id']] = 'none';
			}
			return false;
		}
	}

	public static function resetMatchedProductsAndVariants() {
		$_SESSION['lsShop']['filter']['matchedProducts'] = array();
		$_SESSION['lsShop']['filter']['matchedVariants'] = array();
	}

	public static function adaptFilterCriteriaToCurrentFilterFormCriteria() {
		/*
		 * If the filter settings get altered, we have to reset the matchedProducts
		 * and matchedVariants because these cached filter results were related
		 * to the previous filter settings.
		 */
		ls_shop_filterHelper::resetMatchedProductsAndVariants();

		/*
		 * Get the attributes that are actually relevant for the current filtering process
		 *
		 */
		$_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'] = $_SESSION['lsShop']['filter']['criteria'];

		/*
		 * Walk through each attribute in the filter
		 */
		foreach ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'] as $attributeID => $arrAttributeValues) {
			/*
			 * Check for each attributeID if it is part of the criteria to use in the filter form
			 */
			if (!isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$attributeID])) {
				/*
				 * and if it's not, remove the entire attribute
				 */
				unset ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID]);
			} else {
				/*
				 * and if it is, we walk through each attributeValue and check if it exists in
				 * the criteria to use in the filter form.
				 */
				foreach ($arrAttributeValues as $k => $attributeValueID) {
					if (!in_array($attributeValueID, $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$attributeID])) {
						// and if it's not...
						if (count($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID]) > 1) {
							/*
							 * we have to remove the attributeValue from the filter
							 */
							unset ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID][$k]);
						} else {
							/*
							 * or we have to remove the entire attribute because this was it's only attributeValue in the filter
							 */
							unset ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID]);
						}
					}
				}
			}
		};

		/*
		 * Reset the producers that are no longer available in the filter form
		 */
		foreach ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['producers'] as $k => $producer) {
			if (!in_array($producer, $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'])) {
				unset ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['producers'][$k]);
			}
		};

		/*
		 * Reset the price range if it is no longer in the filter form
		 */
		if ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price']['low'] == $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price']['high']) {
			$_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['price'] = array(
				'low' => null,
				'high' => null
			);
		}
	}

	public static function setCriteriaToUseInFilterForm($arrProductsComplete = array()) {
		if (!isset($GLOBALS['merconis_globals']['criteriaToUseInFilterFormHasBeenSet'])) {
			$GLOBALS['merconis_globals']['criteriaToUseInFilterFormHasBeenSet'] = true;
		}
		if (!is_array($arrProductsComplete)) {
			return;
		}

		ls_shop_filterHelper::resetCriteriaToUseInFilterForm();

		foreach ($arrProductsComplete as $arrProduct) {
			if (!$arrProduct['lowestPrice']) {
				ls_shop_filterHelper::addPriceToCriteriaUsedInFilterForm($arrProduct['price']);
			} else {
				ls_shop_filterHelper::addPriceToCriteriaUsedInFilterForm($arrProduct['lowestPrice']);
				ls_shop_filterHelper::addPriceToCriteriaUsedInFilterForm($arrProduct['highestPrice']);
			}

			ls_shop_filterHelper::addProducerToCriteriaUsedInFilterForm($arrProduct['lsShopProductProducer']);

			foreach ($arrProduct['attributeAndValueIDs'] as $intAttributeID => $arrValueIDs) {
				ls_shop_filterHelper::addAttributeValueToCriteriaUsedInFilterForm($intAttributeID, $arrValueIDs);
			}
			foreach ($arrProduct['variants'] as $arrVariant) {
				foreach ($arrVariant['attributeAndValueIDs'] as $intAttributeID => $arrValueIDs) {
					ls_shop_filterHelper::addAttributeValueToCriteriaUsedInFilterForm($intAttributeID, $arrValueIDs);
				}
			}
		}

		/*
		 * #######################################
		 * Remove filter criteria from the filter form if they don't make any sense, e.g. attributes, if there
		 * is only one possible value and producers if there is only one possible producer
		 */
		foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'] as $attributeID => $arrAttributeValueIDs) {
			if (count($arrAttributeValueIDs) < 2) {
				unset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$attributeID]);
			}
		}

		if (count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers']) < 2) {
			$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'] = array();
		}
		/*
		 * #######################################
		 */

		ls_shop_filterHelper::adaptFilterCriteriaToCurrentFilterFormCriteria();
	}

	public static function getResetOption($blnImportant = false) {
		return array(
			'value' => '--reset--',
			'label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText109'],
			'class' => 'noSelection',
			'important' => $blnImportant
		);
	}

	public static function getCheckAllOption($blnImportant = false) {
		return array(
			'value' => '--checkall--',
			'label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText110'],
			'class' => 'checkAll',
			'important' => $blnImportant
		);
	}

	public static function filterReload() {
		\Controller::redirect(preg_replace('/(page_(?:crossSeller|standard).*?=)(.*?[0-9]*?)([^0-9]|&|$)/', '${1}1$3', \Environment::get('request')));
	}

	public static function resetFilter() {
		ls_shop_filterHelper::createEmptyFilterSession();
		ls_shop_filterHelper::filterReload();
	}

	public static function handleFilterModeSettings() {
		if (!isset($_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'])) {
			$_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'] = array();
		}

		$arr_filterModeInput = \Input::post('filterModeForAttribute');

		if (is_array($arr_filterModeInput)) {
			foreach ($arr_filterModeInput as $var_attribute => $str_filterMode) {
				$_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'][$var_attribute] = $str_filterMode;
			}
		}
	}

	public static function setFilter($what = '', $varValue) {
		if (!$what) {
			return;
		}

		/*
		 * If the filter settings get altered, we have to reset the matchedProducts
		 * and matchedVariants because these cached filter results were related
		 * to the previous filter settings.
		 */
		ls_shop_filterHelper::resetMatchedProductsAndVariants();

		switch ($what) {
			case 'attributes':
				if (!$varValue['value']) {
					unset($_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']]);
				} else {
					$varValue['value'] = is_array($varValue['value']) ? $varValue['value'] : array($varValue['value']);

					/*
					 * Attribute values that are currently in the filter criteria but that have not been sent with the filter form
					 * because they weren't even part of the filter form should be added. The reason is, that we don't want filter criteria
					 * to be reset by submitting a filter form if the user didn't intentionally uncheck them.
					 */
					if (isset($_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']]) && is_array($_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']])) {
						foreach ($_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']] as $attributeValueIDCurrentlyInFilter) {
							if (
								!in_array($attributeValueIDCurrentlyInFilter, $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$varValue['attributeID']])
								&&	!in_array($attributeValueIDCurrentlyInFilter, $varValue['value'])
							) {
								$varValue['value'][] = $attributeValueIDCurrentlyInFilter;
							}
						}
					}

					foreach($varValue['value'] as $k => $v) {
						if (!$v || $v == '--reset--' || $v == '--checkall--') {
							unset($varValue['value'][$k]);
						}
					}

					if (!count($varValue['value'])) {
						unset($_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']]);
						break;
					}

					$_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']] = $varValue['value'];
				}
				break;

			case 'price':
				$_SESSION['lsShop']['filter']['criteria']['price']['low'] = $varValue['low'];
				$_SESSION['lsShop']['filter']['criteria']['price']['high'] = $varValue['high'];
				break;

			case 'producers':
				if (!$varValue) {
					$_SESSION['lsShop']['filter']['criteria']['producers'] = array();
				} else {
					$varValue = is_array($varValue) ? $varValue : array($varValue);

					/*
					 * Producers that are currently in the filter criteria but that have not been sent with the filter form
					 * because they weren't even part of the filter form should be added. The reason is, that we don't want filter criteria
					 * to be reset by submitting a filter form if the user didn't intentionally uncheck them.
					 */
					foreach ($_SESSION['lsShop']['filter']['criteria']['producers'] as $producerCurrentlyInFilter) {
						if (
							!in_array($producerCurrentlyInFilter, $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'])
							&&	!in_array($producerCurrentlyInFilter, $varValue)
						) {
							$varValue[] = $producerCurrentlyInFilter;
						}
					}

					foreach($varValue as $k => $v) {
						if (!$v || $v == '--reset--' || $v == '--checkall--') {
							unset($varValue[$k]);
						}
					}

					$_SESSION['lsShop']['filter']['criteria']['producers'] = $varValue;
				}
				break;
		}
	}

	public static function getMatchesInProductResultSet($arrProductsResultSet = array(), $arrCriteriaToFilterWith = null, $blnStoreProductAndVariantMatchesInSession = true) {
		if (!is_array($arrProductsResultSet)) {
			return null;
		}

		$arrFilterMatches = array(
			'numMatching' => 0,
			'numNotMatching' => 0,
			'arrMatchingProducts' => array(),
			'numVariantsMatching' => 0
		);
		foreach ($arrProductsResultSet as $rowProduct) {
			if (ls_shop_filterHelper::checkIfProductMatchesFilter($rowProduct, $arrCriteriaToFilterWith, $blnStoreProductAndVariantMatchesInSession, $arrFilterMatches['numVariantsMatching'])) {
				$arrFilterMatches['numMatching']++;
				$arrFilterMatches['arrMatchingProducts'][] = $rowProduct;
			} else {
				$arrFilterMatches['numNotMatching']++;
			}
		}

		return $arrFilterMatches;
	}

	/*
	 * In this function we determine how many matches a selected criteria would have.
	 */
	public static function getEstimatedMatchNumbers($arrProductsResultSet = array()) {
		$_SESSION['lsShop']['filter']['matchEstimates']['attributeValues'] = array();
		$_SESSION['lsShop']['filter']['matchEstimates']['producers'] = array();
		if (!isset($GLOBALS['merconis_globals']['ls_shop_useFilterMatchEstimates']) || !$GLOBALS['merconis_globals']['ls_shop_useFilterMatchEstimates']) {
			return;
		}

		$_SESSION['lsShop']['filter']['noMatchEstimatesDetermined'] = false;
		if (
			isset($GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxNumProducts'])
			&&	$GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxNumProducts'] > 0
			&& count($arrProductsResultSet) > $GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxNumProducts']
		) {
			$_SESSION['lsShop']['filter']['noMatchEstimatesDetermined'] = true;
			return;
		}

		$numFilterValuesToDetermineEstimatesFor = 0;
		foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'] as $arrAttributeValues) {
			$numFilterValuesToDetermineEstimatesFor += count($arrAttributeValues);
		}
		$numFilterValuesToDetermineEstimatesFor += count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers']);

		if (
			isset($GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxFilterValues'])
			&&	$GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxFilterValues'] > 0
			&& $numFilterValuesToDetermineEstimatesFor > $GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxFilterValues']
		) {
			$_SESSION['lsShop']['filter']['noMatchEstimatesDetermined'] = true;
			return;
		}

		/*
		 * Getting the estimates for the attributes
		 */
		/*
		 * Walk through all attributes used in the filter form and create an array with filter criteria that does not
		 * include the current attribute
		 */
		foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'] as $attributeID => $arrAttributeValues) {
			$tmpCriteriaToFilterWith = $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'];

			/*
			 * Remove the current attribute from the criteria array
			 */
			if (isset($tmpCriteriaToFilterWith['attributes'][$attributeID])) {
				unset($tmpCriteriaToFilterWith['attributes'][$attributeID]);
			}

			/*
			 * Walk through all the attribute values and create a temporary filter criteria array in which the current
			 * attribute value is added
			 */
			foreach ($arrAttributeValues as $attributeValueID) {
				$tmpCriteriaToFilterWithPlusCurrentValue = $tmpCriteriaToFilterWith;
				$tmpCriteriaToFilterWithPlusCurrentValue['attributes'][$attributeID] = array($attributeValueID);

				/*
				 * Filter the previously created result set using only the current attribute value
				 */
				$arrFilterMatches = ls_shop_filterHelper::getMatchesInProductResultSet($arrProductsResultSet, $tmpCriteriaToFilterWithPlusCurrentValue, false);

				/*
				 * Storing the number of matches
				 */
				$_SESSION['lsShop']['filter']['matchEstimates']['attributeValues'][$attributeValueID] = array(
					'products' => $arrFilterMatches['numMatching'],
					'variants' => $arrFilterMatches['numVariantsMatching']
				);
			}
		}

		/*
		 * Getting the estimates for the producers
		 */
		$tmpCriteriaToFilterWith = $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'];

		/*
		 * Remove the producers from the criteria array
		 */
		$tmpCriteriaToFilterWith['producers'] = array();

		/*
		 * Walk through all the producers and create a temporary filter criteria array in which the current
		 * producer is added
		 */
		foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'] as $producerValue) {
			$tmpCriteriaToFilterWithPlusCurrentValue = $tmpCriteriaToFilterWith;
			$tmpCriteriaToFilterWithPlusCurrentValue['producers'] = array($producerValue);

			/*
			 * Filter the previously created result set using only the current producer value
			 */
			$arrFilterMatches = ls_shop_filterHelper::getMatchesInProductResultSet($arrProductsResultSet, $tmpCriteriaToFilterWithPlusCurrentValue, false);

			/*
			 * Storing the number of matches
			 */
			$_SESSION['lsShop']['filter']['matchEstimates']['producers'][md5($producerValue)] = array(
				'products' => $arrFilterMatches['numMatching'],
				'variants' => $arrFilterMatches['numVariantsMatching']
			);
		}
	}
}