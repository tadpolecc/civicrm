<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * form to process actions on the field aspect of Price
 */
class CRM_Price_Form_Field extends CRM_Core_Form {

  use CRM_Core_Form_EntityFormTrait;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'PriceField';
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Get the entity id being edited.
   *
   * @return int|null
   */
  public function getEntityId() {
    return $this->_fid;
  }

  /**
   * Constants for number of options for data types of multiple option.
   */
  const NUM_OPTION = 15;

  /**
   * The custom set id saved to the session for an update.
   *
   * @var int
   */
  protected $_sid;

  /**
   * The field id, used when editing the field
   *
   * @var int
   */
  protected $_fid;

  /**
   * The extended component Id.
   *
   * @var array
   */
  protected $_extendComponentId;

  /**
   * Variable is set if price set is used for membership.
   * @var bool
   */
  protected $_useForMember;

  /**
   * Set the price Set Id (only used in tests)
   */
  public function setPriceSetId($priceSetId) {
    $this->_sid = $priceSetId;
  }

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {

    $this->_sid = CRM_Utils_Request::retrieve('sid', 'Positive', $this);
    $this->_fid = CRM_Utils_Request::retrieve('fid', 'Positive', $this);
    $url = CRM_Utils_System::url('civicrm/admin/price/field', "reset=1&action=browse&sid={$this->_sid}");
    $breadCrumb = [['title' => ts('Price Set Fields'), 'url' => $url]];

    $this->_extendComponentId = [];
    $extendComponentId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_sid, 'extends', 'id');
    if ($extendComponentId) {
      $this->_extendComponentId = explode(CRM_Core_DAO::VALUE_SEPARATOR, $extendComponentId);
    }

    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    $this->setPageTitle(ts('Price Field'));
  }

  /**
   * Set default values for the form.
   *
   * @return array
   *   array of default values
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {
    $defaults = [];
    // is it an edit operation ?
    if ($this->getEntityId()) {
      $params = ['id' => $this->getEntityId()];
      $this->assign('fid', $this->getEntityId());
      CRM_Price_BAO_PriceField::retrieve($params, $defaults);
      $this->_sid = $defaults['price_set_id'];

      // if text, retrieve price
      if ($defaults['html_type'] === 'Text') {
        $isActive = $defaults['is_active'];
        $valueParams = ['price_field_id' => $this->getEntityId()];

        CRM_Price_BAO_PriceFieldValue::retrieve($valueParams, $defaults);

        // fix the display of the monetary value, CRM-4038
        $defaults['price'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($defaults['amount']);
        $defaults['is_active'] = $isActive;
      }

    }
    else {
      $defaults['is_active'] = 1;
      for ($i = 1; $i <= self::NUM_OPTION; $i++) {
        $defaults['option_status[' . $i . ']'] = 1;
        $defaults['option_weight[' . $i . ']'] = $i;
        $defaults['option_visibility_id[' . $i . ']'] = 1;
      }
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $fieldValues = ['price_set_id' => $this->_sid];
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Price_DAO_PriceField', $fieldValues);
      $defaults['options_per_line'] = 1;
      $defaults['is_display_amounts'] = 1;
    }

    if (isset($this->_sid) && $this->_action == CRM_Core_Action::ADD) {
      $financialTypeId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_sid, 'financial_type_id');
      $defaults['financial_type_id'] = $financialTypeId;
      for ($i = 1; $i <= self::NUM_OPTION; $i++) {
        $defaults['option_financial_type_id[' . $i . ']'] = $financialTypeId;
      }
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // lets trim all the whitespace
    $this->applyFilter('__ALL__', 'trim');

    // add a hidden field to remember the price set id
    // this get around the browser tab issue
    $this->add('hidden', 'sid', $this->_sid);
    $this->add('hidden', 'fid', $this->getEntityId());

    // label
    $this->add('text', 'label', ts('Field Label'), CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceField', 'label'), TRUE);

    // html_type
    $javascript = ['onchange' => 'option_html_type(this.form);'];

    $htmlTypes = CRM_Price_BAO_PriceField::htmlTypes();

    // Text box for Participant Count for a field

    // Financial Type
    $financialTypes = CRM_Financial_BAO_FinancialType::getIncomeFinancialType();
    $this->assign('financialType', $financialTypes);
    //Visibility Type Options
    $visibilityType = CRM_Core_PseudoConstant::visibility();
    $this->assign('visibilityType', $visibilityType);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceFieldValue');

    $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      [' ' => ts('- select -')] + $financialTypes
    );

    $this->assign('useForMember', FALSE);
    if ($this->extendsEvent()) {
      $this->add('text', 'count', ts('Participant Count'), $attributes['count']);

      $this->addRule('count', ts('Participant Count should be a positive number'), 'positiveInteger');

      $this->add('text', 'max_value', ts('Max Participants'), $attributes['max_value']);
      $this->addRule('max_value', ts('Please enter a valid Max Participants.'), 'positiveInteger');

      $this->assign('useForEvent', TRUE);
    }
    else {
      if ($this->extendsMembership()) {
        $this->_useForMember = 1;
        $this->assign('useForMember', $this->_useForMember);
      }
      $this->assign('useForEvent', FALSE);
    }

    $sel = $this->add('select', 'html_type', ts('Field Type'),
      $htmlTypes, TRUE, $javascript
    );

    // price (for text inputs)
    $this->add('text', 'price', ts('Unit Price'));
    $this->registerRule('price', 'callback', 'money', 'CRM_Utils_Rule');
    $this->addRule('price', ts('must be a monetary value'), 'money');

    $this->add('text', 'non_deductible_amount', ts('Non-deductible Amount'), NULL);
    $this->registerRule('non_deductible_amount', 'callback', 'money', 'CRM_Utils_Rule');
    $this->addRule('non_deductible_amount', ts('Please enter a monetary value for this field.'), 'money');

    if ($this->_action == CRM_Core_Action::UPDATE) {
      $this->freeze('html_type');
    }

    // form fields of Custom Option rows
    $_showHide = new CRM_Core_ShowHideBlocks();

    for ($i = 1; $i <= self::NUM_OPTION; $i++) {

      //the show hide blocks
      $showBlocks = 'optionField_' . $i;
      if ($i > 2) {
        $_showHide->addHide($showBlocks);
        if ($i == self::NUM_OPTION) {
          $_showHide->addHide('additionalOption');
        }
      }
      else {
        $_showHide->addShow($showBlocks);
      }
      // label
      $attributes['label']['size'] = 25;
      $this->add('text', 'option_label[' . $i . ']', ts('Label'), $attributes['label']);

      // amount
      $this->add('text', 'option_amount[' . $i . ']', ts('Amount'), $attributes['amount']);
      $this->addRule('option_amount[' . $i . ']', ts('Please enter a valid amount for this field.'), 'money');

      //Financial Type
      $this->add(
        'select',
        'option_financial_type_id[' . $i . ']',
        ts('Financial Type'),
        ['' => ts('- select -')] + $financialTypes
      );
      if ($this->extendsEvent()) {
        // count
        $this->add('text', 'option_count[' . $i . ']', ts('Participant Count'), $attributes['count']);
        $this->addRule('option_count[' . $i . ']', ts('Please enter a valid Participants Count.'), 'positiveInteger');

        // max_value
        $this->add('text', 'option_max_value[' . $i . ']', ts('Max Participants'), $attributes['max_value']);
        $this->addRule('option_max_value[' . $i . ']', ts('Please enter a valid Max Participants.'), 'positiveInteger');

        // description
        //$this->add('textArea', 'option_description['.$i.']', ts('Description'), array('rows' => 1, 'cols' => 40 ));
      }
      if ($this->extendsMembership()) {
        $membershipTypes = CRM_Member_PseudoConstant::membershipType();
        $js = ['onchange' => "calculateRowValues( $i );"];

        $this->add('select', 'membership_type_id[' . $i . ']', ts('Membership Type'),
          ['' => ' '] + $membershipTypes, FALSE, $js
        );
        $this->add('text', 'membership_num_terms[' . $i . ']', ts('Number of Terms'), CRM_Utils_Array::value('membership_num_terms', $attributes));
      }

      // weight
      $this->add('number', 'option_weight[' . $i . ']', ts('Order'), $attributes['weight']);

      // is active ?
      $this->add('checkbox', 'option_status[' . $i . ']', ts('Active?'));

      $this->add('select', 'option_visibility_id[' . $i . ']', ts('Visibility'), $visibilityType);
      $defaultOption[$i] = NULL;

      //for checkbox handling of default option
      $this->add('checkbox', "default_checkbox_option[$i]", NULL);
    }
    //default option selection
    $this->addRadio('default_option', NULL, $defaultOption);
    $_showHide->addToTemplate();

    // is_display_amounts
    $this->add('checkbox', 'is_display_amounts', ts('Display Amount?'));

    // weight
    $this->add('number', 'weight', ts('Order'), CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceField', 'weight'), TRUE);
    $this->addRule('weight', ts('is a numeric field'), 'numeric');

    // checkbox / radio options per line
    $this->add('text', 'options_per_line', ts('Options Per Line'));
    $this->addRule('options_per_line', ts('must be a numeric value'), 'numeric');

    $this->add('textarea', 'help_pre', ts('Pre Field Help'),
      CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceField', 'help_pre')
    );

    // help post, mask, attributes, javascript ?
    $this->add('textarea', 'help_post', ts('Post Field Help'),
      CRM_Core_DAO::getAttribute('CRM_Price_DAO_PriceField', 'help_post')
    );

    $this->add('datepicker', 'active_on', ts('Active On'), [], FALSE, ['time' => TRUE]);

    // expire_on
    $this->add('datepicker', 'expire_on', ts('Expire On'), [], FALSE, ['time' => TRUE]);

    // is required ?
    $this->add('checkbox', 'is_required', ts('Required?'));

    // is active ?
    $this->add('checkbox', 'is_active', ts('Active?'));

    // add buttons
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'next',
        'name' => ts('Save and New'),
        'subName' => 'new',
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
    // is public?
    $this->add('select', 'visibility_id', ts('Visibility'), CRM_Core_PseudoConstant::visibility());

    // add a form rule to check default value
    $this->addFormRule(['CRM_Price_Form_Field', 'formRule'], $this);

    // if view mode pls freeze it with the done button.
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
      $url = CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=browse&sid=' . $this->_sid);
      $this->addElement('xbutton',
        'done',
        ts('Done'),
        [
          'type' => 'button',
          'onclick' => "location.href='$url'",
        ]
      );
    }
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param array $files
   * @param self $form
   *
   * @return array|bool
   *   if errors then list of errors to be posted back to the form,
   *                  true otherwise
   */
  public static function formRule($fields, $files, $form) {

    // all option fields are of type "money"
    $errors = [];

    /** Check the option values entered
     *  Appropriate values are required for the selected datatype
     *  Incomplete row checking is also required.
     */
    if (($form->_action & CRM_Core_Action::ADD || $form->_action & CRM_Core_Action::UPDATE) &&
      $fields['html_type'] === 'Text'
    ) {
      if ($fields['price'] == NULL) {
        $errors['price'] = ts('Price is a required field');
      }
      if ($fields['financial_type_id'] == '') {
        $errors['financial_type_id'] = ts('Financial Type is a required field');
      }
    }

    if ((is_numeric($fields['count'] ?? '') &&
        empty($fields['count'])
      ) &&
      (($fields['html_type'] ?? NULL) === 'Text')
    ) {
      $errors['count'] = ts('Participant Count must be greater than zero.');
    }

    // Validate start/end date inputs
    $validateDates = \CRM_Utils_Date::validateStartEndDatepickerInputs('active_on', $fields['active_on'], 'expire_on', $fields['expire_on']);
    if ($validateDates !== TRUE) {
      $errors[$validateDates['key']] = $validateDates['message'];
    }

    if ($form->_action & CRM_Core_Action::ADD) {
      if ($fields['html_type'] !== 'Text') {
        $countemptyrows = 0;
        $publicOptionCount = $_flagOption = $_rowError = 0;

        $_showHide = new CRM_Core_ShowHideBlocks();
        $visibilityOptions = CRM_Price_BAO_PriceFieldValue::buildOptions('visibility_id', 'validate');

        for ($index = 1; $index <= self::NUM_OPTION; $index++) {

          $noLabel = $noAmount = $noWeight = 1;
          if (!empty($fields['option_label'][$index])) {
            $noLabel = 0;
            $duplicateIndex = CRM_Utils_Array::key($fields['option_label'][$index],
              $fields['option_label']
            );

            if ((!($duplicateIndex === FALSE)) &&
              (!($duplicateIndex == $index))
            ) {
              $errors["option_label[{$index}]"] = ts('Duplicate label value');
              $_flagOption = 1;
            }
          }
          if ($form->_useForMember) {
            if (!empty($fields['membership_type_id'][$index])) {
              $memTypesIDS[] = $fields['membership_type_id'][$index];
            }
          }

          // allow for 0 value.
          if (!empty($fields['option_amount'][$index]) ||
            strlen($fields['option_amount'][$index] ?? '') > 0
          ) {
            $noAmount = 0;
          }

          if (!empty($fields['option_weight'][$index])) {
            $noWeight = 0;
            $duplicateIndex = CRM_Utils_Array::key($fields['option_weight'][$index],
              $fields['option_weight']
            );

            if ((!($duplicateIndex === FALSE)) &&
              (!($duplicateIndex == $index))
            ) {
              $errors["option_weight[{$index}]"] = ts('Duplicate weight value');
              $_flagOption = 1;
            }
          }
          if (!$noLabel && !$noAmount && !empty($fields['option_financial_type_id']) && $fields['option_financial_type_id'][$index] == '' && $fields['html_type'] !== 'Text') {
            $errors["option_financial_type_id[{$index}]"] = ts('Financial Type is a Required field.');
          }
          if ($noLabel && !$noAmount) {
            $errors["option_label[{$index}]"] = ts('Label cannot be empty.');
            $_flagOption = 1;
          }

          if (!$noLabel && $noAmount) {
            $errors["option_amount[{$index}]"] = ts('Amount cannot be empty.');
            $_flagOption = 1;
          }

          if ($noLabel && $noAmount) {
            $countemptyrows++;
            $_emptyRow = 1;
          }
          elseif (!empty($fields['option_max_value'][$index]) &&
            !empty($fields['option_count'][$index]) &&
            ($fields['option_count'][$index] > $fields['option_max_value'][$index])
          ) {
            $errors["option_max_value[{$index}]"] = ts('Participant count can not be greater than max participants.');
            $_flagOption = 1;
          }

          $showBlocks = 'optionField_' . $index;
          if ($_flagOption) {
            $_showHide->addShow($showBlocks);
            $_rowError = 1;
          }

          if (!empty($_emptyRow)) {
            $_showHide->addHide($showBlocks);
          }
          else {
            $_showHide->addShow($showBlocks);
          }
          if ($index == self::NUM_OPTION) {
            $hideBlock = 'additionalOption';
            $_showHide->addHide($hideBlock);
          }

          if (!empty($fields['option_visibility_id'][$index]) && (!$noLabel || !$noAmount)) {
            if ($visibilityOptions[$fields['option_visibility_id'][$index]] === 'public') {
              $publicOptionCount++;
            }
          }

          $_flagOption = $_emptyRow = 0;
        }

        if (!empty($memTypesIDS)) {
          // check for checkboxes allowing user to select multiple memberships from same membership organization
          if ($fields['html_type'] === 'CheckBox') {
            $foundDuplicate = FALSE;
            $orgIds = [];
            foreach ($memTypesIDS as $key => $val) {
              $memTypeDetails = CRM_Member_BAO_MembershipType::getMembershipType($val);
              if (in_array($memTypeDetails['member_of_contact_id'], $orgIds)) {
                $foundDuplicate = TRUE;
                break;
              }
              $orgIds[$val] = $memTypeDetails['member_of_contact_id'];

            }
            if ($foundDuplicate) {
              $errors['_qf_default'] = ts('You have selected multiple memberships for the same organization or entity. Please review your selections and choose only one membership per entity.');
            }
          }

          // CRM-10390 - Only one price field in a set can include auto-renew membership options
          $foundAutorenew = FALSE;
          foreach ($memTypesIDS as $key => $val) {
            // see if any price field option values in this price field are for memberships with autorenew
            $memTypeDetails = CRM_Member_BAO_MembershipType::getMembershipType($val);
            if (!empty($memTypeDetails['auto_renew'])) {
              $foundAutorenew = TRUE;
              break;
            }
          }

          if ($foundAutorenew) {
            // if so, check for other fields in this price set which also have auto-renew membership options
            $otherFieldAutorenew = CRM_Price_BAO_PriceSet::checkAutoRenewForPriceSet($form->_sid);
            if ($otherFieldAutorenew) {
              $errors['_qf_default'] = ts('You can include auto-renew membership choices for only one price field in a price set. Another field in this set already contains one or more auto-renew membership options.');
            }
          }
        }
        $_showHide->addToTemplate();

        if ($countemptyrows == 15) {
          $errors['option_label[1]'] = $errors['option_amount[1]'] = ts('Label and value cannot be empty.');
          $_flagOption = 1;
        }

        if ($visibilityOptions[$fields['visibility_id']] === 'public' && $publicOptionCount == 0) {
          $errors['visibility_id'] = ts('You have selected to make this field public but have not enabled any public price options. Please update your selections to include a public price option, or make this field admin visibility only.');
          for ($index = 1; $index <= self::NUM_OPTION; $index++) {
            if (!empty($fields['option_label'][$index]) || !empty($fields['option_amount'][$index])) {
              $errors["option_visibility_id[{$index}]"] = ts('Public field should at least have one public option.');
            }
          }
        }

        if ($visibilityOptions[$fields['visibility_id']] === 'admin' && $publicOptionCount > 0) {
          $errors['visibility_id'] = ts('Field with \'Admin\' visibility should only contain \'Admin\' options.');

          for ($index = 1; $index <= self::NUM_OPTION; $index++) {

            $isOptionSet = !empty($fields['option_label'][$index]) || !empty($fields['option_amount'][$index]);
            $currentOptionVisibility = $visibilityOptions[$fields['option_visibility_id'][$index]] ?? NULL;

            if ($isOptionSet && $currentOptionVisibility === 'public') {
              $errors["option_visibility_id[{$index}]"] = ts('\'Admin\' field should only have \'Admin\' visibility options.');
            }
          }
        }
      }
      elseif (!empty($fields['max_value']) &&
        !empty($fields['count']) &&
        ($fields['count'] > $fields['max_value'])
      ) {
        $errors['max_value'] = ts('Participant count can not be greater than max participants.');
      }

      // do not process if no option rows were submitted
      if (empty($fields['option_amount']) && empty($fields['option_label'])) {
        return TRUE;
      }

      if (empty($fields['option_name'])) {
        $fields['option_amount'] = [];
      }

      if (empty($fields['option_label'])) {
        $fields['option_label'] = [];
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues('Field');
    $params['id'] = $this->getEntityId();
    $submitResult = $this->submit($params);
    // Update _fid property to match the saved id especially important in add mode so extensions can reliably call getEntityID()
    $this->_fid = $submitResult->id;
    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another price set field.'), '', 'info');
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/price/field/edit', 'reset=1&action=add&sid=' . $this->_sid));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=browse&sid=' . $this->_sid));
    }
  }

  public function submit($params) {
    $params['price'] = CRM_Utils_Rule::cleanMoney($params['price']);
    foreach ($params['option_amount'] as $key => $amount) {
      $params['option_amount'][$key] = CRM_Utils_Rule::cleanMoney($amount);
    }

    $params['is_display_amounts'] ??= FALSE;
    $params['is_required'] ??= FALSE;
    $params['is_active'] ??= FALSE;
    $params['financial_type_id'] ??= FALSE;
    $params['visibility_id'] ??= FALSE;
    $params['count'] ??= FALSE;

    // need the FKEY - price set id
    $params['price_set_id'] = $this->_sid;

    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $fieldValues = ['price_set_id' => $this->_sid];
      $oldWeight = NULL;
      if ($this->getEntityId()) {
        $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $this->getEntityId(), 'weight', 'id');
      }
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Price_DAO_PriceField', $oldWeight, $params['weight'], $fieldValues);
    }

    // make value <=> name consistency.
    if (isset($params['option_name'])) {
      $params['option_value'] = $params['option_name'];
    }
    $params['is_enter_qty'] ??= FALSE;

    if ($params['html_type'] === 'Text') {
      // if html type is Text, force is_enter_qty on
      $params['is_enter_qty'] = 1;
      // modify params values as per the option group and option
      // value
      $params['option_amount'] = [1 => $params['price']];
      $params['option_label'] = [1 => $params['label']];
      $params['option_count'] = [1 => $params['count']];
      $params['option_max_value'] = [1 => $params['max_value'] ?? NULL];
      //$params['option_description']  = array( 1 => $params['description'] );
      $params['option_weight'] = [1 => $params['weight']];
      $params['option_financial_type_id'] = [1 => $params['financial_type_id']];
      $params['option_visibility_id'] = [1 => $params['visibility_id'] ?? NULL];
    }

    $params['membership_num_terms'] = (!empty($params['membership_type_id'])) ? CRM_Utils_Array::value('membership_num_terms', $params, 1) : NULL;

    return CRM_Price_BAO_PriceField::create($params);
  }

  /**
   * Does the price Set extend memberships.
   *
   * @return bool
   */
  protected function extendsMembership(): bool {
    if (!CRM_Core_Component::isEnabled('CiviMember')) {
      return FALSE;
    }
    return in_array(CRM_Core_Component::getComponentID('CiviMember'), array_filter($this->_extendComponentId));
  }

  /**
   * Does the price Set extend events.
   *
   * @return bool
   */
  protected function extendsEvent(): bool {
    if (!CRM_Core_Component::isEnabled('CiviEvent')) {
      return FALSE;
    }
    return in_array(CRM_Core_Component::getComponentID('CiviEvent'), array_filter($this->_extendComponentId));
  }

  /**
   * Get id of Price Field being acted on.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   */
  public function getPriceFieldID(): ?int {
    return $this->_fid;
  }

}
