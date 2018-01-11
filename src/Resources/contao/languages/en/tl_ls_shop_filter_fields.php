<?php

	/*
	 * Fields
	 */

	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['alias']								= array('Alias', 'Unique designation to be used as a reference.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['title']								= array('Designation');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['dataSource']							= array('Data source', 'Define here where the filter field gets it\'s values from.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['sourceAttribute']						= array('Source attribute');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['classForFilterFormField']				= array('CSS class', 'This CSS class will be used with the filter form field.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['numItemsInReducedMode']				= array('Number of values in &quot;reduced mode&quot;', 'Enter 0 if you want to show all values in the &quot;reduced mode&quot; or, if you have marked some values as &quot;important&quot;, the important ones.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterFormFieldType']					= array('Field type');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['priority']								= array('Priority', 'This priority will be used to sort the fields in the filter form. Fields with higher priority will be shown above fields with lower priority.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['startClosedIfNothingSelected']			= array('Close field if nothing is selected');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterMode']							= array('Filter mode', 'Define the logic operation to use if multiple filter options are selected.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['displayFilterModeInfo']				= array('Display filter mode info', 'Check if you want to display a message informing the customer about the applied filter mode.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['makeFilterModeUserAdjustable']			= array('Make filter mode adjustable in the frontend');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUse']						= array('Template');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['published']							= array('Active');

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['title_legend']   = 'Designation and alias';
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['output_legend'] = 'Display settings';
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['dataSource_legend'] = 'Data source';
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['published_legend'] = 'Activation';
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterLogic_legend'] = 'Filter logic';
	
	/*
	 * Reference
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterFormFieldType']['options'] = array(
		'checkbox' => 'Checkbox menu',
		'radio' => 'Radio menu'
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterMode']['options'] = array(
		'and' => 'and',
		'or' => 'or'
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['dataSource']['options'] = array(
		'attribute' => array('Product attribute', 'The attribute values of the attribute you select as the data source will be used. Don\'t enter child records for this filter field in this case.'),
		'producer' => array('Producers', 'The producers you entered in your product records will be used as field values. Enter child records with corresponding values in order to be able to sort and prioritize certain values and to add individual classes.'),
		'price' => array('Price', 'Two input fields for the minimum and maximum price will be displayed. Don\'t enter child records for this filter field in this case.')
	);
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['new']        = array('New field', 'Add a new filter field attribute');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['editheader']        = array('Edit field', 'Edit filter field ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['edit'] = array('Edit field', 'Edit filter field ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['delete']        = array('Delete field', 'Delete filter field ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['copy']        = array('Copy field', 'Copy filter field ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['show']        = array('Show details', 'Show details of product feature ID %s');
